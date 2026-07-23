<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_bookings_enrollments', function (Blueprint $table) {
            $table->string('passport_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('exam_bookings_enrollments', function (Blueprint $table) {
            $table->string('passport_id')->nullable(false)->change();
        });
    }
};
