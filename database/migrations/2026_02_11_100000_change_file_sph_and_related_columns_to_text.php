<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ubah kolom yang menyimpan URL/path file ke TEXT agar URL presigned (panjang) bisa disimpan.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE data_trx_sph MODIFY COLUMN file_sph TEXT NULL');
        DB::statement('ALTER TABLE temp_sph MODIFY COLUMN temp_link TEXT');
        DB::statement('ALTER TABLE sph_pdf_jobs MODIFY COLUMN pdf_url TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE data_trx_sph MODIFY COLUMN file_sph VARCHAR(255) NULL');
        DB::statement('ALTER TABLE temp_sph MODIFY COLUMN temp_link VARCHAR(255)');
        DB::statement('ALTER TABLE sph_pdf_jobs MODIFY COLUMN pdf_url VARCHAR(500) NULL');
    }
};
