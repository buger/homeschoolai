<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DateTimeFormatterService
{
    /**
     * Get the current authenticated user or null
     */
    private function getCurrentUser(): ?User
    {
        return Auth::check() ? Auth::user() : null;
    }

    /**
     * Format a date according to user's preferences
     */
    public function formatDate($date, ?User $user = null): string
    {
        if (! $date) {
            return '';
        }

        $user = $user ?? $this->getCurrentUser();

        try {
            $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        } catch (\Exception $e) {
            return ''; // Return empty string for invalid dates
        }

        if ($user) {
            return $carbon->format($user->getDateFormatString());
        }

        // Default format if no user
        return $carbon->format('m/d/Y');
    }

    /**
     * Format a time according to user's preferences
     */
    public function formatTime($time, ?User $user = null): string
    {
        if (! $time) {
            return '';
        }

        $user = $user ?? $this->getCurrentUser();

        try {
            $carbon = $time instanceof Carbon ? $time : Carbon::parse($time);
        } catch (\Exception $e) {
            return ''; // Return empty string for invalid times
        }

        if ($user) {
            return $carbon->format($user->getTimeFormatString());
        }

        // Default format if no user
        return $carbon->format('g:i A');
    }

    /**
     * Format a datetime according to user's preferences
     */
    public function formatDateTime($datetime, ?User $user = null): string
    {
        if (! $datetime) {
            return '';
        }

        $user = $user ?? $this->getCurrentUser();

        try {
            $carbon = $datetime instanceof Carbon ? $datetime : Carbon::parse($datetime);
        } catch (\Exception $e) {
            return ''; // Return empty string for invalid datetimes
        }

        if ($user) {
            return $carbon->format($user->getDateTimeFormatString());
        }

        // Default format if no user
        return $carbon->format('m/d/Y g:i A');
    }

    /**
     * Format date for display with relative time (e.g., "Today", "Yesterday", "3 days ago")
     */
    public function formatRelativeDate($date, ?User $user = null): string
    {
        if (! $date) {
            return '';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        $now = Carbon::now();

        // If it's today, show relative time
        if ($carbon->isToday()) {
            return __('Today').' '.$this->formatTime($carbon, $user);
        }

        // If it's yesterday, show "Yesterday"
        if ($carbon->isYesterday()) {
            return __('Yesterday').' '.$this->formatTime($carbon, $user);
        }

        // If it's tomorrow, show "Tomorrow"
        if ($carbon->isTomorrow()) {
            return __('Tomorrow').' '.$this->formatTime($carbon, $user);
        }

        // If it's within the past week, show day name
        if ($carbon->diffInDays($now) <= 7 && $carbon->isPast()) {
            return $carbon->format('l').' '.$this->formatTime($carbon, $user);
        }

        // If it's within the next week, show day name
        if ($carbon->diffInDays($now) <= 7 && $carbon->isFuture()) {
            return $carbon->format('l').' '.$this->formatTime($carbon, $user);
        }

        // For older/future dates, show full date
        return $this->formatDateTime($carbon, $user);
    }

    /**
     * Get week start day based on user preference
     */
    public function getWeekStartDay(?User $user = null): int
    {
        $user = $user ?? $this->getCurrentUser();

        if ($user && $user->prefersMondayWeekStart()) {
            return Carbon::MONDAY;
        }

        return Carbon::SUNDAY;
    }

    /**
     * Get start of week for a given date according to user preference
     */
    public function getWeekStart($date = null, ?User $user = null): Carbon
    {
        $carbon = $date ? Carbon::parse($date) : Carbon::now();
        $weekStartDay = $this->getWeekStartDay($user);

        return $carbon->startOfWeek($weekStartDay);
    }

    /**
     * Get end of week for a given date according to user preference
     */
    public function getWeekEnd($date = null, ?User $user = null): Carbon
    {
        $carbon = $date ? Carbon::parse($date) : Carbon::now();
        $weekStartDay = $this->getWeekStartDay($user);

        // Get week start and add 6 days to get the proper week end
        return $carbon->startOfWeek($weekStartDay)->addDays(6);
    }

    /**
     * Get an array of week days starting from user's preferred week start
     */
    public function getWeekDays(?User $user = null, bool $short = false): array
    {
        $user = $user ?? $this->getCurrentUser();
        $startsMondayFirst = $user && $user->prefersMondayWeekStart();

        if ($short) {
            $days = $startsMondayFirst
                ? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
                : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        } else {
            $days = $startsMondayFirst
                ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
                : ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        }

        // Translate day names
        return array_map(function ($day) {
            return __($day);
        }, $days);
    }

    /**
     * Format a date range according to user preferences
     */
    public function formatDateRange($startDate, $endDate, ?User $user = null): string
    {
        if (! $startDate || ! $endDate) {
            return '';
        }

        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        // If same day, show date once with time range
        if ($start->isSameDay($end)) {
            return $this->formatDate($start, $user).' '.
                   $this->formatTime($start, $user).' - '.
                   $this->formatTime($end, $user);
        }

        // Different days, show full date range
        return $this->formatDateTime($start, $user).' - '.$this->formatDateTime($end, $user);
    }

    /**
     * Get time format preferences for JavaScript
     */
    public function getJavaScriptFormatOptions(?User $user = null): array
    {
        $user = $user ?? $this->getCurrentUser();

        return [
            'dateFormat' => $user ? $user->getDateFormatString() : 'm/d/Y',
            'timeFormat' => $user ? $user->getTimeFormatString() : 'g:i A',
            'dateTimeFormat' => $user ? $user->getDateTimeFormatString() : 'm/d/Y g:i A',
            'weekStartsMonday' => $user ? $user->prefersMondayWeekStart() : false,
            'use24Hour' => $user ? ($user->time_format === '24h') : false,
            'regionFormat' => $user ? ($user->region_format ?? 'us') : 'us',
        ];
    }

    /**
     * Create a Carbon instance with user's timezone
     */
    public function createInUserTimezone($date = null, ?User $user = null): Carbon
    {
        $user = $user ?? $this->getCurrentUser();
        $timezone = $user ? ($user->timezone ?? 'UTC') : 'UTC';

        if ($date) {
            return Carbon::parse($date, $timezone);
        }

        return Carbon::now($timezone);
    }

    /**
     * Convert a date to user's timezone
     */
    public function toUserTimezone($date, ?User $user = null): Carbon
    {
        if (! $date) {
            return Carbon::now();
        }

        $user = $user ?? $this->getCurrentUser();
        $timezone = $user ? ($user->timezone ?? 'UTC') : 'UTC';
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $carbon->setTimezone($timezone);
    }
}
