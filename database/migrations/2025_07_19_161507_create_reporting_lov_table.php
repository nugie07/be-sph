<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportingLovTable extends Migration
{
    public function up()
    {
        Schema::create('reporting_lov', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable(false);
            $table->string('value')->nullable(false);
            $table->unsignedBigInteger('parent_id')->nullable()->default(null);
            $table->timestamps();

            // Jika parent_id relasi ke tabel ini sendiri (optional, uncomment jika butuh FK)
            // $table->foreign('parent_id')->references('id')->on('reporting_lov')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reporting_lov');
    }
}