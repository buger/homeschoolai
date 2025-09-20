<?php

namespace App\Services;

use App\Models\Topic;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TopicMaterialService
{
    /**
     * Allowed file types for uploads
     */
    public const ALLOWED_FILE_TYPES = [
        'pdf',
        'doc',
        'docx',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'mp4',
        'mp3',
        'wav',
    ];

    /**
     * Maximum file size in bytes (10MB)
     */
    public const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Allowed video domains
     */
    public const ALLOWED_VIDEO_DOMAINS = [
        'youtube.com',
        'youtu.be',
        'vimeo.com',
        'khanacademy.org',
    ];

    /**
     * Handle file upload for a topic
     */
    public function uploadFile(Topic $topic, UploadedFile $file, ?string $title = null): array
    {
        // Validate file
        $this->validateFile($file);

        // Generate unique filename
        $filename = $this->generateUniqueFilename($topic, $file);

        // Store file
        $path = $file->storeAs("topic-materials/{$topic->id}", $filename, 'public');

        // Return file data array
        return [
            'name' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'title' => $title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'path' => $path,
            'url' => Storage::url($path),
            'type' => strtolower($file->getClientOriginalExtension()),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Delete a file from storage
     */
    public function deleteFile(array $fileData): bool
    {
        if (isset($fileData['path']) && Storage::disk('public')->exists($fileData['path'])) {
            return Storage::disk('public')->delete($fileData['path']);
        }

        return false;
    }

    /**
     * Validate uploaded file
     */
    public function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('File size exceeds maximum allowed size of '.(self::MAX_FILE_SIZE / 1024 / 1024).'MB');
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, self::ALLOWED_FILE_TYPES)) {
            throw new \InvalidArgumentException('File type not allowed. Allowed types: '.implode(', ', self::ALLOWED_FILE_TYPES));
        }

        // Additional security check
        $mimeType = $file->getMimeType();
        if (! $this->isValidMimeType($extension, $mimeType)) {
            throw new \InvalidArgumentException('File content does not match file extension');
        }
    }

    /**
     * Process video URL and extract metadata
     */
    public function processVideoUrl(string $url, ?string $title = null, ?string $description = null): array
    {
        // Validate URL
        $this->validateVideoUrl($url);

        // Parse video metadata
        $videoData = $this->parseVideoUrl($url);

        if (! $videoData) {
            throw new \InvalidArgumentException('Unable to parse video URL. Please check the URL format.');
        }

        return [
            'title' => $title ?: 'Video',
            'url' => $url,
            'description' => $description,
            'type' => $videoData['type'],
            'video_id' => $videoData['id'],
            'thumbnail' => $videoData['thumbnail'],
            'duration' => null, // Could be fetched from API
            'added_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Process regular link
     */
    public function processLink(string $url, ?string $title = null, ?string $description = null): array
    {
        // Basic URL validation
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL format');
        }

        // Ensure HTTPS for security
        if (parse_url($url, PHP_URL_SCHEME) === 'http') {
            $url = str_replace('http://', 'https://', $url);
        }

        return [
            'title' => $title ?: 'Link',
            'url' => $url,
            'description' => $description,
            'domain' => parse_url($url, PHP_URL_HOST),
            'added_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Clean up all files for a topic when it's deleted
     */
    public function cleanupTopicFiles(Topic $topic): bool
    {
        $directory = "topic-materials/{$topic->id}";

        if (Storage::disk('public')->exists($directory)) {
            return Storage::disk('public')->deleteDirectory($directory);
        }

        return true;
    }

    /**
     * Get file size in human readable format
     */
    public function getHumanReadableSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(Topic $topic, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Sanitize filename
        $basename = Str::slug($basename);
        $basename = substr($basename, 0, 50); // Limit length

        // Ensure uniqueness
        $filename = $basename.'.'.$extension;
        $counter = 1;

        while (Storage::disk('public')->exists("topic-materials/{$topic->id}/$filename")) {
            $filename = $basename.'-'.$counter.'.'.$extension;
            $counter++;
        }

        return $filename;
    }

    /**
     * Validate MIME type matches extension
     */
    private function isValidMimeType(string $extension, ?string $mimeType): bool
    {
        $validMimeTypes = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'mp4' => ['video/mp4'],
            'mp3' => ['audio/mpeg', 'audio/mp3'],
            'wav' => ['audio/wav', 'audio/wave'],
        ];

        if (! isset($validMimeTypes[$extension])) {
            return false;
        }

        return in_array($mimeType, $validMimeTypes[$extension]);
    }

    /**
     * Validate video URL domain
     */
    private function validateVideoUrl(string $url): void
    {
        $domain = parse_url($url, PHP_URL_HOST);
        $domain = strtolower($domain);

        // Remove www prefix for checking
        $domain = preg_replace('/^www\./', '', $domain);

        $allowed = false;
        foreach (self::ALLOWED_VIDEO_DOMAINS as $allowedDomain) {
            if (str_contains($domain, $allowedDomain)) {
                $allowed = true;
                break;
            }
        }

        if (! $allowed) {
            throw new \InvalidArgumentException('Video URL must be from an allowed domain: '.implode(', ', self::ALLOWED_VIDEO_DOMAINS));
        }
    }

    /**
     * Parse video URL to extract metadata
     */
    private function parseVideoUrl(string $url): ?array
    {
        $domain = parse_url($url, PHP_URL_HOST);
        $domain = strtolower(preg_replace('/^www\./', '', $domain));

        // Basic video metadata based on domain
        $videoData = [
            'url' => $url,
            'type' => 'video',
            'platform' => $this->getPlatformFromDomain($domain),
        ];

        return $videoData;
    }

    /**
     * Get platform name from domain
     */
    private function getPlatformFromDomain(string $domain): string
    {
        if (str_contains($domain, 'youtube.com') || str_contains($domain, 'youtu.be')) {
            return 'youtube';
        }
        if (str_contains($domain, 'vimeo.com')) {
            return 'vimeo';
        }
        if (str_contains($domain, 'khanacademy.org')) {
            return 'khan_academy';
        }

        return 'unknown';
    }
}
