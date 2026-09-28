<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds a local admin user who owns a demo workspace.
     * Credentials come from ADMIN_EMAIL / ADMIN_PASSWORD; nothing is created without them.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set; skipping admin seed.');
            return;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Admin', 'password' => $password],
        );

        $workspace = Workspace::firstOrCreate(
            ['slug' => 'demo'],
            ['name' => 'Demo', 'owner_user_id' => $user->id],
        );

        $workspace->members()->syncWithoutDetaching([$user->id => ['role' => 'owner']]);
    }
}
