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
        Schema::create('user_behavior_logs', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 64)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('post_id')->index();
            $table->string('action', 20);
            $table->string('source', 50)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['visitor_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['post_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['source', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_behavior_logs');
    }
};
