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
        Schema::create('data_trx_sph', function (Blueprint $table) {
        $table->id();
        $table->string('kode_sph');
        $table->string('comp_name');
        $table->string('pic');
        $table->string('contact_no');
        $table->string('product');
        $table->decimal('price_liter', 15, 2);
        $table->decimal('ppn', 15, 2);
        $table->decimal('pbbkb', 15, 2);
        $table->decimal('total_price', 15, 2);
        $table->text('pay_method');
        $table->string('susut');
        $table->text('note_berlaku');
        $table->integer('status');

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_trx_sph');
    }
};
