<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Add `tipe_sph` column after `id` in `data_trx_sph` table.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('data_trx_sph', function (Blueprint $table) {
            $table->string('tipe_sph', 10)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drop the `tipe_sph` column from `data_trx_sph` table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('data_trx_sph', function (Blueprint $table) {
            $table->dropColumn('tipe_sph');
        });
    }
};