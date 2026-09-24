<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Dinonaktifkan untuk production agar source code tidak menyimpan
     * email/password admin bawaan.
     *
     * Akun admin yang sudah ada di database TIDAK diubah atau dihapus.
     */
    public function run(): void
    {
        $this->command?->warn('AdminUserSeeder dinonaktifkan. Kelola akun admin dari database/proses provisioning yang aman.');
    }
}