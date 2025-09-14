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
}
