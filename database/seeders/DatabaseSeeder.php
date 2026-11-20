<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $adminRole = Role::findByName(RoleName::Admin);

        if ($adminRole !== null && ! User::query()->where('role_id', $adminRole->id)->exists()) {
            $adminEmail = env('INITIAL_ADMIN_EMAIL', 'admin@ruangtemubuku.local');
            $generatedPassword = env('INITIAL_ADMIN_PASSWORD') ?: Str::password(16);

            $admin = new User([
                'name' => 'Administrator Perpustakaan',
                'email' => $adminEmail,
                'password' => Hash::make($generatedPassword),
            ]);
            $admin->role_id = $adminRole->id;
            $admin->status = UserStatus::Active;
            $admin->save();

            if ($this->command !== null && ! env('INITIAL_ADMIN_PASSWORD')) {
                $this->command->warn("Akun Admin awal dibuat: {$adminEmail} | Password sekali tampil: {$generatedPassword}");
            }
        }
    }
}
