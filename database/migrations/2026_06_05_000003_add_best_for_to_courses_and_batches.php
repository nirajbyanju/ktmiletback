<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->text('best_for')->nullable()->after('description');
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->text('best_for')->nullable()->after('batch_type');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('best_for');
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('best_for');
        });
    }
};
