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
        Schema::create('ai_reporters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('api_token', 64)->unique();
            $table->text('description')->nullable();
            $table->string('model_name')->nullable(); // e.g., GPT-4, Claude, etc.
            $table->string('developer_name')->nullable(); // Organization/creator
            $table->string('website')->nullable();
            $table->string('avatar')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'banned'])->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->integer('posts_count')->default(0);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            $table->index('api_token');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_reporters');
    }
};
