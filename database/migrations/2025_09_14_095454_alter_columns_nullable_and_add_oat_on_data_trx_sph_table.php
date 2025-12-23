<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make existing columns nullable and add new columns oat and ppn_oat
        // Use raw SQL to avoid requiring doctrine/dbal for change()
        DB::statement("ALTER TABLE data_trx_sph
            MODIFY COLUMN template_id BIGINT UNSIGNED NULL,
            MODIFY COLUMN tipe_sph VARCHAR(10) NULL,
            MODIFY COLUMN kode_sph VARCHAR(255) NULL,
            MODIFY COLUMN comp_name VARCHAR(255) NULL,
            MODIFY COLUMN pic VARCHAR(255) NULL,
            MODIFY COLUMN contact_no VARCHAR(255) NULL,
            MODIFY COLUMN product VARCHAR(255) NULL,
            MODIFY COLUMN price_liter DECIMAL(15,2) NULL,
            MODIFY COLUMN biaya_lokasi VARCHAR(255) NULL,
            MODIFY COLUMN ppn DECIMAL(15,2) NULL,
            MODIFY COLUMN pbbkb DECIMAL(15,2) NULL,
            MODIFY COLUMN total_price DECIMAL(15,2) NULL,
            MODIFY COLUMN pay_method TEXT NULL,
            MODIFY COLUMN susut VARCHAR(255) NULL,
            MODIFY COLUMN note_berlaku TEXT NULL,
            MODIFY COLUMN status INT NULL,
            MODIFY COLUMN file_sph VARCHAR(255) NULL,
            MODIFY COLUMN created_by VARCHAR(255) NULL,
            MODIFY COLUMN created_by_id SMALLINT UNSIGNED NULL,
            MODIFY COLUMN last_updateby INT NULL,
            MODIFY COLUMN created_at TIMESTAMP NULL,
            MODIFY COLUMN updated_at TIMESTAMP NULL
        ");

        Schema::table('data_trx_sph', function (Blueprint $table) {
            $table->decimal('oat', 15, 2)->nullable()->after('total_price');
            $table->decimal('ppn_oat', 15, 2)->nullable()->after('oat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_trx_sph', function (Blueprint $table) {
            $table->dropColumn('ppn_oat');
            $table->dropColumn('oat');
        });

        // Revert columns back to NOT NULL based on original definitions
        DB::statement("ALTER TABLE data_trx_sph
            MODIFY COLUMN template_id BIGINT UNSIGNED NULL,
            MODIFY COLUMN tipe_sph VARCHAR(10) NOT NULL,
            MODIFY COLUMN kode_sph VARCHAR(255) NOT NULL,
            MODIFY COLUMN comp_name VARCHAR(255) NOT NULL,
            MODIFY COLUMN pic VARCHAR(255) NOT NULL,
            MODIFY COLUMN contact_no VARCHAR(255) NOT NULL,
            MODIFY COLUMN product VARCHAR(255) NOT NULL,
            MODIFY COLUMN price_liter DECIMAL(15,2) NOT NULL,
            MODIFY COLUMN biaya_lokasi VARCHAR(255) NULL,
            MODIFY COLUMN ppn DECIMAL(15,2) NOT NULL,
            MODIFY COLUMN pbbkb DECIMAL(15,2) NOT NULL,
            MODIFY COLUMN total_price DECIMAL(15,2) NOT NULL,
            MODIFY COLUMN pay_method TEXT NOT NULL,
            MODIFY COLUMN susut VARCHAR(255) NOT NULL,
            MODIFY COLUMN note_berlaku TEXT NOT NULL,
            MODIFY COLUMN status INT NOT NULL,
            MODIFY COLUMN file_sph VARCHAR(255) NULL,
            MODIFY COLUMN created_by VARCHAR(255) NULL,
            MODIFY COLUMN created_by_id SMALLINT UNSIGNED NULL,
            MODIFY COLUMN last_updateby INT NULL,
            MODIFY COLUMN created_at TIMESTAMP NOT NULL,
            MODIFY COLUMN updated_at TIMESTAMP NOT NULL
        ");
    }
};
