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
        Schema::table('finance_invoice', function (Blueprint $table) {
            $table->text('bill_to_address')->nullable()->after('bill_to');
            $table->text('ship_to_address')->nullable()->after('ship_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_invoice', function (Blueprint $table) {
            $table->dropColumn(['bill_to_address', 'ship_to_address']);
        });
    }
};
