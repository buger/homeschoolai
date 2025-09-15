<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserModelTest extends TestCase
{
    /**
     * Test getRegionalDefaults returns correct defaults for English locale
     */
    public function test_get_regional_defaults_for_english(): void
    {
        $defaults = User::getRegionalDefaults('en');

        $expected = [
            'region_format' => 'us',
            'time_format' => '12h',
            'week_start' => 'sunday',
            'date_format_type' => 'us',
            'date_format' => 'm/d/Y',
        ];

        $this->assertEquals($expected, $defaults);
    }

    /**
     * Test getRegionalDefaults returns correct defaults for Russian locale
     */
    public function test_get_regional_defaults_for_russian(): void
    {
        $defaults = User::getRegionalDefaults('ru');

        $expected = [
            'region_format' => 'eu',
            'time_format' => '24h',
            'week_start' => 'monday',
            'date_format_type' => 'eu',
            'date_format' => 'd.m.Y',
        ];

        $this->assertEquals($expected, $defaults);
    }

    /**
     * Test getRegionalDefaults returns US defaults for unknown locales
     */
    public function test_get_regional_defaults_for_unknown_locale(): void
    {
        $defaults = User::getRegionalDefaults('es');

        $expected = [
            'region_format' => 'us',
            'time_format' => '12h',
            'week_start' => 'sunday',
            'date_format_type' => 'us',
            'date_format' => 'm/d/Y',
        ];

        $this->assertEquals($expected, $defaults);
    }

    /**
     * Test applyRegionalDefaults sets correct values for English
     */
    public function test_apply_regional_defaults_for_english(): void
    {
        $user = new User;
        $user->locale = 'en';

        $user->applyRegionalDefaults('en');

        $this->assertEquals('us', $user->region_format);
        $this->assertEquals('12h', $user->time_format);
        $this->assertEquals('sunday', $user->week_start);
        $this->assertEquals('us', $user->date_format_type);
        $this->assertEquals('m/d/Y', $user->date_format);
    }

    /**
     * Test applyRegionalDefaults sets correct values for Russian
     */
    public function test_apply_regional_defaults_for_russian(): void
    {
        $user = new User;
        $user->locale = 'ru';

        $user->applyRegionalDefaults('ru');

        $this->assertEquals('eu', $user->region_format);
        $this->assertEquals('24h', $user->time_format);
        $this->assertEquals('monday', $user->week_start);
        $this->assertEquals('eu', $user->date_format_type);
        $this->assertEquals('d.m.Y', $user->date_format);
    }

    /**
     * Test applyRegionalDefaults uses user's locale if none provided
     */
    public function test_apply_regional_defaults_uses_user_locale(): void
    {
        $user = new User;
        $user->locale = 'ru';

        $user->applyRegionalDefaults();

        $this->assertEquals('eu', $user->region_format);
        $this->assertEquals('24h', $user->time_format);
        $this->assertEquals('monday', $user->week_start);
    }

    /**
     * Test applyRegionalDefaults falls back to English defaults
     */
    public function test_apply_regional_defaults_falls_back_to_english(): void
    {
        $user = new User;
        // No locale set

        $user->applyRegionalDefaults();

        $this->assertEquals('us', $user->region_format);
        $this->assertEquals('12h', $user->time_format);
        $this->assertEquals('sunday', $user->week_start);
    }

    /**
     * Test isCustomFormat returns true when region_format is custom
     */
    public function test_is_custom_format_returns_true_for_custom(): void
    {
        $user = new User;
        $user->region_format = 'custom';

        $this->assertTrue($user->isCustomFormat());
    }

    /**
     * Test isCustomFormat returns false for preset formats
     */
    public function test_is_custom_format_returns_false_for_preset_formats(): void
    {
        $user = new User;
        $user->region_format = 'us';

        $this->assertFalse($user->isCustomFormat());

        $user->region_format = 'eu';
        $this->assertFalse($user->isCustomFormat());
    }

    /**
     * Test isCustomFormat returns false when region_format is null
     */
    public function test_is_custom_format_returns_false_for_null(): void
    {
        $user = new User;
        $user->region_format = null;

        $this->assertFalse($user->isCustomFormat());
    }

    /**
     * Test getDateFormatString returns custom format when custom region format
     */
    public function test_get_date_format_string_returns_custom_format(): void
    {
        $user = new User;
        $user->region_format = 'custom';
        $user->date_format = 'Y/m/d';

        $this->assertEquals('Y/m/d', $user->getDateFormatString());
    }

    /**
     * Test getDateFormatString falls back to default when custom but no date_format
     */
    public function test_get_date_format_string_falls_back_to_default_for_custom(): void
    {
        $user = new User;
        $user->region_format = 'custom';
        $user->date_format = null;

        $this->assertEquals('m/d/Y', $user->getDateFormatString());
    }

    /**
     * Test getDateFormatString returns correct format for US type
     */
    public function test_get_date_format_string_returns_us_format(): void
    {
        $user = new User;
        $user->region_format = 'us';
        $user->date_format_type = 'us';

        $this->assertEquals('m/d/Y', $user->getDateFormatString());
    }

    /**
     * Test getDateFormatString returns correct format for EU type
     */
    public function test_get_date_format_string_returns_eu_format(): void
    {
        $user = new User;
        $user->region_format = 'eu';
        $user->date_format_type = 'eu';

        $this->assertEquals('d.m.Y', $user->getDateFormatString());
    }

    /**
     * Test getDateFormatString returns correct format for ISO type
     */
    public function test_get_date_format_string_returns_iso_format(): void
    {
        $user = new User;
        $user->region_format = 'us';
        $user->date_format_type = 'iso';

        $this->assertEquals('Y-m-d', $user->getDateFormatString());
    }

    /**
     * Test getDateFormatString falls back to US format for unknown type
     */
    public function test_get_date_format_string_falls_back_to_us_format(): void
    {
        $user = new User;
        $user->region_format = 'us';
        $user->date_format_type = 'unknown';

        $this->assertEquals('m/d/Y', $user->getDateFormatString());
    }

    /**
     * Test getDateFormatString falls back to US format when date_format_type is null
     */
    public function test_get_date_format_string_falls_back_when_null(): void
    {
        $user = new User;
        $user->region_format = 'us';
        $user->date_format_type = null;

        $this->assertEquals('m/d/Y', $user->getDateFormatString());
    }

    /**
     * Test getTimeFormatString returns 12-hour format
     */
    public function test_get_time_format_string_returns_12h_format(): void
    {
        $user = new User;
        $user->time_format = '12h';

        $this->assertEquals('g:i A', $user->getTimeFormatString());
    }

    /**
     * Test getTimeFormatString returns 24-hour format
     */
    public function test_get_time_format_string_returns_24h_format(): void
    {
        $user = new User;
        $user->time_format = '24h';

        $this->assertEquals('H:i', $user->getTimeFormatString());
    }

    /**
     * Test getTimeFormatString falls back to 12-hour format
     */
    public function test_get_time_format_string_falls_back_to_12h(): void
    {
        $user = new User;
        $user->time_format = null;

        $this->assertEquals('g:i A', $user->getTimeFormatString());

        $user->time_format = 'unknown';
        $this->assertEquals('g:i A', $user->getTimeFormatString());
    }

    /**
     * Test getDateTimeFormatString combines date and time formats
     */
    public function test_get_date_time_format_string_combines_formats(): void
    {
        $user = new User;
        $user->region_format = 'eu';
        $user->date_format_type = 'eu';
        $user->time_format = '24h';

        $expected = 'd.m.Y H:i';
        $this->assertEquals($expected, $user->getDateTimeFormatString());
    }

    /**
     * Test getDateTimeFormatString with custom date format
     */
    public function test_get_date_time_format_string_with_custom_date(): void
    {
        $user = new User;
        $user->region_format = 'custom';
        $user->date_format = 'Y/m/d';
        $user->time_format = '12h';

        $expected = 'Y/m/d g:i A';
        $this->assertEquals($expected, $user->getDateTimeFormatString());
    }

    /**
     * Test prefersMondayWeekStart returns true for Monday preference
     */
    public function test_prefers_monday_week_start_returns_true(): void
    {
        $user = new User;
        $user->week_start = 'monday';

        $this->assertTrue($user->prefersMondayWeekStart());
    }

    /**
     * Test prefersMondayWeekStart returns false for Sunday preference
     */
    public function test_prefers_monday_week_start_returns_false_for_sunday(): void
    {
        $user = new User;
        $user->week_start = 'sunday';

        $this->assertFalse($user->prefersMondayWeekStart());
    }

    /**
     * Test prefersMondayWeekStart returns false when week_start is null
     */
    public function test_prefers_monday_week_start_returns_false_for_null(): void
    {
        $user = new User;
        $user->week_start = null;

        $this->assertFalse($user->prefersMondayWeekStart());
    }

    /**
     * Test format constants are defined correctly
     */
    public function test_format_constants_are_defined(): void
    {
        $this->assertArrayHasKey('us', User::REGION_FORMATS);
        $this->assertArrayHasKey('eu', User::REGION_FORMATS);
        $this->assertArrayHasKey('custom', User::REGION_FORMATS);

        $this->assertArrayHasKey('12h', User::TIME_FORMATS);
        $this->assertArrayHasKey('24h', User::TIME_FORMATS);

        $this->assertArrayHasKey('sunday', User::WEEK_START_OPTIONS);
        $this->assertArrayHasKey('monday', User::WEEK_START_OPTIONS);

        $this->assertArrayHasKey('us', User::DATE_FORMAT_TYPES);
        $this->assertArrayHasKey('eu', User::DATE_FORMAT_TYPES);
        $this->assertArrayHasKey('iso', User::DATE_FORMAT_TYPES);
    }

    /**
     * Test regional format validation values
     */
    public function test_regional_format_constants_have_correct_values(): void
    {
        $this->assertEquals('US Format', User::REGION_FORMATS['us']);
        $this->assertEquals('European Format', User::REGION_FORMATS['eu']);
        $this->assertEquals('Custom', User::REGION_FORMATS['custom']);

        $this->assertEquals('12-hour (AM/PM)', User::TIME_FORMATS['12h']);
        $this->assertEquals('24-hour', User::TIME_FORMATS['24h']);

        $this->assertEquals('Sunday', User::WEEK_START_OPTIONS['sunday']);
        $this->assertEquals('Monday', User::WEEK_START_OPTIONS['monday']);

        $this->assertEquals('MM/DD/YYYY', User::DATE_FORMAT_TYPES['us']);
        $this->assertEquals('DD.MM.YYYY', User::DATE_FORMAT_TYPES['eu']);
        $this->assertEquals('YYYY-MM-DD', User::DATE_FORMAT_TYPES['iso']);
    }
}
