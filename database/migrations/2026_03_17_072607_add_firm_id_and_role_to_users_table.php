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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('firm_id')->nullable()->constrained('law_firms')->onDelete('cascade');
            $table->enum('role', ['SYSTEM_ADMIN', 'ADMIN', 'LAWYER', 'CLIENT']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['firm_id']);
            $table->dropColumn(['firm_id', 'role']);
        });
    }
};
