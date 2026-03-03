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
        Schema::table('master_customer', function (Blueprint $table) {
            $table->string('pbbkb')->nullable()->after('ship_to');
            $table->string('susut')->nullable()->after('pbbkb');
            $table->string('payment')->nullable()->after('susut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_customer', function (Blueprint $table) {
            $table->dropColumn(['pbbkb', 'susut', 'payment']);
        });
    }
};
