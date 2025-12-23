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
        Schema::table('sph_details', function (Blueprint $table) {
            $table->string('biaya_lokasi')->nullable()->after('product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sph_details', function (Blueprint $table) {
            $table->dropColumn('biaya_lokasi');
        });
    }
};
