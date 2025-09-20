<?php

namespace App\Services;

use App\Models\Child;
use App\Models\TimeBlock;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

class IcsImportService
{
    public function __construct(
        private SupabaseClient $supabase
    ) {}

    /**
     * Import ICS file and create time blocks for external events
     */
    public function importIcsFile(UploadedFile $file, int $childId, int $userId): array
    {
        // Validate child belongs to user
        $child = Child::find($childId);
        if (! $child || (int) $child->user_id !== $userId) {
            throw new \InvalidArgumentException('Child not found or access denied');
        }

        // Read and parse ICS file
        $content = file_get_contents($file->getRealPath());
        $events = $this->parseIcsContent($content);

        $imported = [];
        $errors = [];

        foreach ($events as $event) {
            try {
                $timeBlock = $this->createTimeBlockFromEvent($event, $childId);
                if ($timeBlock) {
                    $imported[] = $timeBlock;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'event' => $event['summary'] ?? 'Unknown event',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'imported_count' => count($imported),
            'error_count' => count($errors),
            'imported_events' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * Import ICS from URL (for recurring external classes)
     */
    public function importIcsFromUrl(string $url, int $childId, int $userId): array
    {
        // Validate child belongs to user
        $child = Child::find($childId);
        if (! $child || (int) $child->user_id !== $userId) {
            throw new \InvalidArgumentException('Child not found or access denied');
        }

        // Validate URL
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL provided');
        }

        // Fetch ICS content from URL
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'HomeschoolApp/1.0',
                'follow_location' => true,
                'max_redirects' => 3,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            throw new \Exception('Failed to fetch calendar from URL');
        }

        // Parse and import
        $events = $this->parseIcsContent($content);

        $imported = [];
        $errors = [];

        foreach ($events as $event) {
            try {
                $timeBlock = $this->createTimeBlockFromEvent($event, $childId);
                if ($timeBlock) {
                    $imported[] = $timeBlock;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'event' => $event['summary'] ?? 'Unknown event',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'imported_count' => count($imported),
            'error_count' => count($errors),
            'imported_events' => $imported,
            'errors' => $errors,
            'source_url' => $url,
        ];
    }

    /**
     * Parse ICS content and extract events
     */
    private function parseIcsContent(string $content): array
    {
        $lines = explode("\n", str_replace("\r", '', $content));
        $events = [];
        $currentEvent = null;
        $inEvent = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === 'BEGIN:VEVENT') {
                $inEvent = true;
                $currentEvent = [];

                continue;
            }

            if ($line === 'END:VEVENT') {
                if ($currentEvent && $inEvent) {
                    $events[] = $currentEvent;
                }
                $inEvent = false;
                $currentEvent = null;

                continue;
            }

            if (! $inEvent || ! $line) {
                continue;
            }

            // Parse property line
            $colonPos = strpos($line, ':');
            if ($colonPos === false) {
                continue;
            }

            $property = substr($line, 0, $colonPos);
            $value = substr($line, $colonPos + 1);

            // Handle properties with parameters (e.g., DTSTART;TZID=...)
            $semicolonPos = strpos($property, ';');
            if ($semicolonPos !== false) {
                $property = substr($property, 0, $semicolonPos);
            }

            switch (strtoupper($property)) {
                case 'SUMMARY':
                    $currentEvent['summary'] = $this->unescapeIcsValue($value);
                    break;
                case 'DTSTART':
                    $currentEvent['start'] = $this->parseIcsDateTime($value);
                    break;
                case 'DTEND':
                    $currentEvent['end'] = $this->parseIcsDateTime($value);
                    break;
                case 'DESCRIPTION':
                    $currentEvent['description'] = $this->unescapeIcsValue($value);
                    break;
                case 'LOCATION':
                    $currentEvent['location'] = $this->unescapeIcsValue($value);
                    break;
                case 'RRULE':
                    $currentEvent['rrule'] = $value;
                    break;
                case 'UID':
                    $currentEvent['uid'] = $value;
                    break;
            }
        }

        return $this->expandRecurringEvents($events);
    }

    /**
     * Parse ICS datetime format
     */
    private function parseIcsDateTime(string $value): ?Carbon
    {
        try {
            // Remove timezone identifier if present
            $value = preg_replace('/;TZID=[^:]*:/', '', $value);

            if (strlen($value) === 8) {
                // Date only (YYYYMMDD)
                return Carbon::createFromFormat('Ymd', $value)->startOfDay();
            } elseif (strlen($value) === 15 && substr($value, -1) === 'Z') {
                // UTC datetime (YYYYMMDDTHHMMSSZ)
                return Carbon::createFromFormat('Ymd\THis\Z', $value, 'UTC');
            } elseif (strlen($value) === 15) {
                // Local datetime (YYYYMMDDTHHMMSS)
                return Carbon::createFromFormat('Ymd\THis', $value);
            }
        } catch (\Exception $e) {
            // Fallback parsing
            try {
                return Carbon::parse($value);
            } catch (\Exception $e2) {
                return null;
            }
        }

        return null;
    }

    /**
     * Unescape ICS text values
     */
    private function unescapeIcsValue(string $value): string
    {
        return str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], $value);
    }

    /**
     * Expand recurring events (basic RRULE support)
     */
    private function expandRecurringEvents(array $events): array
    {
        $expanded = [];
        $maxDate = Carbon::now()->addMonths(6); // Don't expand beyond 6 months

        foreach ($events as $event) {
            if (! isset($event['rrule'])) {
                // Single event
                $expanded[] = $event;

                continue;
            }

            // Parse basic RRULE (FREQ=WEEKLY, FREQ=DAILY, etc.)
            $rrule = $this->parseRrule($event['rrule']);
            if (! $rrule || ! isset($event['start'])) {
                $expanded[] = $event; // Skip if can't parse

                continue;
            }

            $current = $event['start']->copy();
            $originalDuration = isset($event['end'])
                ? $event['end']->diffInMinutes($event['start'])
                : 60;

            $count = 0;
            while ($current->lte($maxDate) && $count < 100) { // Safety limit
                $expandedEvent = $event;
                $expandedEvent['start'] = $current->copy();
                $expandedEvent['end'] = $current->copy()->addMinutes($originalDuration);
                $expanded[] = $expandedEvent;

                // Move to next occurrence
                switch ($rrule['freq']) {
                    case 'DAILY':
                        $current->addDay();
                        break;
                    case 'WEEKLY':
                        $current->addWeek();
                        break;
                    case 'MONTHLY':
                        $current->addMonth();
                        break;
                    default:
                        break 2; // Unknown frequency, stop
                }

                $count++;

                if (isset($rrule['count']) && $count >= $rrule['count']) {
                    break;
                }

                if (isset($rrule['until']) && $current->gt($rrule['until'])) {
                    break;
                }
            }
        }

        return $expanded;
    }

    /**
     * Parse basic RRULE
     */
    private function parseRrule(string $rrule): ?array
    {
        $parts = explode(';', $rrule);
        $parsed = [];

        foreach ($parts as $part) {
            $equalPos = strpos($part, '=');
            if ($equalPos === false) {
                continue;
            }

            $key = strtoupper(substr($part, 0, $equalPos));
            $value = substr($part, $equalPos + 1);

            switch ($key) {
                case 'FREQ':
                    $parsed['freq'] = strtoupper($value);
                    break;
                case 'COUNT':
                    $parsed['count'] = (int) $value;
                    break;
                case 'UNTIL':
                    $parsed['until'] = $this->parseIcsDateTime($value);
                    break;
                case 'INTERVAL':
                    $parsed['interval'] = (int) $value;
                    break;
            }
        }

        return empty($parsed) ? null : $parsed;
    }

    /**
     * Get supported ICS file extensions
     */
    public static function getSupportedExtensions(): array
    {
        return ['ics', 'ical', 'ifb', 'icalendar'];
    }

    /**
     * Validate ICS file
     */
    public function validateIcsFile(UploadedFile $file): array
    {
        $errors = [];

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, self::getSupportedExtensions())) {
            $errors[] = 'Invalid file type. Supported formats: '.implode(', ', self::getSupportedExtensions());
        }

        // Check file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            $errors[] = 'File too large. Maximum size is 5MB.';
        }

        // Try to read content
        if (empty($errors)) {
            try {
                $content = file_get_contents($file->getRealPath());
                if (strpos($content, 'BEGIN:VCALENDAR') === false) {
                    $errors[] = 'Invalid ICS file format. Must contain VCALENDAR data.';
                }
            } catch (\Exception $e) {
                $errors[] = 'Could not read file content: '.$e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Preview events from ICS file without importing
     */
    public function previewIcsFile(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());
        $events = $this->parseIcsContent($content);

        // Limit preview to next 30 days and max 50 events
        $cutoffDate = Carbon::now()->addDays(30);
        $preview = [];

        foreach ($events as $event) {
            if (count($preview) >= 50) {
                break;
            }

            if (isset($event['start']) &&
                $event['start'] instanceof Carbon &&
                $event['start']->gte(Carbon::now()) &&
                $event['start']->lte($cutoffDate)) {

                $preview[] = [
                    'summary' => $event['summary'] ?? 'Untitled Event',
                    'start' => $event['start']->format('M j, Y g:i A'),
                    'end' => isset($event['end']) ? $event['end']->format('g:i A') : null,
                    'duration_minutes' => isset($event['end'])
                        ? $event['end']->diffInMinutes($event['start'])
                        : 60,
                    'location' => $event['location'] ?? null,
                    'day_of_week' => $event['start']->dayOfWeekIso,
                    'day_name' => $event['start']->format('l'),
                ];
            }
        }

        return [
            'total_events_found' => count($events),
            'preview_events' => $preview,
            'preview_limited' => count($events) > 50,
        ];
    }

    /**
     * Create a TimeBlock from an ICS event
     */
    private function createTimeBlockFromEvent(array $event, int $childId): ?TimeBlock
    {
        if (! isset($event['start']) || ! $event['start'] instanceof Carbon) {
            return null;
        }

        $startTime = $event['start']->format('H:i');
        $endTime = isset($event['end']) && $event['end'] instanceof Carbon
            ? $event['end']->format('H:i')
            : $event['start']->addHour()->format('H:i'); // Default to 1 hour if no end time

        return new TimeBlock([
            'child_id' => $childId,
            'day_of_week' => $event['start']->dayOfWeekIso,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'label' => $event['summary'] ?? 'Imported Event',
            'is_imported' => true,
            'commitment_type' => 'fixed', // Imported events are usually fixed commitments
            'source_uid' => $event['uid'] ?? null,
        ]);
    }
}
