<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileFormatPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'locale' => 'en',
            'timezone' => 'America/New_York',
            'region_format' => 'us',
            'time_format' => '12h',
            'week_start' => 'sunday',
            'date_format_type' => 'us',
            'date_format' => 'm/d/Y',
            'email_notifications' => true,
            'review_reminders' => false,
        ]);

        $this->actingAs($this->user);
    }

    /**
     * Test profile settings page displays current preferences
     */
    public function test_profile_settings_page_displays_current_preferences(): void
    {
        $response = $this->get('/profile');

        $response->assertOk()
            ->assertSee($this->user->locale)
            ->assertSee($this->user->timezone)
            ->assertSee($this->user->region_format)
            ->assertSee($this->user->date_format);
    }

    /**
     * Test updatePreferences with valid US format preset
     */
    public function test_update_preferences_with_valid_us_format_preset(): void
    {
        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'America/Chicago',
            'date_format' => 'm/d/Y',
            'region_format' => 'us',
            'email_notifications' => true,
            'review_reminders' => true,
        ]);

        $response->assertRedirect('/profile')
            ->assertSessionHas('status');

        $this->user->refresh();

        $this->assertEquals('en', $this->user->locale);
        $this->assertEquals('America/Chicago', $this->user->timezone);
        $this->assertEquals('us', $this->user->region_format);
        $this->assertEquals('12h', $this->user->time_format);
        $this->assertEquals('sunday', $this->user->week_start);
        $this->assertEquals('us', $this->user->date_format_type);
        $this->assertEquals('m/d/Y', $this->user->date_format);
        $this->assertTrue($this->user->email_notifications);
        $this->assertTrue($this->user->review_reminders);
    }

    /**
     * Test updatePreferences with valid European format preset
     */
    public function test_update_preferences_with_valid_european_format_preset(): void
    {
        $response = $this->patch('/profile/preferences', [
            'locale' => 'ru',
            'timezone' => 'Europe/London',
            'date_format' => 'd.m.Y',
            'region_format' => 'eu',
            'email_notifications' => false,
            'review_reminders' => false,
        ]);

        $response->assertRedirect('/profile')
            ->assertSessionHas('status');

        $this->user->refresh();

        $this->assertEquals('ru', $this->user->locale);
        $this->assertEquals('Europe/London', $this->user->timezone);
        $this->assertEquals('eu', $this->user->region_format);
        $this->assertEquals('24h', $this->user->time_format);
        $this->assertEquals('monday', $this->user->week_start);
        $this->assertEquals('eu', $this->user->date_format_type);
        $this->assertEquals('d.m.Y', $this->user->date_format);
        $this->assertFalse($this->user->email_notifications);
        $this->assertFalse($this->user->review_reminders);
    }

    /**
     * Test updatePreferences with custom format preferences
     */
    public function test_update_preferences_with_custom_format_preferences(): void
    {
        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'Asia/Tokyo',
            'date_format' => 'Y/m/d',
            'region_format' => 'custom',
            'time_format' => '24h',
            'week_start' => 'monday',
            'date_format_type' => 'iso',
        ]);

        $response->assertRedirect('/profile')
            ->assertSessionHas('status');

        $this->user->refresh();

        $this->assertEquals('en', $this->user->locale);
        $this->assertEquals('Asia/Tokyo', $this->user->timezone);
        $this->assertEquals('custom', $this->user->region_format);
        $this->assertEquals('24h', $this->user->time_format);
        $this->assertEquals('monday', $this->user->week_start);
        $this->assertEquals('iso', $this->user->date_format_type);
        $this->assertEquals('Y/m/d', $this->user->date_format);
    }

    /**
     * Test updatePreferences with partial custom format falls back to defaults
     */
    public function test_update_preferences_with_partial_custom_format_uses_defaults(): void
    {
        $this->user->update([
            'time_format' => '24h',
            'week_start' => 'monday',
            'date_format_type' => 'eu',
        ]);

        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'region_format' => 'custom',
            // Not providing time_format, week_start, date_format_type
        ]);

        $response->assertRedirect('/profile');

        $this->user->refresh();

        // Should preserve existing values when not provided
        $this->assertEquals('custom', $this->user->region_format);
        $this->assertEquals('24h', $this->user->time_format);
        $this->assertEquals('monday', $this->user->week_start);
        $this->assertEquals('eu', $this->user->date_format_type);
    }

    /**
     * Test updatePreferences with custom format and null existing values uses defaults
     */
    public function test_update_preferences_with_custom_format_and_null_values_uses_defaults(): void
    {
        $this->user->update([
            'time_format' => null,
            'week_start' => null,
            'date_format_type' => null,
        ]);

        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'region_format' => 'custom',
        ]);

        $response->assertRedirect('/profile');

        $this->user->refresh();

        // Should use defaults for null values
        $this->assertEquals('custom', $this->user->region_format);
        $this->assertEquals('12h', $this->user->time_format);
        $this->assertEquals('sunday', $this->user->week_start);
        $this->assertEquals('us', $this->user->date_format_type);
    }

    /**
     * Test updatePreferences updates session locale when changed
     */
    public function test_update_preferences_updates_session_locale_when_changed(): void
    {
        $this->assertEquals('en', session('locale', 'default'));

        $response = $this->patch('/profile/preferences', [
            'locale' => 'ru',
            'timezone' => 'UTC',
            'date_format' => 'd.m.Y',
            'region_format' => 'eu',
        ]);

        $response->assertRedirect('/profile');

        $this->assertEquals('ru', session('locale'));
    }

    /**
     * Test updatePreferences doesn't update session when locale unchanged
     */
    public function test_update_preferences_doesnt_update_session_when_locale_unchanged(): void
    {
        session(['locale' => 'en']);

        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'America/Chicago',
            'date_format' => 'm/d/Y',
            'region_format' => 'us',
        ]);

        $response->assertRedirect('/profile');

        // Session should still be 'en'
        $this->assertEquals('en', session('locale'));
    }

    /**
     * Test updatePreferences validation for required fields
     */
    public function test_update_preferences_validates_required_fields(): void
    {
        $response = $this->patch('/profile/preferences', []);

        $response->assertSessionHasErrors([
            'locale',
            'timezone',
            'date_format',
            'region_format',
        ]);
    }

    /**
     * Test updatePreferences validation for locale values
     */
    public function test_update_preferences_validates_locale_values(): void
    {
        $response = $this->patch('/profile/preferences', [
            'locale' => 'invalid',
            'timezone' => 'UTC',
            'date_format' => 'm/d/Y',
            'region_format' => 'us',
        ]);

        $response->assertSessionHasErrors('locale');

        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'm/d/Y',
            'region_format' => 'us',
        ]);

        $response->assertSessionDoesntHaveErrors('locale');
    }

    /**
     * Test updatePreferences validation for region format values
     */
    public function test_update_preferences_validates_region_format_values(): void
    {
        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'm/d/Y',
            'region_format' => 'invalid',
        ]);

        $response->assertSessionHasErrors('region_format');

        // Test valid values
        foreach (['us', 'eu', 'custom'] as $validFormat) {
            $response = $this->patch('/profile/preferences', [
                'locale' => 'en',
                'timezone' => 'UTC',
                'date_format' => 'm/d/Y',
                'region_format' => $validFormat,
            ]);

            $response->assertSessionDoesntHaveErrors('region_format');
        }
    }

    /**
     * Test updatePreferences validation for time format values
     */
    public function test_update_preferences_validates_time_format_values(): void
    {
        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'm/d/Y',
            'region_format' => 'custom',
            'time_format' => 'invalid',
        ]);

        $response->assertSessionHasErrors('time_format');

        // Test valid values
        foreach (['12h', '24h'] as $validFormat) {
            $response = $this->patch('/profile/preferences', [
                'locale' => 'en',
                'timezone' => 'UTC',
                'date_format' => 'm/d/Y',
                'region_format' => 'custom',
                'time_format' => $validFormat,
            ]);

            $response->assertSessionDoesntHaveErrors('time_format');
        }
    }

    /**
     * Test updatePreferences validation for week start values
     */
    public function test_update_preferences_validates_week_start_values(): void
    {
        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'm/d/Y',
            'region_format' => 'custom',
            'week_start' => 'invalid',
        ]);

        $response->assertSessionHasErrors('week_start');

        // Test valid values
        foreach (['sunday', 'monday'] as $validStart) {
            $response = $this->patch('/profile/preferences', [
                'locale' => 'en',
                'timezone' => 'UTC',
                'date_format' => 'm/d/Y',
                'region_format' => 'custom',
                'week_start' => $validStart,
            ]);

            $response->assertSessionDoesntHaveErrors('week_start');
        }
    }

    /**
     * Test updatePreferences validation for date format type values
     */
    public function test_update_preferences_validates_date_format_type_values(): void
    {
        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'm/d/Y',
            'region_format' => 'custom',
            'date_format_type' => 'invalid',
        ]);

        $response->assertSessionHasErrors('date_format_type');

        // Test valid values
        foreach (['us', 'eu', 'iso'] as $validType) {
            $response = $this->patch('/profile/preferences', [
                'locale' => 'en',
                'timezone' => 'UTC',
                'date_format' => 'm/d/Y',
                'region_format' => 'custom',
                'date_format_type' => $validType,
            ]);

            $response->assertSessionDoesntHaveErrors('date_format_type');
        }
    }

    /**
     * Test updatePreferences handles boolean checkboxes correctly
     */
    public function test_update_preferences_handles_boolean_checkboxes(): void
    {
        // Test unchecked checkboxes (no value sent)
        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'm/d/Y',
            'region_format' => 'us',
        ]);

        $response->assertRedirect('/profile');

        $this->user->refresh();

        $this->assertFalse($this->user->email_notifications);
        $this->assertFalse($this->user->review_reminders);

        // Test checked checkboxes
        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'm/d/Y',
            'region_format' => 'us',
            'email_notifications' => true,
            'review_reminders' => true,
        ]);

        $response->assertRedirect('/profile');

        $this->user->refresh();

        $this->assertTrue($this->user->email_notifications);
        $this->assertTrue($this->user->review_reminders);
    }

    /**
     * Test updatePreferences returns success message
     */
    public function test_update_preferences_returns_success_message(): void
    {
        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'm/d/Y',
            'region_format' => 'us',
        ]);

        $response->assertRedirect('/profile')
            ->assertSessionHas('status');

        $status = session('status');
        $this->assertNotEmpty($status);
    }

    /**
     * Test updatePreferences preserves unrelated user data
     */
    public function test_update_preferences_preserves_unrelated_user_data(): void
    {
        $originalName = $this->user->name;
        $originalEmail = $this->user->email;
        $originalCreatedAt = $this->user->created_at;

        $response = $this->patch('/profile/preferences', [
            'locale' => 'ru',
            'timezone' => 'Europe/Moscow',
            'date_format' => 'd.m.Y',
            'region_format' => 'eu',
        ]);

        $response->assertRedirect('/profile');

        $this->user->refresh();

        $this->assertEquals($originalName, $this->user->name);
        $this->assertEquals($originalEmail, $this->user->email);
        $this->assertEquals($originalCreatedAt, $this->user->created_at);
    }

    /**
     * Test updatePreferences requires authentication
     */
    public function test_update_preferences_requires_authentication(): void
    {
        $this->app['auth']->logout();

        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'm/d/Y',
            'region_format' => 'us',
        ]);

        $response->assertRedirect('/login');
    }

    /**
     * Test updatePreferences with complex custom scenario
     */
    public function test_update_preferences_complex_custom_scenario(): void
    {
        // Start with EU user switching to custom format
        $this->user->update([
            'locale' => 'ru',
            'region_format' => 'eu',
            'time_format' => '24h',
            'week_start' => 'monday',
            'date_format_type' => 'eu',
        ]);

        $response = $this->patch('/profile/preferences', [
            'locale' => 'en', // Change locale
            'timezone' => 'America/Los_Angeles',
            'date_format' => 'Y/m/d', // Custom format
            'region_format' => 'custom',
            'time_format' => '12h', // Custom time format
            'week_start' => 'sunday', // Custom week start
            'date_format_type' => 'iso', // Custom date type
        ]);

        $response->assertRedirect('/profile');

        $this->user->refresh();

        $this->assertEquals('en', $this->user->locale);
        $this->assertEquals('America/Los_Angeles', $this->user->timezone);
        $this->assertEquals('custom', $this->user->region_format);
        $this->assertEquals('12h', $this->user->time_format);
        $this->assertEquals('sunday', $this->user->week_start);
        $this->assertEquals('iso', $this->user->date_format_type);
        $this->assertEquals('Y/m/d', $this->user->date_format);
    }

    /**
     * Test switching between preset formats preserves correct settings
     */
    public function test_switching_between_preset_formats_preserves_correct_settings(): void
    {
        // Start with US format
        $this->user->update(['region_format' => 'us']);

        // Switch to EU format
        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'd.m.Y',
            'region_format' => 'eu',
        ]);

        $response->assertRedirect('/profile');

        $this->user->refresh();

        $this->assertEquals('eu', $this->user->region_format);
        $this->assertEquals('24h', $this->user->time_format);
        $this->assertEquals('monday', $this->user->week_start);
        $this->assertEquals('eu', $this->user->date_format_type);
        $this->assertEquals('d.m.Y', $this->user->date_format);

        // Switch back to US format
        $response = $this->patch('/profile/preferences', [
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'm/d/Y',
            'region_format' => 'us',
        ]);

        $response->assertRedirect('/profile');

        $this->user->refresh();

        $this->assertEquals('us', $this->user->region_format);
        $this->assertEquals('12h', $this->user->time_format);
        $this->assertEquals('sunday', $this->user->week_start);
        $this->assertEquals('us', $this->user->date_format_type);
        $this->assertEquals('m/d/Y', $this->user->date_format);
    }
}
