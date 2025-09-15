<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DateTimeFormatterService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class FormatDisplayTest extends TestCase
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
    }

    /**
     * Test that helper functions are available and work correctly
     */
    public function test_helper_functions_are_available_and_work(): void
    {
        $this->actingAs($this->user);

        $date = Carbon::create(2024, 3, 15, 14, 30, 0);

        // Test formatDate helper
        $this->assertEquals('3/15/2024', formatDate($date));

        // Test formatTime helper
        $this->assertEquals('2:30 PM', formatTime($date));

        // Test formatDateTime helper
        $this->assertEquals('3/15/2024 2:30 PM', formatDateTime($date));

        // Test formatDateRange helper
        $endDate = Carbon::create(2024, 3, 15, 16, 0, 0);
        $expected = '3/15/2024 2:30 PM - 4:00 PM';
        $this->assertEquals($expected, formatDateRange($date, $endDate));
    }

    /**
     * Test that format helpers work without authenticated user
     */
    public function test_format_helpers_work_without_authenticated_user(): void
    {
        $date = Carbon::create(2024, 3, 15, 14, 30, 0);

        // Should use default US format when no user is authenticated
        $this->assertEquals('3/15/2024', formatDate($date));
        $this->assertEquals('2:30 PM', formatTime($date));
        $this->assertEquals('3/15/2024 2:30 PM', formatDateTime($date));
    }

    /**
     * Test format consistency across different locale settings
     */
    public function test_format_consistency_across_locale_settings(): void
    {
        $this->actingAs($this->user);

        $date = Carbon::create(2024, 3, 15, 14, 30, 0);

        // Test English locale with US format
        App::setLocale('en');
        $this->user->update([
            'locale' => 'en',
            'region_format' => 'us',
            'date_format_type' => 'us',
            'time_format' => '12h',
        ]);

        $this->assertEquals('3/15/2024', formatDate($date));
        $this->assertEquals('2:30 PM', formatTime($date));

        // Test Russian locale with EU format
        App::setLocale('ru');
        $this->user->update([
            'locale' => 'ru',
            'region_format' => 'eu',
            'date_format_type' => 'eu',
            'time_format' => '24h',
        ]);

        $this->assertEquals('15.3.2024', formatDate($date));
        $this->assertEquals('14:30', formatTime($date));
    }

    /**
     * Test relative date formatting for different scenarios
     */
    public function test_relative_date_formatting_for_different_scenarios(): void
    {
        $this->actingAs($this->user);

        // Mock current time for consistent testing
        Carbon::setTestNow(Carbon::create(2024, 3, 15, 12, 0, 0));

        // Test today
        $today = Carbon::now()->setTime(14, 30, 0);
        $formatted = $this->formatter->formatRelativeDate($today, $this->user);
        $this->assertStringStartsWith('Today', $formatted);
        $this->assertStringContains('2:30 PM', $formatted);

        // Test yesterday
        $yesterday = Carbon::now()->subDay()->setTime(14, 30, 0);
        $formatted = $this->formatter->formatRelativeDate($yesterday, $this->user);
        $this->assertStringStartsWith('Yesterday', $formatted);

        // Test tomorrow
        $tomorrow = Carbon::now()->addDay()->setTime(14, 30, 0);
        $formatted = $this->formatter->formatRelativeDate($tomorrow, $this->user);
        $this->assertStringStartsWith('Tomorrow', $formatted);

        // Test past day within week
        $pastDay = Carbon::now()->subDays(3)->setTime(14, 30, 0);
        $formatted = $this->formatter->formatRelativeDate($pastDay, $this->user);
        $this->assertStringContains($pastDay->format('l'), $formatted); // Day name

        // Test future day within week
        $futureDay = Carbon::now()->addDays(4)->setTime(14, 30, 0);
        $formatted = $this->formatter->formatRelativeDate($futureDay, $this->user);
        $this->assertStringContains($futureDay->format('l'), $formatted); // Day name

        // Test older date (should show full date)
        $oldDate = Carbon::now()->subWeeks(2)->setTime(14, 30, 0);
        $formatted = $this->formatter->formatRelativeDate($oldDate, $this->user);
        $this->assertStringContains('3/', $formatted); // Month part of date
        $this->assertStringContains('2024', $formatted); // Year part

        Carbon::setTestNow(); // Reset mock time
    }

    /**
     * Test format display with European preferences
     */
    public function test_format_display_with_european_preferences(): void
    {
        $this->user->update([
            'locale' => 'ru',
            'region_format' => 'eu',
            'date_format_type' => 'eu',
            'time_format' => '24h',
            'week_start' => 'monday',
        ]);

        $this->actingAs($this->user);

        $date = Carbon::create(2024, 3, 15, 14, 30, 0);

        // Test European date format
        $this->assertEquals('15.3.2024', formatDate($date));

        // Test 24-hour time format
        $this->assertEquals('14:30', formatTime($date));

        // Test combined datetime
        $this->assertEquals('15.3.2024 14:30', formatDateTime($date));

        // Test week starts on Monday
        $weekStart = $this->formatter->getWeekStart($date, $this->user);
        $this->assertEquals(Carbon::MONDAY, $weekStart->dayOfWeek);
    }

    /**
     * Test format display with custom preferences
     */
    public function test_format_display_with_custom_preferences(): void
    {
        $this->user->update([
            'region_format' => 'custom',
            'date_format' => 'Y-m-d',
            'time_format' => '24h',
            'week_start' => 'monday',
        ]);

        $this->actingAs($this->user);

        $date = Carbon::create(2024, 3, 15, 14, 30, 0);

        // Test custom date format (ISO)
        $this->assertEquals('2024-03-15', formatDate($date));

        // Test 24-hour time format
        $this->assertEquals('14:30', formatTime($date));

        // Test combined datetime
        $this->assertEquals('2024-03-15 14:30', formatDateTime($date));

        // Test Monday week start
        $weekStart = $this->formatter->getWeekStart($date, $this->user);
        $this->assertEquals(Carbon::MONDAY, $weekStart->dayOfWeek);
    }

    /**
     * Test timezone conversion for display
     */
    public function test_timezone_conversion_for_display(): void
    {
        $this->actingAs($this->user);

        // Create UTC time
        $utcTime = Carbon::create(2024, 3, 15, 20, 0, 0, 'UTC');

        // Test with New York timezone (should be earlier)
        $this->user->update(['timezone' => 'America/New_York']);
        $userTime = $this->formatter->toUserTimezone($utcTime, $this->user);
        $this->assertTrue($userTime->hour < 20); // Should be earlier in NY

        $formatted = $this->formatter->formatDateTime($userTime, $this->user);
        $this->assertStringContains('3/15/2024', $formatted);

        // Test with Tokyo timezone (should be next day)
        $this->user->update(['timezone' => 'Asia/Tokyo']);
        $userTime = $this->formatter->toUserTimezone($utcTime, $this->user);
        $this->assertTrue($userTime->day >= 16 || ($userTime->day == 15 && $userTime->hour > 20));

        $formatted = $this->formatter->formatDateTime($userTime, $this->user);
        $this->assertIsString($formatted);
    }

    /**
     * Test JavaScript format options integration
     */
    public function test_javascript_format_options_integration(): void
    {
        $this->actingAs($this->user);

        // Test US format options
        $options = $this->formatter->getJavaScriptFormatOptions($this->user);

        $this->assertArrayHasKey('dateFormat', $options);
        $this->assertArrayHasKey('timeFormat', $options);
        $this->assertArrayHasKey('dateTimeFormat', $options);
        $this->assertArrayHasKey('weekStartsMonday', $options);
        $this->assertArrayHasKey('use24Hour', $options);
        $this->assertArrayHasKey('regionFormat', $options);

        $this->assertEquals('m/d/Y', $options['dateFormat']);
        $this->assertEquals('g:i A', $options['timeFormat']);
        $this->assertEquals('m/d/Y g:i A', $options['dateTimeFormat']);
        $this->assertFalse($options['weekStartsMonday']);
        $this->assertFalse($options['use24Hour']);
        $this->assertEquals('us', $options['regionFormat']);

        // Test EU format options
        $this->user->update([
            'region_format' => 'eu',
            'date_format_type' => 'eu',
            'time_format' => '24h',
            'week_start' => 'monday',
        ]);

        $options = $this->formatter->getJavaScriptFormatOptions($this->user);

        $this->assertEquals('d.m.Y', $options['dateFormat']);
        $this->assertEquals('H:i', $options['timeFormat']);
        $this->assertEquals('d.m.Y H:i', $options['dateTimeFormat']);
        $this->assertTrue($options['weekStartsMonday']);
        $this->assertTrue($options['use24Hour']);
        $this->assertEquals('eu', $options['regionFormat']);
    }

    /**
     * Test format changes reflect immediately without cache issues
     */
    public function test_format_changes_reflect_immediately(): void
    {
        $this->actingAs($this->user);

        $date = Carbon::create(2024, 3, 15, 14, 30, 0);

        // Initial US format
        $this->assertEquals('3/15/2024', formatDate($date));
        $this->assertEquals('2:30 PM', formatTime($date));

        // Change to EU format
        $this->user->update([
            'region_format' => 'eu',
            'date_format_type' => 'eu',
            'time_format' => '24h',
        ]);

        // Refresh user model to ensure changes are loaded
        $this->user->refresh();

        // Should immediately reflect new format
        $this->assertEquals('15.3.2024', formatDate($date, $this->user));
        $this->assertEquals('14:30', formatTime($date, $this->user));

        // Change to custom format
        $this->user->update([
            'region_format' => 'custom',
            'date_format' => 'Y/m/d',
        ]);

        $this->user->refresh();

        // Should immediately reflect custom format
        $this->assertEquals('2024/03/15', formatDate($date, $this->user));
    }

    /**
     * Test edge cases with null and empty values
     */
    public function test_edge_cases_with_null_and_empty_values(): void
    {
        $this->actingAs($this->user);

        // Test null date
        $this->assertEquals('', formatDate(null));
        $this->assertEquals('', formatTime(null));
        $this->assertEquals('', formatDateTime(null));

        // Test empty string
        $this->assertEquals('', formatDate(''));
        $this->assertEquals('', formatTime(''));
        $this->assertEquals('', formatDateTime(''));

        // Test invalid date string (should handle gracefully)
        try {
            formatDate('invalid-date');
            $this->assertTrue(true); // Should not throw exception
        } catch (\Exception $e) {
            $this->fail('Should handle invalid dates gracefully');
        }
    }

    /**
     * Test format persistence across session changes
     */
    public function test_format_persistence_across_session_changes(): void
    {
        // Set specific format preferences
        $this->user->update([
            'region_format' => 'eu',
            'date_format_type' => 'eu',
            'time_format' => '24h',
            'week_start' => 'monday',
        ]);

        $this->actingAs($this->user);

        $date = Carbon::create(2024, 3, 15, 14, 30, 0);

        // Test initial format
        $this->assertEquals('15.3.2024', formatDate($date));
        $this->assertEquals('14:30', formatTime($date));

        // Simulate session end and restart
        $this->app['auth']->logout();
        $this->user->refresh();
        $this->actingAs($this->user);

        // Format should persist
        $this->assertEquals('15.3.2024', formatDate($date));
        $this->assertEquals('14:30', formatTime($date));
    }

    /**
     * Test date range formatting for events and appointments
     */
    public function test_date_range_formatting_for_events(): void
    {
        $this->actingAs($this->user);

        // Same day event
        $start = Carbon::create(2024, 3, 15, 9, 0, 0);
        $end = Carbon::create(2024, 3, 15, 17, 30, 0);

        $formatted = formatDateRange($start, $end);
        $this->assertEquals('3/15/2024 9:00 AM - 5:30 PM', $formatted);

        // Multi-day event
        $endNextDay = Carbon::create(2024, 3, 16, 10, 0, 0);
        $formatted = formatDateRange($start, $endNextDay);
        $expected = '3/15/2024 9:00 AM - 3/16/2024 10:00 AM';
        $this->assertEquals($expected, $formatted);

        // Test with EU format
        $this->user->update([
            'region_format' => 'eu',
            'date_format_type' => 'eu',
            'time_format' => '24h',
        ]);

        $formatted = formatDateRange($start, $end, $this->user);
        $this->assertEquals('15.3.2024 09:00 - 17:30', $formatted);
    }

    /**
     * Test format display with mixed scenarios
     */
    public function test_format_display_with_mixed_scenarios(): void
    {
        $this->actingAs($this->user);

        // User starts with US format
        $date = Carbon::create(2024, 3, 15, 14, 30, 0);
        $this->assertEquals('3/15/2024 2:30 PM', formatDateTime($date));

        // Change to European format
        $this->user->update([
            'region_format' => 'eu',
            'date_format_type' => 'eu',
            'time_format' => '24h',
        ]);

        $this->assertEquals('15.3.2024 14:30', formatDateTime($date, $this->user));

        // Change to custom format
        $this->user->update([
            'region_format' => 'custom',
            'date_format' => 'Y-m-d',
            'time_format' => '12h',
        ]);

        $this->assertEquals('2024-03-15 2:30 PM', formatDateTime($date, $this->user));
    }

    /**
     * Test format helpers with different Carbon instances and timezones
     */
    public function test_format_helpers_with_different_carbon_instances(): void
    {
        $this->actingAs($this->user);

        // Test with different timezone Carbon instance
        $utcDate = Carbon::create(2024, 3, 15, 20, 0, 0, 'UTC');
        $nyDate = Carbon::create(2024, 3, 15, 15, 0, 0, 'America/New_York');

        // Both should format according to the given time (not auto-convert)
        $this->assertEquals('3/15/2024', formatDate($utcDate));
        $this->assertEquals('3/15/2024', formatDate($nyDate));

        $this->assertEquals('8:00 PM', formatTime($utcDate));
        $this->assertEquals('3:00 PM', formatTime($nyDate));

        // Test with immutable Carbon
        $immutableDate = Carbon::create(2024, 3, 15, 14, 30)->toImmutable();
        $this->assertEquals('3/15/2024 2:30 PM', formatDateTime($immutableDate));
    }
}
