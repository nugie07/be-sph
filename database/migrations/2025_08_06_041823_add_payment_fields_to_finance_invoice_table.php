<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentFieldsToFinanceInvoiceTable extends Migration
{
    public function up()
    {
        Schema::table('finance_invoice', function (Blueprint $table) {
            $table->integer('payment_status')->default(0)->after('file');
            $table->string('receipt_number')->nullable()->after('payment_status');
            $table->text('receipt_file')->nullable()->after('receipt_number');
            $table->date('payment_date')->nullable()->after('receipt_file');
        });
    }

    public function down()
    {
        Schema::table('finance_invoice', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'receipt_number',
                'receipt_file',
                'payment_date'
            ]);
        });
    }
}