<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the uuid column doesn't exist
        if (!Schema::hasColumn('failed_jobs', 'uuid')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->string('uuid')->nullable()->unique()->after('id');
            });

            // Generate UUIDs for existing records
            DB::table('failed_jobs')->whereNull('uuid')->cursor()->each(function ($failedJob) {
                DB::table('failed_jobs')
                    ->where('id', $failedJob->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

            // After populating, you might want to make it non-nullable
            // Uncomment the following if you want to enforce non-nullable after migration:
            // Schema::table('failed_jobs', function (Blueprint $table) {
            //     $table->string('uuid')->nullable(false)->change();
            // });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('failed_jobs', 'uuid')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            });
        }
    }
};
