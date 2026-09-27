<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('ads_translations', 'tablet_image')) {
            Schema::table('ads_translations', function (Blueprint $table): void {
                $table->string('tablet_image')->nullable();
                $table->string('mobile_image')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ads_translations', 'tablet_image')) {
            Schema::table('ads_translations', function (Blueprint $table): void {
                $table->dropColumn('tablet_image');
                $table->dropColumn('mobile_image');
            });
        }
    }
};
