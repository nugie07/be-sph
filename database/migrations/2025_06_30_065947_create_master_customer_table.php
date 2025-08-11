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
        Schema::create('master_customer', function (Blueprint $table) {
            $table->id();
            $table->string('cust_code');
            $table->string('alias');
            $table->string('type');
            $table->string('name');
            $table->text('address');
            $table->string('pic_name');
            $table->string('pic_contact');
            $table->string('email')->nullable();
            $table->string('pay_terms');
            $table->text('fob');
            $table->integer('delivery_method');
            $table->text('bill_to');
            $table->text('ship_to');
            $table->integer('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_customer');
    }
};
