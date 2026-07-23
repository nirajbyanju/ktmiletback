<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            // Remove education fields no longer needed on the booking form
            $table->dropColumn(['education_level', 'pass_year']);

            // Add student context fields
            $table->string('country')->nullable()->after('phone');
            $table->string('preferred_at')->nullable()->after('country'); // "Preferred date & time" as free text
        });
    }

    public function down(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->dropColumn(['country', 'preferred_at']);
            $table->string('education_level')->default('');
            $table->string('pass_year')->nullable();
        });
    }
};
