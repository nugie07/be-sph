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
        Schema::create('sph_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sph_id');
            $table->string('cname_lname');
            $table->string('product');
            $table->integer('qty');
            $table->decimal('price_liter', 15, 2);
            $table->decimal('ppn', 15, 2);
            $table->decimal('pbbkb', 15, 2);
            $table->decimal('transport', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->decimal('grand_total', 15, 2);
            $table->timestamps();

            $table->foreign('sph_id')->references('id')->on('data_trx_sph')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sph_details');
    }
};
