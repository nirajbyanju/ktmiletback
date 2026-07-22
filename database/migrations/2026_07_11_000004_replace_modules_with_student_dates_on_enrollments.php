<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Each student has their OWN course period (rolling batches):
            // completion is determined by end_date passing, not module ticks.
            $table->date('start_date')->nullable()->after('enrollment_date');
            $table->date('end_date')->nullable()->after('start_date');
            $table->dropColumn('modules_completed');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
            $table->json('modules_completed')->nullable();
        });
    }
};
