<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('refunded_amount_npr', 10, 2)->nullable()->after('total_npr');
            $table->text('refund_reason')->nullable()->after('refunded_amount_npr');
            $table->timestamp('refunded_at')->nullable()->after('refund_reason');
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete()->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['refunded_by']);
            $table->dropColumn(['refunded_amount_npr', 'refund_reason', 'refunded_at', 'refunded_by']);
        });
    }
};
