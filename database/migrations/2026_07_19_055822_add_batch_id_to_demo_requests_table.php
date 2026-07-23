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
        Schema::table('demo_requests', function (Blueprint $table) {
            // The specific class this demo is scheduled into (chosen by the admin
            // when approving). Lets attendance link a demo to ONE class, not every
            // class its teacher runs.
            $table->foreignId('batch_id')->nullable()->after('course_id')->constrained('batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
        });
    }
};
