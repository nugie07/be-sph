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
        Schema::create('workflow_record', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trx_id');
            $table->integer('curr_user');
            $table->integer('next_user');
            $table->integer('wf_status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_record');
    }
};
