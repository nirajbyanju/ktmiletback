<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            // Set once the "1 hour after the demo" follow-up email has been sent,
            // so the scheduled command never emails the same demo twice.
            $table->timestamp('outcome_email_sent_at')->nullable()->after('read_at');
        });
    }

    public function down(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->dropColumn('outcome_email_sent_at');
        });
    }
};
