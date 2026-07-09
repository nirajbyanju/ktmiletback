<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            // Nullable so NULL means "auto-compute from dates".
            // Admin can override by setting explicitly: upcoming | in_progress | completed
            $table->string('class_status', 20)->nullable()->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('class_status');
        });
    }
};
