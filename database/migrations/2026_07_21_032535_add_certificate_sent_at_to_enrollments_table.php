<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // When the completion certificate email was sent (null = not yet).
            // Prevents the scheduler from emailing the same student twice.
            $table->timestamp('certificate_sent_at')->nullable()->after('certificate_eligible');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('certificate_sent_at');
        });
    }
};
