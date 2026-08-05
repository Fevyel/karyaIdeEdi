<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Buat/pastikan satu-satunya akun admin Toko Mebel.
     * Password disimpan ter-hash menggunakan Hash::make (bcrypt).
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'karyaku@gmail.com'],
            [
                'name' => 'Admin Toko Mebel',
                'password' => Hash::make('pemilikasliideedi123'),
                'email_verified_at' => now(),
            ],
        );
    }
}