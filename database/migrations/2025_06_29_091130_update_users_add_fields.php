<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rename column
            $table->renameColumn('name', 'first_name');

            // Add new columns
            $table->string('last_name')->nullable();
            $table->text('address')->nullable();
            $table->string('country')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0: Suspend, 1: Active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rollback new columns
            $table->dropColumn(['last_name', 'address', 'country', 'status']);

            // Rename back
            $table->renameColumn('first_name', 'name');
        });
    }
};