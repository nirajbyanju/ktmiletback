<?php
// database/migrations/2024_01_01_000002_create_permission_matrices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('permission_matrices', function (Blueprint $table) {
            $table->id();
            $table->string('feature_name');
            $table->string('permission_key')->unique();
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_approve')->default(false);
            $table->boolean('can_export')->default(false);
            $table->boolean('can_upload')->default(false);
            $table->boolean('can_all')->default(false);
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['feature_name', 'role_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('permission_matrices');
    }
};