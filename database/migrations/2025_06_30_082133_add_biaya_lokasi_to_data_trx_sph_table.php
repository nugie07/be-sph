<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('data_trx_sph', function (Blueprint $table) {
            $table->string('biaya_lokasi')->after('price_liter')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('data_trx_sph', function (Blueprint $table) {
            $table->dropColumn('biaya_lokasi');
        });
    }
};