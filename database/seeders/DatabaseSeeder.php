<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 🚫 Temporarily disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Now you can safely truncate
        DB::table('users')->truncate();

        // ✅ Re-enable them
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Seed your user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
            'role_id' => 1,
            'is_active' => true,
            'avatar' => 'avatars/uA7kLLBrNLmTUBOqItIbIL7ItzXgnjToCcXSHY10.gif',
            'preferences' => json_encode(['theme' => 'light']),
            'password_changed_at' => now(),
            'force_password_change' => false,
            'last_login_at' => now(),
            'email_verified_at' => now(),
        ]);
    }
}
