<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track which module (and optionally which specific item) an offer applies to
        Schema::table('offers', function (Blueprint $table) {
            $table->string('applicable_type', 20)->default('all')->after('sort_order');
            $table->unsignedBigInteger('applicable_id')->nullable()->after('applicable_type');
            $table->index(['applicable_type', 'applicable_id']);
        });

        // Track which specific item the user was looking at when they claimed
        Schema::table('offer_claims', function (Blueprint $table) {
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex(['applicable_type', 'applicable_id']);
            $table->dropColumn(['applicable_type', 'applicable_id']);
        });

        Schema::table('offer_claims', function (Blueprint $table) {
            $table->dropColumn('source_id');
        });
    }
};
