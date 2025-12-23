<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('oat_customer', function (Blueprint $table) {
            // Hapus kolom
            $table->dropColumn(['detail', 'pic_name', 'pic_contact', 'price']);

            // Ubah tipe kolom qty dari int ke varchar
            $table->string('qty', 255)->change();

            // Tambahkan kolom baru
            $table->string('oat', 255)->nullable()->after('qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oat_customer', function (Blueprint $table) {
            // Kembalikan kolom yang dihapus (harus didefinisikan ulang sesuai tipe awal)
            $table->text('detail')->nullable();
            $table->string('pic_name', 255)->nullable();
            $table->string('pic_contact', 50)->nullable();
            $table->decimal('price', 15, 2)->nullable();

            // Ubah kembali kolom qty ke int
            $table->integer('qty')->change();

            // Hapus kolom oat
            $table->dropColumn('oat');
        });
    }
};