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
        Schema::create('oat_customer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cust_id');
            $table->string('location');
            $table->text('detail');
            $table->string('pic_name');
            $table->string('pic_contact');
            $table->integer('qty');
            $table->decimal('price', 15, 2);

            $table->foreign('cust_id')->references('id')->on('master_customer')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oat_customer');
    }
};
