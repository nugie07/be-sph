<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBastDateToDeliveryNoteTable extends Migration
{
    public function up()
    {
        Schema::table('delivery_note', function (Blueprint $table) {
            $table->date('bast_date')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('delivery_note', function (Blueprint $table) {
            $table->dropColumn('bast_date');
        });
    }
}