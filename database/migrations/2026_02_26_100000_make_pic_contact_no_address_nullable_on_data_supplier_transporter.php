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
        Schema::table('data_supplier_transporter', function (Blueprint $table) {
            $table->string('pic')->nullable()->change();
            $table->string('contact_no')->nullable()->change();
            $table->string('address')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_supplier_transporter', function (Blueprint $table) {
            $table->string('pic')->nullable(false)->change();
            $table->string('contact_no')->nullable(false)->change();
            $table->string('address')->nullable(false)->change();
        });
    }
};
