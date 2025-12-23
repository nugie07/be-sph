<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFormatAndAliasToDataTransporterTable extends Migration
{
    public function up()
    {
        Schema::table('data_transporter', function (Blueprint $table) {
            $table->string('tipe')->after('id')->nullable();
            $table->string('format')->after('tipe')->nullable();
            $table->string('alias')->after('format')->nullable();
        });
    }

    public function down()
    {
        Schema::table('data_transporter', function (Blueprint $table) {
            $table->dropColumn(['tipe','format', 'alias']);
        });
    }
}