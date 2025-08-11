<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMasterCustomerTableDropAndModifyColumns extends Migration
{
    public function up()
    {
        Schema::table('master_customer', function (Blueprint $table) {
            // Drop columns
            $table->dropColumn(['pay_terms', 'fob', 'delivery_method']);

            // Modify columns to be nullable
            $table->string('bill_to')->nullable()->change();
            $table->string('ship_to')->nullable()->change();
            $table->string('status')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('master_customer', function (Blueprint $table) {
            // Re-add dropped columns (adjust types as per original)
            $table->string('pay_terms')->nullable();
            $table->string('fob')->nullable();
            $table->string('delivery_method')->nullable();

            // Revert nullable changes
            $table->string('bill_to')->nullable(false)->change();
            $table->string('ship_to')->nullable(false)->change();
            $table->string('status')->nullable(false)->change();
        });
    }
}