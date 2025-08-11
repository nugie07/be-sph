<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('workflow_record', function (Blueprint $table) {
            // Add a new non-nullable varchar column 'trx_tipe' after 'trx_id'
            $table->string('tipe_trx')->after('trx_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('workflow_record', function (Blueprint $table) {
            // Drop the 'trx_tipe' column
            $table->dropColumn('tipe_trx');
        });
    }
};