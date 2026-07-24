<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('initials', 10);
            $table->string('name', 100);
            $table->string('meta', 150);
            $table->string('tag', 100);
            $table->string('country', 100);
            $table->text('quote');
            $table->json('cats');
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('photo')->nullable();
            $table->userAuditable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
