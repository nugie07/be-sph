<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sph_pdf_jobs', function (Blueprint $table) {
            $table->string('kode_sph', 100)->nullable()->after('sph_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sph_pdf_jobs', function (Blueprint $table) {
            $table->dropColumn('kode_sph');
        });
    }
};
