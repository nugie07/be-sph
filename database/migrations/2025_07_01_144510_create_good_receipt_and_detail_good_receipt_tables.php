<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table 1: good_receipt
        Schema::create('good_receipt', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sph');
            $table->string('nama_customer');
            $table->string('po_file')->nullable();
            $table->decimal('sub_total', 18, 2)->default(0);
            $table->decimal('ppn', 18, 2)->default(0);
            $table->decimal('pbbkb', 18, 2)->default(0);
            $table->decimal('pph', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->string('terbilang')->nullable();
            $table->integer('status');
            $table->string('created_by');
            $table->integer('last_updateby');
            $table->timestamps();
        });

        // Table 2: detail_good_receipt
        Schema::create('detail_good_receipt', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gr_id');
            $table->string('nama_item');
            $table->integer('qty')->default(0);
            $table->decimal('per_item', 18, 2)->default(0);
            $table->decimal('total_harga', 18, 2)->default(0);
            $table->timestamps();

            // Foreign key ke good_receipt
            $table->foreign('gr_id')->references('id')->on('good_receipt')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_good_receipt');
        Schema::dropIfExists('good_receipt');
    }
};