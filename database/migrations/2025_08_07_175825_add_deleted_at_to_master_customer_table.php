<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedAtToMasterCustomerTable extends Migration
{
    public function up()
    {
        Schema::table('master_customer', function (Blueprint $table) {
            $table->softDeletes()->after('status');
        });
    }

    public function down()
    {
        Schema::table('master_customer', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}