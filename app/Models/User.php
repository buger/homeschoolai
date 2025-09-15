<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $locale
 * @property string $timezone
 * @property string $date_format
 * @property string $region_format
 * @property string $time_format
 * @property string $week_start
 * @property string $date_format_type
 * @property bool $email_notifications
 * @property bool $review_reminders
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Available locales
     */
    public const AVAILABLE_LOCALES = [
        'en' => 'English',
        'ru' => 'Русский',
    ];

    /**
     * Available timezones (common ones)
     */
    public const COMMON_TIMEZONES = [
        'UTC' => 'UTC',
        'America/New_York' => 'Eastern Time',
        'America/Chicago' => 'Central Time',
        'America/Denver' => 'Mountain Time',
        'America/Los_Angeles' => 'Pacific Time',
        'Europe/London' => 'London',
        'Europe/Moscow' => 'Moscow',
        'Asia/Tokyo' => 'Tokyo',
    ];

    /**
     * Available date formats
     */
    public const DATE_FORMATS = [
        'Y-m-d' => 'YYYY-MM-DD',
        'd/m/Y' => 'DD/MM/YYYY',
        'm/d/Y' => 'MM/DD/YYYY',
        'd.m.Y' => 'DD.MM.YYYY',
    ];

    /**
     * Regional format presets
     */
    public const REGION_FORMATS = [
        'us' => 'US Format',
        'eu' => 'European Format',
        'custom' => 'Custom',
    ];

    /**
     * Time format options
     */
    public const TIME_FORMATS = [
        '12h' => '12-hour (AM/PM)',
        '24h' => '24-hour',
    ];

    /**
     * Week start options
     */
    public const WEEK_START_OPTIONS = [
        'sunday' => 'Sunday',
        'monday' => 'Monday',
    ];

    /**
     * Date format types
     */
    public const DATE_FORMAT_TYPES = [
        'us' => 'MM/DD/YYYY',
        'eu' => 'DD.MM.YYYY',
        'iso' => 'YYYY-MM-DD',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'timezone',
        'date_format',
        'region_format',
        'time_format',
        'week_start',
        'date_format_type',
        'email_notifications',
        'review_reminders',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_notifications' => 'boolean',
            'review_reminders' => 'boolean',
        ];
    }

    /**
     * Get the children for the user.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    /**
     * Get all subjects for all of the user's children.
     */
    public function subjects(): HasManyThrough
    {
        return $this->hasManyThrough(
            Subject::class,
            Child::class,
            'user_id', // Foreign key on children table
            'child_id', // Foreign key on subjects table
            'id', // Local key on users table
            'id' // Local key on children table
        );
    }

    /**
     * Get the user's preferences.
     */
    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreferences::class);
    }

    /**
     * Get or create user preferences
     */
    public function getPreferences(): UserPreferences
    {
        /** @var UserPreferences $preferences */
        $preferences = $this->preferences()->firstOrCreate([
            'user_id' => $this->id,
        ]);

        return $preferences;
    }

    /**
     * Get regional format defaults based on locale
     */
    public static function getRegionalDefaults(string $locale): array
    {
        return match ($locale) {
            'ru' => [
                'region_format' => 'eu',
                'time_format' => '24h',
                'week_start' => 'monday',
                'date_format_type' => 'eu',
                'date_format' => 'd.m.Y',
            ],
            'en' => [
                'region_format' => 'us',
                'time_format' => '12h',
                'week_start' => 'sunday',
                'date_format_type' => 'us',
                'date_format' => 'm/d/Y',
            ],
            default => [
                'region_format' => 'us',
                'time_format' => '12h',
                'week_start' => 'sunday',
                'date_format_type' => 'us',
                'date_format' => 'm/d/Y',
            ],
        };
    }

    /**
     * Apply regional format defaults to user
     */
    public function applyRegionalDefaults(?string $locale = null): void
    {
        $locale = $locale ?? $this->locale ?? 'en';
        $defaults = self::getRegionalDefaults($locale);

        $this->region_format = $defaults['region_format'];
        $this->time_format = $defaults['time_format'];
        $this->week_start = $defaults['week_start'];
        $this->date_format_type = $defaults['date_format_type'];
        $this->date_format = $defaults['date_format'];
    }

    /**
     * Check if user is using custom regional format
     */
    public function isCustomFormat(): bool
    {
        return $this->region_format === 'custom';
    }

    /**
     * Get date format string based on user's preference
     */
    public function getDateFormatString(): string
    {
        if ($this->isCustomFormat()) {
            return $this->date_format ?? 'm/d/Y';
        }

        return match ($this->date_format_type ?? 'us') {
            'eu' => 'd.m.Y',
            'iso' => 'Y-m-d',
            'us' => 'm/d/Y',
            default => 'm/d/Y',
        };
    }

    /**
     * Get time format string based on user's preference
     */
    public function getTimeFormatString(): string
    {
        return match ($this->time_format ?? '12h') {
            '24h' => 'H:i',
            '12h' => 'g:i A',
            default => 'g:i A',
        };
    }

    /**
     * Get datetime format string based on user's preferences
     */
    public function getDateTimeFormatString(): string
    {
        return $this->getDateFormatString().' '.$this->getTimeFormatString();
    }

    /**
     * Check if user prefers Monday as week start
     */
    public function prefersMondayWeekStart(): bool
    {
        return ($this->week_start ?? 'sunday') === 'monday';
    }
}
