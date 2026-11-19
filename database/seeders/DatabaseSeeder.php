<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Production seed: ONLY the first Admin account (from .env). No demo/family data —
 * real members, leaders and transactions are entered through the application.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $cfg = config('app.first_admin');
        if (app()->isProduction() && (empty($cfg['email']) || empty($cfg['password']))) {
            throw new \RuntimeException('Set ADMIN_EMAIL and ADMIN_PASSWORD in .env before seeding production.');
        }
        $email = strtolower((string) ($cfg['email'] ?: 'admin@example.com'));
        if (User::where('email', $email)->exists()) {
            return;
        }
        $admin = new User([
            'display_name' => $cfg['name'] ?: 'ຜູ້ດູແລລະບົບ',
            'email' => $email,
            'password' => $cfg['password'] ?: 'ChangeMe@2026',
        ]);
        $admin->role = 'admin';
        $admin->approved_at = now();
        $admin->save();

        $this->command?->warn("Admin created: {$email} — change the password after first login.");
    }
}
