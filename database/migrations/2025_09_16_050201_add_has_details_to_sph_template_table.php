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
        Schema::table('sph_template', function (Blueprint $table) {
            $table->integer('has_details')->nullable()->after('template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sph_template', function (Blueprint $table) {
            $table->dropColumn('has_details');
        });
    }
};
