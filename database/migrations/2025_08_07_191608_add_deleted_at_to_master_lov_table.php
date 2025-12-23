<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedAtToMasterLovTable extends Migration
{
    public function up()
    {
        Schema::table('master_lov', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down()
    {
        Schema::table('master_lov', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}