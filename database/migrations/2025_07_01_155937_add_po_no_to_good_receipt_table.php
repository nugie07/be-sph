<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('good_receipt', function (Blueprint $table) {
            $table->string('po_no')->nullable()->after('nama_customer'); // sebelum po_file
        });
    }

    public function down(): void
    {
        Schema::table('good_receipt', function (Blueprint $table) {
            $table->dropColumn('po_no');
        });
    }
};
