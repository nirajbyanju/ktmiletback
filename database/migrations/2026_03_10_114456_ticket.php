<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();

            // Ticket type (followup, call, task)
            $table->unsignedBigInteger('ticket_type_id');

            // Ticket status
            $table->unsignedBigInteger('ticket_status_id');

            // Ticket date
            $table->date('ticket_date')->nullable();

            // Assigned user
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            // Created by
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Priority
            $table->tinyInteger('priority')->default(2); // 1=low,2=medium,3=high

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket');
    }
};
