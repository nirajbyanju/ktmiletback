<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Rolling batch model: which of the four skill modules
            // (listening / reading / speaking / writing) the student has completed.
            $table->json('modules_completed')->nullable()->after('attendance_percentage');
            // Flexible packages (Elite Private, Friends Private Group):
            // the student's requested date/time, free text.
            $table->string('preferred_schedule')->nullable()->after('modules_completed');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['modules_completed', 'preferred_schedule']);
        });
    }
};
