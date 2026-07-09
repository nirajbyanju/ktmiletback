<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            // NULL = no teacher assigned yet; explicit value = admin-assigned teacher
            $table->foreignId('teacher_id')
                  ->nullable()
                  ->after('admin_notes')
                  ->constrained('teachers')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Teacher::class);
            $table->dropColumn('teacher_id');
        });
    }
};
