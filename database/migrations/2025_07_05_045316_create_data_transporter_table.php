<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDataTransporterTable extends Migration
{
    public function up()
    {
        Schema::create('data_transporter', function (Blueprint $table) {
            $table->id(); // Auto increment
            $table->string('nama');
            $table->string('pic');
            $table->string('contact_no');
            $table->string('email')->nullable();
            $table->string('address');
            $table->integer('status')->default(1);
            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_transporter');
    }
}