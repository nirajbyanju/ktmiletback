<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BUG-02: testimonials.initials — make nullable (backend now derives from name).
 * BUG-03: exam_bookings.discount — add default 0 so null submissions don't fail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('initials', 10)->nullable()->default(null)->change();
        });

        Schema::table('exam_bookings', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->default(0)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('initials', 10)->nullable(false)->change();
        });

        Schema::table('exam_bookings', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->nullable(false)->change();
        });
    }
};
