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
        // Schema::table('law_firms', function (Blueprint $table) {
        //     $table->boolean('is_default_subscription')->default(false);
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('law_firms', function (Blueprint $table) {
             Schema::table('law_firms', function (Blueprint $table) {
            $table->dropColumn('is_default_subscription'); // <- this line is required
        });
        });
    }
};
