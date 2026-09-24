<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'bca_account_number',
            'bca_account_name',
            'bri_account_number',
            'bri_account_name',
            'dana_account_number',
            'dana_account_name',
        ];

        foreach ($columns as $column) {
            if (! Schema::hasColumn('settings', $column)) {
                Schema::table('settings', function (Blueprint $table) use ($column): void {
                    $table->string($column, 120)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        $columns = [
            'bca_account_number',
            'bca_account_name',
            'bri_account_number',
            'bri_account_name',
            'dana_account_number',
            'dana_account_name',
        ];

        $existing = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('settings', $column)
        ));

        if ($existing !== []) {
            Schema::table('settings', function (Blueprint $table) use ($existing): void {
                $table->dropColumn($existing);
            });
        }
    }
};