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
        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('law_firms')->onDelete('cascade');
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('shared_with_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('permission', ['VIEW', 'EDIT']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_shares');
    }
};
