<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DateTimeFormatterService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private DateTimeFormatterService $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = app(DateTimeFormatterService::class);

        $this->user = User::factory()->create([
            'locale' => 'en',
            'timezone' => 'America/New_York',
            'region_format' => 'us',
            'time_format' => '12h',
            'week_start' => 'sunday',
            'date_format_type' => 'us',
            'date_format' => 'm/d/Y',
        ]);

        $this->actingAs($this->user);
    }

    /**
     * Test calendar respects Sunday week start preference
     */
    public function test_calendar_respects_sunday_week_start(): void
    {
        $this->user->update(['week_start' => 'sunday']);

        $date = Carbon::create(2024, 3, 13); // Wednesday

        $weekStart = $this->formatter->getWeekStart($date, $this->user);
        $weekEnd = $this->formatter->getWeekEnd($date, $this->user);

        $this->assertEquals(Carbon::SUNDAY, $weekStart->dayOfWeek);
        $this->assertEquals(Carbon::SATURDAY, $weekEnd->dayOfWeek);

        // Week should be March 10 (Sun) to March 16 (Sat)
        $this->assertEquals(10, $weekStart->day);
        $this->assertEquals(16, $weekEnd->day);
    }

    /**
     * Test calendar respects Monday week start preference
     */
    public function test_calendar_respects_monday_week_start(): void
    {
        $this->user->update(['week_start' => 'monday']);

        $date = Carbon::create(2024, 3, 13); // Wednesday

        $weekStart = $this->formatter->getWeekStart($date, $this->user);
        $weekEnd = $this->formatter->getWeekEnd($date, $this->user);

        $this->assertEquals(Carbon::MONDAY, $weekStart->dayOfWeek);
        $this->assertEquals(Carbon::SUNDAY, $weekEnd->dayOfWeek);

        // Week should be March 11 (Mon) to March 17 (Sun)
        $this->assertEquals(11, $weekStart->day);
        $this->assertEquals(17, $weekEnd->day);
    }

    /**
     * Test getWeekDays returns correct order for calendar display
     */
    public function test_get_week_days_for_calendar_display_sunday_start(): void
    {
        $this->user->update(['week_start' => 'sunday']);

        $weekDays = $this->formatter->getWeekDays($this->user);

        $expected = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $this->assertEquals($expected, $weekDays);

        // Test short names for calendar headers
        $shortDays = $this->formatter->getWeekDays($this->user, true);
        $expectedShort = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $this->assertEquals($expectedShort, $shortDays);
    }

    /**
     * Test getWeekDays returns correct order for calendar display with Monday start
     */
    public function test_get_week_days_for_calendar_display_monday_start(): void
    {
        $this->user->update(['week_start' => 'monday']);

        $weekDays = $this->formatter->getWeekDays($this->user);

        $expected = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $this->assertEquals($expected, $weekDays);

        // Test short names for calendar headers
        $shortDays = $this->formatter->getWeekDays($this->user, true);
        $expectedShort = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $this->assertEquals($expectedShort, $shortDays);
    }

    /**
     * Test calendar navigation maintains week start preference
     */
    public function test_calendar_navigation_maintains_week_start_preference(): void
    {
        $this->user->update(['week_start' => 'monday']);

        // Start with first week of March 2024
        $firstWeek = Carbon::create(2024, 3, 4); // Monday, March 4

        $weekStart = $this->formatter->getWeekStart($firstWeek, $this->user);
        $this->assertEquals(Carbon::MONDAY, $weekStart->dayOfWeek);
        $this->assertEquals(4, $weekStart->day);

        // Navigate to next week
        $nextWeek = $weekStart->copy()->addWeek();
        $nextWeekStart = $this->formatter->getWeekStart($nextWeek, $this->user);
        $this->assertEquals(Carbon::MONDAY, $nextWeekStart->dayOfWeek);
        $this->assertEquals(11, $nextWeekStart->day);

        // Navigate to previous week
        $prevWeek = $weekStart->copy()->subWeek();
        $prevWeekStart = $this->formatter->getWeekStart($prevWeek, $this->user);
        $this->assertEquals(Carbon::MONDAY, $prevWeekStart->dayOfWeek);
        $this->assertEquals(26, $prevWeekStart->day); // February 26
    }

    /**
     * Test calendar date formatting respects user preferences
     */
    public function test_calendar_date_formatting_respects_user_preferences(): void
    {
        $date = Carbon::create(2024, 3, 15);

        // Test US format
        $this->user->update([
            'region_format' => 'us',
            'date_format_type' => 'us',
        ]);

        $formatted = $this->formatter->formatDate($date, $this->user);
        $this->assertEquals('3/15/2024', $formatted);

        // Test EU format
        $this->user->update([
            'region_format' => 'eu',
            'date_format_type' => 'eu',
        ]);

        $formatted = $this->formatter->formatDate($date, $this->user);
        $this->assertEquals('15.3.2024', $formatted);

        // Test ISO format
        $this->user->update([
            'region_format' => 'custom',
            'date_format_type' => 'iso',
        ]);

        $formatted = $this->formatter->formatDate($date, $this->user);
        $this->assertEquals('2024-03-15', $formatted);
    }

    /**
     * Test calendar time formatting respects user preferences
     */
    public function test_calendar_time_formatting_respects_user_preferences(): void
    {
        $time = Carbon::create(2024, 3, 15, 14, 30);

        // Test 12-hour format
        $this->user->update(['time_format' => '12h']);

        $formatted = $this->formatter->formatTime($time, $this->user);
        $this->assertEquals('2:30 PM', $formatted);

        // Test 24-hour format
        $this->user->update(['time_format' => '24h']);

        $formatted = $this->formatter->formatTime($time, $this->user);
        $this->assertEquals('14:30', $formatted);
    }

    /**
     * Test calendar datetime formatting for events
     */
    public function test_calendar_datetime_formatting_for_events(): void
    {
        $datetime = Carbon::create(2024, 3, 15, 14, 30);

        // US format with 12-hour time
        $this->user->update([
            'region_format' => 'us',
            'date_format_type' => 'us',
            'time_format' => '12h',
        ]);

        $formatted = $this->formatter->formatDateTime($datetime, $this->user);
        $this->assertEquals('3/15/2024 2:30 PM', $formatted);

        // EU format with 24-hour time
        $this->user->update([
            'region_format' => 'eu',
            'date_format_type' => 'eu',
            'time_format' => '24h',
        ]);

        $formatted = $this->formatter->formatDateTime($datetime, $this->user);
        $this->assertEquals('15.3.2024 14:30', $formatted);
    }

    /**
     * Test calendar event time range formatting
     */
    public function test_calendar_event_time_range_formatting(): void
    {
        $start = Carbon::create(2024, 3, 15, 9, 0);
        $end = Carbon::create(2024, 3, 15, 10, 30);

        // Same day event with 12-hour format
        $this->user->update(['time_format' => '12h']);

        $formatted = $this->formatter->formatDateRange($start, $end, $this->user);
        $this->assertEquals('3/15/2024 9:00 AM - 10:30 AM', $formatted);

        // Same day event with 24-hour format
        $this->user->update(['time_format' => '24h']);

        $formatted = $this->formatter->formatDateRange($start, $end, $this->user);
        $this->assertEquals('3/15/2024 09:00 - 10:30', $formatted);

        // Multi-day event
        $endNextDay = Carbon::create(2024, 3, 16, 10, 30);
        $formatted = $this->formatter->formatDateRange($start, $endNextDay, $this->user);
        $this->assertStringContains('3/15/2024', $formatted);
        $this->assertStringContains('3/16/2024', $formatted);
    }

    /**
     * Test calendar with different timezone preferences
     */
    public function test_calendar_with_different_timezone_preferences(): void
    {
        // Test with New York timezone
        $this->user->update(['timezone' => 'America/New_York']);

        $utcDate = Carbon::create(2024, 3, 15, 20, 0, 0, 'UTC'); // 8 PM UTC

        $userTimezone = $this->formatter->toUserTimezone($utcDate, $this->user);
        $this->assertEquals('America/New_York', $userTimezone->timezone->getName());

        // Should be earlier in the day in NY (4 PM or 3 PM depending on DST)
        $this->assertTrue($userTimezone->hour < 20);

        // Test with Tokyo timezone
        $this->user->update(['timezone' => 'Asia/Tokyo']);

        $userTimezone = $this->formatter->toUserTimezone($utcDate, $this->user);
        $this->assertEquals('Asia/Tokyo', $userTimezone->timezone->getName());

        // Should be next day in Tokyo (5 AM on March 16)
        $this->assertTrue($userTimezone->hour >= 0);
        $this->assertTrue($userTimezone->day >= 15);
    }

    /**
     * Test JavaScript format options for calendar integration
     */
    public function test_javascript_format_options_for_calendar(): void
    {
        // Test US user preferences
        $this->user->update([
            'region_format' => 'us',
            'date_format_type' => 'us',
            'time_format' => '12h',
            'week_start' => 'sunday',
        ]);

        $jsOptions = $this->formatter->getJavaScriptFormatOptions($this->user);

        $this->assertEquals('m/d/Y', $jsOptions['dateFormat']);
        $this->assertEquals('g:i A', $jsOptions['timeFormat']);
        $this->assertEquals('m/d/Y g:i A', $jsOptions['dateTimeFormat']);
        $this->assertFalse($jsOptions['weekStartsMonday']);
        $this->assertFalse($jsOptions['use24Hour']);
        $this->assertEquals('us', $jsOptions['regionFormat']);

        // Test EU user preferences
        $this->user->update([
            'region_format' => 'eu',
            'date_format_type' => 'eu',
            'time_format' => '24h',
            'week_start' => 'monday',
        ]);

        $jsOptions = $this->formatter->getJavaScriptFormatOptions($this->user);

        $this->assertEquals('d.m.Y', $jsOptions['dateFormat']);
        $this->assertEquals('H:i', $jsOptions['timeFormat']);
        $this->assertEquals('d.m.Y H:i', $jsOptions['dateTimeFormat']);
        $this->assertTrue($jsOptions['weekStartsMonday']);
        $this->assertTrue($jsOptions['use24Hour']);
        $this->assertEquals('eu', $jsOptions['regionFormat']);
    }

    /**
     * Test calendar edge cases with week boundaries
     */
    public function test_calendar_edge_cases_with_week_boundaries(): void
    {
        // Test end of month with Sunday start
        $this->user->update(['week_start' => 'sunday']);

        $endOfMonth = Carbon::create(2024, 2, 29); // Last day of February 2024 (leap year)
        $weekStart = $this->formatter->getWeekStart($endOfMonth, $this->user);
        $weekEnd = $this->formatter->getWeekEnd($endOfMonth, $this->user);

        $this->assertEquals(Carbon::SUNDAY, $weekStart->dayOfWeek);
        $this->assertEquals(Carbon::SATURDAY, $weekEnd->dayOfWeek);

        // Week should span from February 25 (Sun) to March 2 (Sat)
        $this->assertEquals(25, $weekStart->day);
        $this->assertEquals(2, $weekEnd->month); // March

        // Test with Monday start
        $this->user->update(['week_start' => 'monday']);

        $weekStart = $this->formatter->getWeekStart($endOfMonth, $this->user);
        $weekEnd = $this->formatter->getWeekEnd($endOfMonth, $this->user);

        $this->assertEquals(Carbon::MONDAY, $weekStart->dayOfWeek);
        $this->assertEquals(Carbon::SUNDAY, $weekEnd->dayOfWeek);

        // Week should span from February 26 (Mon) to March 3 (Sun)
        $this->assertEquals(26, $weekStart->day);
        $this->assertEquals(3, $weekEnd->day);
        $this->assertEquals(3, $weekEnd->month); // March
    }

    /**
     * Test calendar with different custom format combinations
     */
    public function test_calendar_with_custom_format_combinations(): void
    {
        $this->user->update([
            'region_format' => 'custom',
            'date_format' => 'Y/m/d',
            'time_format' => '24h',
            'week_start' => 'monday',
        ]);

        $date = Carbon::create(2024, 3, 15, 14, 30);

        // Test date formatting
        $formatted = $this->formatter->formatDate($date, $this->user);
        $this->assertEquals('2024/03/15', $formatted);

        // Test time formatting
        $formatted = $this->formatter->formatTime($date, $this->user);
        $this->assertEquals('14:30', $formatted);

        // Test datetime formatting
        $formatted = $this->formatter->formatDateTime($date, $this->user);
        $this->assertEquals('2024/03/15 14:30', $formatted);

        // Test week start
        $weekStart = $this->formatter->getWeekStart($date, $this->user);
        $this->assertEquals(Carbon::MONDAY, $weekStart->dayOfWeek);
    }

    /**
     * Test calendar year transitions with week preferences
     */
    public function test_calendar_year_transitions_with_week_preferences(): void
    {
        // Test New Year's Day 2024 (Monday) with Sunday start
        $this->user->update(['week_start' => 'sunday']);

        $newYear = Carbon::create(2024, 1, 1); // Monday, January 1, 2024
        $weekStart = $this->formatter->getWeekStart($newYear, $this->user);
        $weekEnd = $this->formatter->getWeekEnd($newYear, $this->user);

        $this->assertEquals(Carbon::SUNDAY, $weekStart->dayOfWeek);
        $this->assertEquals(Carbon::SATURDAY, $weekEnd->dayOfWeek);

        // Week should span from December 31, 2023 (Sun) to January 6, 2024 (Sat)
        $this->assertEquals(2023, $weekStart->year);
        $this->assertEquals(31, $weekStart->day);
        $this->assertEquals(6, $weekEnd->day);

        // Test with Monday start
        $this->user->update(['week_start' => 'monday']);

        $weekStart = $this->formatter->getWeekStart($newYear, $this->user);
        $weekEnd = $this->formatter->getWeekEnd($newYear, $this->user);

        // Since Jan 1, 2024 is a Monday, the week should start on Jan 1
        $this->assertEquals(2024, $weekStart->year);
        $this->assertEquals(1, $weekStart->day);
        $this->assertEquals(7, $weekEnd->day); // January 7
    }
}
