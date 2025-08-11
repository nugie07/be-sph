<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterValueColumnToTextInReportingLovTable extends Migration
{
    public function up()
    {
        Schema::table('reporting_lov', function (Blueprint $table) {
            $table->text('value')->change();
        });
    }

    public function down()
    {
        Schema::table('reporting_lov', function (Blueprint $table) {
            $table->string('value', 255)->change();
        });
    }
}