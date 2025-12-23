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
        Schema::create('workflow_remark', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wf_id');
            $table->text('wf_comment');

            $table->foreign('wf_id')->references('id')->on('workflow_record')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_remark');
    }
};
