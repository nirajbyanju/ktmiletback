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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('property_id')->nullable();
            $table->string('from'); //  1 - request, 2 - inquiry
            $table->unsignedInteger('inquiry_type_id'); // 1 - buy, 2 - sell
            $table->unsignedInteger('property_type_id')->nullable(); // 1 - house, 2 - land, 3 - commercial

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();


            $table->string('budget')->nullable();

            $table->text('location')->nullable();



            $table->text('message')->nullable();
            $table->longText('description')->nullable();
            $table->string('response_type_id')->nullable();
            $table->text('reason')->nullable();
            $table->status();
            $table->userAuditable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
