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
        Schema::dropIfExists('customer_template');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('customer_template', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id');
            $table->integer('template_id');
            $table->timestamps();
        });
    }
};

