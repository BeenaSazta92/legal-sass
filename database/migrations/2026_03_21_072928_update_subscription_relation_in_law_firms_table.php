<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('law_firms', function (Blueprint $table) {

            // 1. Drop old foreign key + column
            $table->dropForeign(['subscription_id']);
            $table->dropColumn('subscription_id');
            // 2. Add new column + relationship
            $table->foreignId('current_subscription_id')->nullable()->constrained('firm_subscriptions')->nullOnDelete(); // optional but recommended
        });
    }

    public function down(): void
    {
        Schema::table('law_firms', function (Blueprint $table) {

            // Reverse: remove new column
            $table->dropForeign(['current_subscription_id']);
            $table->dropColumn('current_subscription_id');

            // Restore old column
            $table->foreignId('subscription_id')->constrained('subscriptions');
        });
    }
};
