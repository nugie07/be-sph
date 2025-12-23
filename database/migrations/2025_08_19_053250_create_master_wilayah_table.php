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
        Schema::create('master_wilayah', function (Blueprint $table) {
            $table->id(); // id auto increment (bigint unsigned)
            $table->string('nama', 255); // nama varchar
            $table->string('value', 255); // value varchar
            $table->tinyInteger('status')->default(1); // status int 1 digit
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_wilayah');
    }
};