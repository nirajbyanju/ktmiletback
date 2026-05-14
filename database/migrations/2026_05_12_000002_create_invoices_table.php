<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->decimal('subtotal_npr', 10, 2);
            $table->decimal('discount_npr', 10, 2)->default(0);
            $table->decimal('tax_npr', 10, 2)->default(0);
            $table->decimal('total_npr', 10, 2);
            $table->string('status', 30)->default('unpaid');
            $table->string('payment_method', 50)->default('bank_qr');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->after('batch_id')->constrained('invoices')->nullOnDelete();
            $table->string('status', 30)->default('active')->after('amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('invoice_id');
            $table->dropColumn('status');
        });

        Schema::dropIfExists('invoices');
    }
};
