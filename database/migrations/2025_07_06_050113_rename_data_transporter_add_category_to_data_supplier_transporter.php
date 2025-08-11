<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameDataTransporterAddCategoryToDataSupplierTransporter extends Migration
{
    public function up()
    {
        // Rename table
        Schema::rename('data_transporter', 'data_supplier_transporter');

        // Tambah kolom category setelah kolom status
        Schema::table('data_supplier_transporter', function (Blueprint $table) {
            $table->integer('category')->nullable()->after('status')->comment('1: Supplier, 2: Transporter');
        });
    }

    public function down()
    {
        // Hapus kolom category
        Schema::table('data_supplier_transporter', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        // Rename kembali ke nama semula
        Schema::rename('data_supplier_transporter', 'data_transporter');
    }
}