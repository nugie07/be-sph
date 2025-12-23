<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_request', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('drs_no')->nullable();
            $table->string('drs_unique')->nullable();
            $table->string('customer_name', 191);
            $table->string('po_number', 191);
            $table->date('po_date');
            $table->string('source', 191);
            $table->decimal('volume', 8, 2);
            $table->decimal('truck_capacity', 8, 2);
            $table->date('request_date');
            $table->string('transporter_name', 191)->nullable();
            $table->string('wilayah', 191);
            $table->text('site_location');
            $table->string('delivery_note', 191)->nullable();
            $table->string('pic_site', 191)->nullable();
            $table->string('pic_site_telp', 255)->nullable();
            $table->string('requested_by', 191)->nullable();
            $table->text('additional_note')->nullable();
            $table->string('file_drs', 191)->nullable();
            $table->string('created_by', 191)->nullable();
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_request');
    }
};