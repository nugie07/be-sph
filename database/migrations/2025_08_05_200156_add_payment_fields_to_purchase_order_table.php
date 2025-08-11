<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentFieldsToPurchaseOrderTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_order', function (Blueprint $table) {
            $table->integer('payment_status')->default(0)->after('status');
            $table->text('receipt_file')->nullable()->after('payment_status');
            $table->text('receipt_number')->nullable()->after('receipt_file');
            $table->date('payment_date')->nullable()->after('receipt_number');
        });
    }

    public function down()
    {
        Schema::table('purchase_order', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'receipt_file', 'receipt_number', 'payment_date']);
        });
    }
}