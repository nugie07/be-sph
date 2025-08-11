<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtpVerifikasiTable extends Migration
{
    public function up()
    {
        Schema::create('otp_verifikasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('contact');
            $table->string('otp', 10);
            $table->timestamps(); // created_at & updated_at
            $table->timestamp('expire_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Foreign key ke table users (opsional)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('otp_verifikasi');
    }
}