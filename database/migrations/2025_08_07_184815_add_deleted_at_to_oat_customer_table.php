<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedAtToOatCustomerTable extends Migration
{
    public function up()
    {
        Schema::table('oat_customer', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down()
    {
        Schema::table('oat_customer', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}