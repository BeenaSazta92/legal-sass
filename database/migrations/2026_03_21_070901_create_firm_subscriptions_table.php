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
        Schema::create('firm_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('law_firms')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            // Snapshot fields
            $table->string('name');
            $table->integer('max_admins');
            $table->integer('max_lawyers');
            $table->integer('max_clients');
            $table->integer('max_documents_per_user');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firm_subscriptions');
    }
};
