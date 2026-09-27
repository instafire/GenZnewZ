<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes safely (skip if exists)
        $this->addIndexSafely('posts', 'created_at');
        $this->addIndexSafely('posts', 'views');
        $this->addIndexSafely('posts', 'status');
        
        // For MySQL, we'll use raw SQL for compound indexes
        try {
            DB::statement('CREATE INDEX posts_format_featured_idx ON posts(format_type, is_featured)');
        } catch (\Exception $e) {
            // Index exists, skip
        }
        
        try {
            DB::statement('CREATE INDEX posts_author_idx ON posts(author_type(191), author_id)');
        } catch (\Exception $e) {
            // Index exists, skip
        }
        
        try {
            DB::statement('CREATE INDEX slugs_reference_idx ON slugs(reference_type(191), reference_id)');
        } catch (\Exception $e) {
            // Index exists, skip
        }
        
        $this->addIndexSafely('slugs', 'key');
    }

    public function down(): void
    {
        // Drop indexes safely
        $this->dropIndexSafely('posts', 'posts_created_at_index');
        $this->dropIndexSafely('posts', 'posts_views_index');
        $this->dropIndexSafely('posts', 'posts_status_index');
        
        try {
            DB::statement('DROP INDEX posts_format_featured_idx ON posts');
        } catch (\Exception $e) {}
        
        try {
            DB::statement('DROP INDEX posts_author_idx ON posts');
        } catch (\Exception $e) {}
        
        try {
            DB::statement('DROP INDEX slugs_reference_idx ON slugs');
        } catch (\Exception $e) {}
        
        $this->dropIndexSafely('slugs', 'slugs_key_index');
    }
    
    protected function addIndexSafely(string $table, string $column): void
    {
        try {
            DB::statement("CREATE INDEX {$table}_{$column}_index ON {$table}({$column})");
        } catch (\Exception $e) {
            // Index exists, skip
        }
    }
    
    protected function dropIndexSafely(string $table, string $index): void
    {
        try {
            DB::statement("DROP INDEX {$index} ON {$table}");
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }
    }
};
