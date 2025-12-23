<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterSecondApprNullableOnWorkflowEngine extends Migration
{
    public function up()
    {
        Schema::table('workflow_engine', function (Blueprint $table) {
            $table->string('second_appr')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('workflow_engine', function (Blueprint $table) {
            $table->string('second_appr')->nullable(false)->change();
        });
    }
}