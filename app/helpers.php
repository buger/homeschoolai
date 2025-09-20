<?php

use App\Models\User;
use App\Services\DateTimeFormatterService;

if (! function_exists('formatDate')) {
    /**
     * Format a date according to user's preferences
     */
    function formatDate($date, ?User $user = null): string
    {
        return app(DateTimeFormatterService::class)->formatDate($date, $user);
    }
}

if (! function_exists('formatTime')) {
    /**
     * Format a time according to user's preferences
     */
    function formatTime($time, ?User $user = null): string
    {
        return app(DateTimeFormatterService::class)->formatTime($time, $user);
    }
}

if (! function_exists('formatDateTime')) {
    /**
     * Format a datetime according to user's preferences
     */
    function formatDateTime($datetime, ?User $user = null): string
    {
        return app(DateTimeFormatterService::class)->formatDateTime($datetime, $user);
    }
}

if (! function_exists('formatRelativeDate')) {
    /**
     * Format date with relative time (e.g., "Today", "Yesterday", "3 days ago")
     */
    function formatRelativeDate($date, ?User $user = null): string
    {
        return app(DateTimeFormatterService::class)->formatRelativeDate($date, $user);
    }
}

if (! function_exists('formatDateRange')) {
    /**
     * Format a date range according to user preferences
     */
    function formatDateRange($startDate, $endDate, ?User $user = null): string
    {
        return app(DateTimeFormatterService::class)->formatDateRange($startDate, $endDate, $user);
    }
}

if (! function_exists('userWeekStart')) {
    /**
     * Get start of week for a given date according to user preference
     */
    function userWeekStart($date = null, ?User $user = null): \Carbon\Carbon
    {
        return app(DateTimeFormatterService::class)->getWeekStart($date, $user);
    }
}

if (! function_exists('userWeekEnd')) {
    /**
     * Get end of week for a given date according to user preference
     */
    function userWeekEnd($date = null, ?User $user = null): \Carbon\Carbon
    {
        return app(DateTimeFormatterService::class)->getWeekEnd($date, $user);
    }
}

if (! function_exists('userWeekDays')) {
    /**
     * Get an array of week days starting from user's preferred week start
     */
    function userWeekDays(?User $user = null, bool $short = false): array
    {
        return app(DateTimeFormatterService::class)->getWeekDays($user, $short);
    }
}

if (! function_exists('userFormatOptions')) {
    /**
     * Get user's format preferences for JavaScript
     */
    function userFormatOptions(?User $user = null): array
    {
        return app(DateTimeFormatterService::class)->getJavaScriptFormatOptions($user);
    }
}

if (! function_exists('userTimezone')) {
    /**
     * Create a Carbon instance with user's timezone
     */
    function userTimezone($date = null, ?User $user = null): \Carbon\Carbon
    {
        return app(DateTimeFormatterService::class)->createInUserTimezone($date, $user);
    }
}

if (! function_exists('toUserTimezone')) {
    /**
     * Convert a date to user's timezone
     */
    function toUserTimezone($date, ?User $user = null): \Carbon\Carbon
    {
        return app(DateTimeFormatterService::class)->toUserTimezone($date, $user);
    }
}
