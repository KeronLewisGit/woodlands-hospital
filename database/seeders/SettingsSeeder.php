<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /** Only fills keys that are missing, so re-seeding never overwrites what staff changed. */
    public function run(): void
    {
        foreach (Setting::DEFAULTS as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
        cache()->forget('settings.all');
    }
}
