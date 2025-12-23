<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRevGoodReceiptTable extends Migration
{
    public function up()
    {
        Schema::create('rev_good_receipt', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('gr_id');
            $table->string('old_po');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('gr_id')->references('id')->on('good_receipt')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rev_good_receipt');
    }
}