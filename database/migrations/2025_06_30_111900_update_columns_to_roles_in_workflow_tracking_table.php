<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflow_record', function (Blueprint $table) {
            // Pastikan kolom lama sudah di-drop sebelum tambah kolom baru
            if (Schema::hasColumn('workflow_record', 'curr_user')) {
                $table->dropColumn('curr_user');
            }
            if (Schema::hasColumn('workflow_record', 'next_user')) {
                $table->dropColumn('next_user');
            }

            // Tambahkan kolom baru setelah trx_id
            $table->integer('curr_role')->nullable()->after('trx_id');
            $table->integer('next_role')->nullable()->after('curr_role');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_record', function (Blueprint $table) {
            // Hapus kolom baru
            if (Schema::hasColumn('workflow_record', 'curr_role')) {
                $table->dropColumn('curr_role');
            }
            if (Schema::hasColumn('workflow_record', 'next_role')) {
                $table->dropColumn('next_role');
            }

            // Tambahkan kembali kolom lama
            $table->integer('curr_user')->nullable()->after('trx_id');
            $table->integer('next_user')->nullable()->after('curr_user');
        });
    }
};