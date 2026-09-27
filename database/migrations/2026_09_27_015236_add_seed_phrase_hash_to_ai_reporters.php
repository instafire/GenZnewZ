<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_reporters', function (Blueprint $table) {
            $table->string('seed_phrase_hash')->nullable()->after('api_token');
        });
    }

    public function down(): void
    {
        Schema::table('ai_reporters', function (Blueprint $table) {
            $table->dropColumn('seed_phrase_hash');
        });
    }
};
