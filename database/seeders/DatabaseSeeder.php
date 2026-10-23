<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Production-safe: writes the default phone numbers and extensions (only keys that are
     * missing) and creates the first administrator when ADMIN_EMAIL and ADMIN_PASSWORD are set.
     * Doctors, clinic sessions and job postings are left empty for the hospital to fill in.
     */
    public function run(): void
    {
        $this->call(SettingsSeeder::class);

        $email = config('woodlands.admin.email');
        $password = config('woodlands.admin.password');
        if ($email && $password) {
            User::firstOrCreate(['email' => strtolower($email)], [
                'name' => config('woodlands.admin.name'),
                'password' => Hash::make($password),
                'role' => Role::Admin,
            ]);
        }
    }
}
