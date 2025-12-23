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
        Schema::table('data_trx_sph', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable()->after('id');
            $table->index('template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_trx_sph', function (Blueprint $table) {
            $table->dropIndex(['template_id']);
            $table->dropColumn('template_id');
        });
    }
};
