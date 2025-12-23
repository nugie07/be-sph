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
        DB::statement("ALTER TABLE sph_details
            MODIFY COLUMN cname_lname VARCHAR(255) NULL,
            MODIFY COLUMN product VARCHAR(255) NULL,
            MODIFY COLUMN qty INT NULL,
            MODIFY COLUMN price_liter DECIMAL(15,2) NULL,
            MODIFY COLUMN ppn DECIMAL(15,2) NULL,
            MODIFY COLUMN pbbkb DECIMAL(15,2) NULL,
            MODIFY COLUMN transport DECIMAL(15,2) NULL,
            MODIFY COLUMN total_price DECIMAL(15,2) NULL,
            MODIFY COLUMN grand_total DECIMAL(15,2) NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE sph_details
            MODIFY COLUMN cname_lname VARCHAR(255) NOT NULL,
            MODIFY COLUMN product VARCHAR(255) NOT NULL,
            MODIFY COLUMN qty INT NOT NULL,
            MODIFY COLUMN price_liter DECIMAL(15,2) NOT NULL,
            MODIFY COLUMN ppn DECIMAL(15,2) NOT NULL,
            MODIFY COLUMN pbbkb DECIMAL(15,2) NOT NULL,
            MODIFY COLUMN transport DECIMAL(15,2) NOT NULL,
            MODIFY COLUMN total_price DECIMAL(15,2) NOT NULL,
            MODIFY COLUMN grand_total DECIMAL(15,2) NOT NULL
        ");
    }
};
