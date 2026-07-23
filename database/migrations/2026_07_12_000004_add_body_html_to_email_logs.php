<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            // Exact snapshot of the email as sent — templates may be edited
            // later, so the log keeps what the student actually received.
            $table->longText('body_html')->nullable()->after('subject');
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn('body_html');
        });
    }
};
