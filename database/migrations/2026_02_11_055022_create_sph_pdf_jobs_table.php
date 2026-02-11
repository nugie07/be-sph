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
        Schema::create('sph_pdf_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sph_id');
            $table->string('status', 20)->default('queued'); // queued, processing, success, failed
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->boolean('update_file_sph')->default(false);
            $table->string('temp_sph_action', 20)->default('insert'); // insert, update
            $table->string('pdf_url', 500)->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('triggered_by_user_id')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sph_pdf_jobs');
    }
};
