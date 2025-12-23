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
        Schema::create('sph_template', function (Blueprint $table) {
            $table->id();
            $table->string('tipe')->nullable();
            $table->string('nama')->nullable();
            $table->string('form')->nullable();
            $table->text('template')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sph_template');
    }
};
