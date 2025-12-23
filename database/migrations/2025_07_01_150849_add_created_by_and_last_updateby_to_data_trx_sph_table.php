<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_trx_sph', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('status');
            $table->integer('last_updateby')->nullable()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('data_trx_sph', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->dropColumn('last_updateby');
        });
    }
};