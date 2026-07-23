<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('qualification', 200)->nullable()->after('available_time');
            $table->string('specialization', 100)->nullable()->after('qualification');
            $table->unsignedSmallInteger('experience_years')->nullable()->after('specialization');
            $table->text('bio')->nullable()->after('experience_years');
            $table->json('available_days')->nullable()->after('bio');
            $table->time('available_from')->nullable()->after('available_days');
            $table->time('available_to')->nullable()->after('available_from');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn([
                'qualification', 'specialization', 'experience_years',
                'bio', 'available_days', 'available_from', 'available_to',
            ]);
        });
    }
};
