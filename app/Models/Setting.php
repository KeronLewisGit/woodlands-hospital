<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Key/value settings edited from the dashboard: phone numbers, extensions, hours.
 * DEFAULTS (the numbers on the hospital's current holding page) are used whenever a key
 * is missing or blank; SettingsSeeder writes them into the table once.
 *
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    /**
     * Defaults from the woodlandshospital.com holding page (October 2026). Staff change
     * them in the dashboard; a blank value falls back to these. Hours that the hospital has
     * not published are left empty, and the site shows "call to confirm" copy instead.
     *
     * @var array<string, string>
     */
    public const array DEFAULTS = [
        'phone_main' => '225-4050',
        'ambulance_phone' => '645-4765',
        'ambulance_ext' => '911',
        'ext_clinics' => '251',
        'ext_radiology' => '282',
        'ext_lab' => '319',
        'extra_extensions' => 'Dr N. Gobin clinic | 265',
        'pharmacy_whatsapp' => '622-3829',
        'email_care' => 'wecare@woodlandshospital.com',
        'email_customer' => 'customercare@woodlandshospital.com',
        'visiting_hours' => '',
        'pharmacy_hours' => 'Open 24 hours',
        'clinic_hours_note' => '',
        'notify_email' => '',
        'announcement' => '',
    ];

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** @return array<string, string|null> */
    public static function allCached(): array
    {
        try {
            return Cache::remember('settings.all', 300, fn () => static::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            // Before the first migration (installer, fresh checkout) the table may not exist yet.
            return [];
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::allCached()[$key] ?? null;
        if ($value !== null && $value !== '') {
            return $value;
        }

        return $default ?? (self::DEFAULTS[$key] ?? null);
    }

    /** @param array<string, string|null> $values */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('settings.all');
    }

    public static function tableReady(): bool
    {
        return Schema::hasTable('settings');
    }
}
