<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track which form page the offer was claimed from
        Schema::table('offer_claims', function (Blueprint $table) {
            $table->string('source_type', 20)->nullable()->after('offer_claim_date');
            $table->timestamp('used_at')->nullable()->after('source_type');
        });

        // Link the invoice to the offer claim that was applied
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('offer_claim_id')
                  ->nullable()
                  ->after('exam_booking_enrollment_id')
                  ->constrained('offer_claims')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offer_claim_id');
        });

        Schema::table('offer_claims', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'used_at']);
        });
    }
};
