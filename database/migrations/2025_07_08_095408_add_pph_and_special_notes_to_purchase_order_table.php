<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPphAndSpecialNotesToPurchaseOrderTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('purchase_order', function (Blueprint $table) {
            $table->decimal('pph', 20, 2)->nullable()->after('pbbkb');
            $table->text('special_notes')->nullable()->after('additional_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('purchase_order', function (Blueprint $table) {
            $table->dropColumn('pph');
            $table->dropColumn('special_notes');
        });
    }
}