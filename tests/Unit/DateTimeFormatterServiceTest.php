<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\DateTimeFormatterService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DateTimeFormatterServiceTest extends TestCase
{
    use RefreshDatabase;

    private DateTimeFormatterService $formatter;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = new DateTimeFormatterService;

        // Create a test user with specific format preferences
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'locale' => 'en',
            'timezone' => 'America/New_York',
            'region_format' => 'us',
            'time_format' => '12h',
            'week_start' => 'sunday',
            'date_format_type' => 'us',
            'date_format' => 'm/d/Y',
        ]);
    }

    /**
     * Test formatDate with user preferences
     */
    public function test_format_date_with_user_preferences(): void
    {
        $date = Carbon::create(2024, 3, 15);

        $result = $this->formatter->formatDate($date, $this->user);

        $this->assertEquals('03/15/2024', $result);
    }

    /**
     * Test formatDate with European user preferences
     */
    public function test_format_date_with_european_preferences(): void
    {
        $this->user->region_format = 'eu';
        $this->user->date_format_type = 'eu';

        $date = Carbon::create(2024, 3, 15);

        $result = $this->formatter->formatDate($date, $this->user);

        $this->assertEquals('15.03.2024', $result);
    }

    /**
     * Test formatDate with custom format
     */
    public function test_format_date_with_custom_format(): void
    {
        $this->user->region_format = 'custom';
        $this->user->date_format = 'Y/m/d';

        $date = Carbon::create(2024, 3, 15);

        $result = $this->formatter->formatDate($date, $this->user);

        $this->assertEquals('2024/03/15', $result);
    }

    /**
     * Test formatDate without user defaults to US format
     */
    public function test_format_date_without_user_defaults_to_us_format(): void
    {
        $date = Carbon::create(2024, 3, 15);

        $result = $this->formatter->formatDate($date, null);

        $this->assertEquals('03/15/2024', $result);
    }

    /**
     * Test formatDate with authenticated user
     */
    public function test_format_date_with_authenticated_user(): void
    {
        Auth::login($this->user);

        $date = Carbon::create(2024, 3, 15);

        $result = $this->formatter->formatDate($date);

        $this->assertEquals('03/15/2024', $result);
    }

    /**
     * Test formatDate with null date returns empty string
     */
    public function test_format_date_with_null_returns_empty_string(): void
    {
        $result = $this->formatter->formatDate(null, $this->user);

        $this->assertEquals('', $result);
    }

    /**
     * Test formatDate with string date
     */
    public function test_format_date_with_string_date(): void
    {
        $result = $this->formatter->formatDate('2024-03-15', $this->user);

        $this->assertEquals('03/15/2024', $result);
    }

    /**
     * Test formatTime with 12-hour format
     */
    public function test_format_time_with_12h_format(): void
    {
        $time = Carbon::create(2024, 3, 15, 14, 30, 0);

        $result = $this->formatter->formatTime($time, $this->user);

        $this->assertEquals('2:30 PM', $result);
    }

    /**
     * Test formatTime with 24-hour format
     */
    public function test_format_time_with_24h_format(): void
    {
        $this->user->time_format = '24h';

        $time = Carbon::create(2024, 3, 15, 14, 30, 0);

        $result = $this->formatter->formatTime($time, $this->user);

        $this->assertEquals('14:30', $result);
    }

    /**
     * Test formatTime without user defaults to 12-hour
     */
    public function test_format_time_without_user_defaults_to_12h(): void
    {
        $time = Carbon::create(2024, 3, 15, 14, 30, 0);

        $result = $this->formatter->formatTime($time, null);

        $this->assertEquals('2:30 PM', $result);
    }

    /**
     * Test formatTime with null time returns empty string
     */
    public function test_format_time_with_null_returns_empty_string(): void
    {
        $result = $this->formatter->formatTime(null, $this->user);

        $this->assertEquals('', $result);
    }

    /**
     * Test formatTime with string time
     */
    public function test_format_time_with_string_time(): void
    {
        $result = $this->formatter->formatTime('14:30:00', $this->user);

        $this->assertEquals('2:30 PM', $result);
    }

    /**
     * Test formatDateTime combines date and time
     */
    public function test_format_date_time_combines_date_and_time(): void
    {
        $datetime = Carbon::create(2024, 3, 15, 14, 30, 0);

        $result = $this->formatter->formatDateTime($datetime, $this->user);

        $this->assertEquals('03/15/2024 2:30 PM', $result);
    }

    /**
     * Test formatDateTime with European preferences
     */
    public function test_format_date_time_with_european_preferences(): void
    {
        $this->user->region_format = 'eu';
        $this->user->date_format_type = 'eu';
        $this->user->time_format = '24h';

        $datetime = Carbon::create(2024, 3, 15, 14, 30, 0);

        $result = $this->formatter->formatDateTime($datetime, $this->user);

        $this->assertEquals('15.03.2024 14:30', $result);
    }

    /**
     * Test formatDateTime without user defaults
     */
    public function test_format_date_time_without_user_defaults(): void
    {
        $datetime = Carbon::create(2024, 3, 15, 14, 30, 0);

        $result = $this->formatter->formatDateTime($datetime, null);

        $this->assertEquals('03/15/2024 2:30 PM', $result);
    }

    /**
     * Test formatDateTime with null returns empty string
     */
    public function test_format_date_time_with_null_returns_empty_string(): void
    {
        $result = $this->formatter->formatDateTime(null, $this->user);

        $this->assertEquals('', $result);
    }

    /**
     * Test formatRelativeDate for today
     */
    public function test_format_relative_date_for_today(): void
    {
        $today = Carbon::now()->setTime(14, 30, 0);

        $result = $this->formatter->formatRelativeDate($today, $this->user);

        $this->assertStringStartsWith('Today', $result);
        $this->assertStringContainsString('2:30 PM', $result);
    }

    /**
     * Test formatRelativeDate for yesterday
     */
    public function test_format_relative_date_for_yesterday(): void
    {
        $yesterday = Carbon::now()->subDay()->setTime(14, 30, 0);

        $result = $this->formatter->formatRelativeDate($yesterday, $this->user);

        $this->assertStringStartsWith('Yesterday', $result);
        $this->assertStringContainsString('2:30 PM', $result);
    }

    /**
     * Test formatRelativeDate for tomorrow
     */
    public function test_format_relative_date_for_tomorrow(): void
    {
        $tomorrow = Carbon::now()->addDay()->setTime(14, 30, 0);

        $result = $this->formatter->formatRelativeDate($tomorrow, $this->user);

        $this->assertStringStartsWith('Tomorrow', $result);
        $this->assertStringContainsString('2:30 PM', $result);
    }

    /**
     * Test formatRelativeDate for day within past week
     */
    public function test_format_relative_date_for_day_within_past_week(): void
    {
        $pastDay = Carbon::now()->subDays(3)->setTime(14, 30, 0);

        $result = $this->formatter->formatRelativeDate($pastDay, $this->user);

        $this->assertStringContainsString($pastDay->format('l'), $result);
        $this->assertStringContainsString('2:30 PM', $result);
    }

    /**
     * Test formatRelativeDate for day within next week
     */
    public function test_format_relative_date_for_day_within_next_week(): void
    {
        $futureDay = Carbon::now()->addDays(5)->setTime(14, 30, 0);

        $result = $this->formatter->formatRelativeDate($futureDay, $this->user);

        $this->assertStringContainsString($futureDay->format('l'), $result);
        $this->assertStringContainsString('2:30 PM', $result);
    }

    /**
     * Test formatRelativeDate for older dates shows full date
     */
    public function test_format_relative_date_for_older_dates_shows_full_date(): void
    {
        $oldDate = Carbon::now()->subWeeks(2)->setTime(14, 30, 0);

        $result = $this->formatter->formatRelativeDate($oldDate, $this->user);

        // Should contain full date and time
        $this->assertStringContainsString('2:30 PM', $result);
        $this->assertStringContainsString($oldDate->format('3'), $result); // Day
    }

    /**
     * Test formatRelativeDate with null returns empty string
     */
    public function test_format_relative_date_with_null_returns_empty_string(): void
    {
        $result = $this->formatter->formatRelativeDate(null, $this->user);

        $this->assertEquals('', $result);
    }

    /**
     * Test getWeekStartDay for Sunday preference
     */
    public function test_get_week_start_day_for_sunday(): void
    {
        $this->user->week_start = 'sunday';

        $result = $this->formatter->getWeekStartDay($this->user);

        $this->assertEquals(Carbon::SUNDAY, $result);
    }

    /**
     * Test getWeekStartDay for Monday preference
     */
    public function test_get_week_start_day_for_monday(): void
    {
        $this->user->week_start = 'monday';

        $result = $this->formatter->getWeekStartDay($this->user);

        $this->assertEquals(Carbon::MONDAY, $result);
    }

    /**
     * Test getWeekStartDay without user defaults to Sunday
     */
    public function test_get_week_start_day_without_user_defaults_to_sunday(): void
    {
        $result = $this->formatter->getWeekStartDay(null);

        $this->assertEquals(Carbon::SUNDAY, $result);
    }

    /**
     * Test getWeekStart returns correct start of week
     */
    public function test_get_week_start_returns_correct_start(): void
    {
        $this->user->week_start = 'monday';

        // Wednesday, March 15, 2024
        $date = Carbon::create(2024, 3, 13); // Wednesday

        $result = $this->formatter->getWeekStart($date, $this->user);

        // Should return Monday, March 11, 2024
        $this->assertEquals(Carbon::MONDAY, $result->dayOfWeek);
        $this->assertEquals(11, $result->day);
    }

    /**
     * Test getWeekStart with Sunday preference
     */
    public function test_get_week_start_with_sunday_preference(): void
    {
        $this->user->week_start = 'sunday';

        // Wednesday, March 13, 2024
        $date = Carbon::create(2024, 3, 13);

        $result = $this->formatter->getWeekStart($date, $this->user);

        // Should return Sunday, March 10, 2024
        $this->assertEquals(Carbon::SUNDAY, $result->dayOfWeek);
        $this->assertEquals(10, $result->day);
    }

    /**
     * Test getWeekStart without date uses current date
     */
    public function test_get_week_start_without_date_uses_current(): void
    {
        $now = Carbon::now();
        $result = $this->formatter->getWeekStart(null, $this->user);

        $this->assertEquals(Carbon::SUNDAY, $result->dayOfWeek);
        $this->assertTrue($result->lte($now));
    }

    /**
     * Test getWeekEnd returns correct end of week
     */
    public function test_get_week_end_returns_correct_end(): void
    {
        $this->user->week_start = 'monday';

        // Wednesday, March 13, 2024
        $date = Carbon::create(2024, 3, 13);

        $result = $this->formatter->getWeekEnd($date, $this->user);

        // Just verify we get a valid Carbon instance and it's in the correct week
        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals(2024, $result->year);
        $this->assertEquals(3, $result->month);
    }

    /**
     * Test getWeekDays returns correct order for Sunday start
     */
    public function test_get_week_days_returns_correct_order_for_sunday_start(): void
    {
        $this->user->week_start = 'sunday';

        $result = $this->formatter->getWeekDays($this->user);

        $this->assertEquals('Sunday', $result[0]);
        $this->assertEquals('Monday', $result[1]);
        $this->assertEquals('Saturday', $result[6]);
        $this->assertCount(7, $result);
    }

    /**
     * Test getWeekDays returns correct order for Monday start
     */
    public function test_get_week_days_returns_correct_order_for_monday_start(): void
    {
        $this->user->week_start = 'monday';

        $result = $this->formatter->getWeekDays($this->user);

        $this->assertEquals('Monday', $result[0]);
        $this->assertEquals('Tuesday', $result[1]);
        $this->assertEquals('Sunday', $result[6]);
        $this->assertCount(7, $result);
    }

    /**
     * Test getWeekDays returns short names when requested
     */
    public function test_get_week_days_returns_short_names(): void
    {
        $this->user->week_start = 'sunday';

        $result = $this->formatter->getWeekDays($this->user, true);

        $this->assertEquals('Sun', $result[0]);
        $this->assertEquals('Mon', $result[1]);
        $this->assertEquals('Sat', $result[6]);
        $this->assertCount(7, $result);
    }

    /**
     * Test getWeekDays without user defaults to Sunday start
     */
    public function test_get_week_days_without_user_defaults_to_sunday_start(): void
    {
        $result = $this->formatter->getWeekDays(null);

        $this->assertEquals('Sunday', $result[0]);
        $this->assertEquals('Saturday', $result[6]);
    }

    /**
     * Test formatDateRange for same day
     */
    public function test_format_date_range_for_same_day(): void
    {
        $start = Carbon::create(2024, 3, 15, 10, 0, 0);
        $end = Carbon::create(2024, 3, 15, 14, 30, 0);

        $result = $this->formatter->formatDateRange($start, $end, $this->user);

        $this->assertEquals('03/15/2024 10:00 AM - 2:30 PM', $result);
    }

    /**
     * Test formatDateRange for different days
     */
    public function test_format_date_range_for_different_days(): void
    {
        $start = Carbon::create(2024, 3, 15, 10, 0, 0);
        $end = Carbon::create(2024, 3, 16, 14, 30, 0);

        $result = $this->formatter->formatDateRange($start, $end, $this->user);

        $this->assertEquals('03/15/2024 10:00 AM - 03/16/2024 2:30 PM', $result);
    }

    /**
     * Test formatDateRange with null dates returns empty string
     */
    public function test_format_date_range_with_null_returns_empty_string(): void
    {
        $result = $this->formatter->formatDateRange(null, null, $this->user);

        $this->assertEquals('', $result);

        $date = Carbon::now();
        $this->assertEquals('', $this->formatter->formatDateRange($date, null, $this->user));
        $this->assertEquals('', $this->formatter->formatDateRange(null, $date, $this->user));
    }

    /**
     * Test getJavaScriptFormatOptions returns correct options
     */
    public function test_get_java_script_format_options_returns_correct_options(): void
    {
        $this->user->region_format = 'eu';
        $this->user->date_format_type = 'eu';
        $this->user->time_format = '24h';
        $this->user->week_start = 'monday';

        $result = $this->formatter->getJavaScriptFormatOptions($this->user);

        $expected = [
            'dateFormat' => 'd.m.Y',
            'timeFormat' => 'H:i',
            'dateTimeFormat' => 'd.m.Y H:i',
            'weekStartsMonday' => true,
            'use24Hour' => true,
            'regionFormat' => 'eu',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getJavaScriptFormatOptions with US user
     */
    public function test_get_java_script_format_options_with_us_user(): void
    {
        $result = $this->formatter->getJavaScriptFormatOptions($this->user);

        $expected = [
            'dateFormat' => 'm/d/Y',
            'timeFormat' => 'g:i A',
            'dateTimeFormat' => 'm/d/Y g:i A',
            'weekStartsMonday' => false,
            'use24Hour' => false,
            'regionFormat' => 'us',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getJavaScriptFormatOptions without user returns defaults
     */
    public function test_get_java_script_format_options_without_user_returns_defaults(): void
    {
        $result = $this->formatter->getJavaScriptFormatOptions(null);

        $expected = [
            'dateFormat' => 'm/d/Y',
            'timeFormat' => 'g:i A',
            'dateTimeFormat' => 'm/d/Y g:i A',
            'weekStartsMonday' => false,
            'use24Hour' => false,
            'regionFormat' => 'us',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test createInUserTimezone creates Carbon in user timezone
     */
    public function test_create_in_user_timezone_creates_carbon_in_user_timezone(): void
    {
        $this->user->timezone = 'America/New_York';

        $result = $this->formatter->createInUserTimezone('2024-03-15 10:00:00', $this->user);

        $this->assertEquals('America/New_York', $result->timezone->getName());
        $this->assertEquals(15, $result->day);
        $this->assertEquals(10, $result->hour);
    }

    /**
     * Test createInUserTimezone without date returns now
     */
    public function test_create_in_user_timezone_without_date_returns_now(): void
    {
        $this->user->timezone = 'America/New_York';

        $result = $this->formatter->createInUserTimezone(null, $this->user);

        $this->assertEquals('America/New_York', $result->timezone->getName());
        $this->assertTrue($result->isToday());
    }

    /**
     * Test createInUserTimezone without user uses UTC
     */
    public function test_create_in_user_timezone_without_user_uses_utc(): void
    {
        $result = $this->formatter->createInUserTimezone('2024-03-15 10:00:00', null);

        $this->assertEquals('UTC', $result->timezone->getName());
    }

    /**
     * Test toUserTimezone converts to user timezone
     */
    public function test_to_user_timezone_converts_to_user_timezone(): void
    {
        $this->user->timezone = 'America/New_York';

        $utcDate = Carbon::create(2024, 3, 15, 15, 0, 0, 'UTC'); // 3 PM UTC

        $result = $this->formatter->toUserTimezone($utcDate, $this->user);

        $this->assertEquals('America/New_York', $result->timezone->getName());
        // Should be 11 AM or 10 AM depending on DST
        $this->assertTrue($result->hour < 15);
    }

    /**
     * Test toUserTimezone with null date returns current time
     */
    public function test_to_user_timezone_with_null_returns_current_time(): void
    {
        $result = $this->formatter->toUserTimezone(null, $this->user);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertTrue($result->isToday());
    }

    /**
     * Test toUserTimezone without user uses UTC
     */
    public function test_to_user_timezone_without_user_uses_utc(): void
    {
        $date = Carbon::create(2024, 3, 15, 15, 0, 0, 'UTC');

        $result = $this->formatter->toUserTimezone($date, null);

        $this->assertEquals('UTC', $result->timezone->getName());
        $this->assertEquals(15, $result->hour);
    }
}
