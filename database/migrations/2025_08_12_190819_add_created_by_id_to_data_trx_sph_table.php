<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        Schema::table('data_trx_sph', function (Blueprint $table) {
            // INT(3) unsigned, boleh null, taruh setelah kolom terakhir (atau sesuai kebutuhan)
            $table->unsignedSmallInteger('created_by_id')
                  ->length(3) // untuk dokumentasi, karena MySQL modern abaikan length di INT
                  ->nullable()
                  ->after('created_by'); // ganti 'kolom_terakhir' dengan nama kolom sebelum kolom ini
        });
    }

    /**
     * Reverse migration.
     */
    public function down(): void
    {
        Schema::table('data_trx_sph', function (Blueprint $table) {
            $table->dropColumn('created_by_id');
        });
    }
};