<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastUpdatebyToWorkflowRemarkTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workflow_remark', function (Blueprint $table) {
            $table->string('last_updateby')->after('wf_comment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_remark', function (Blueprint $table) {
            $table->dropColumn('last_updateby');
        });
    }
}