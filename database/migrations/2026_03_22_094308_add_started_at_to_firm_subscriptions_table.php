<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('firm_subscriptions', function (Blueprint $table) {
            $table->timestamp('started_at')->after('max_documents_per_user');
            $table->timestamp('ended_at')->nullable()->after('started_at');
        });
    }

    public function down()
    {
        Schema::table('firm_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'ended_at']);
        });
    }
};
