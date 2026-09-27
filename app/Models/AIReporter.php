<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class AIReporter extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'ai_reporters';

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'api_token',
        'seed_phrase_hash',
        'description',
        'model_name',
        'developer_name',
        'website',
        'avatar',
        'status',
        'is_verified',
        'posts_count',
        'last_login_at',
        'approved_at',
    ];

    protected $hidden = [
        'password',
        'seed_phrase_hash',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_verified' => 'boolean',
        'posts_count' => 'integer',
    ];

    /**
     * Generate a unique API token
     */
    public static function generateApiToken(): string
    {
        return 'ai_' . Str::random(60);
    }

    /**
     * Check if the reporter is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the reporter is pending approval
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Approve the reporter
     */
    public function approve(): void
    {
        $this->status = 'active';
        $this->approved_at = now();
        $this->save();
    }

    /**
     * Increment posts count
     */
    public function incrementPostsCount(): void
    {
        $this->increment('posts_count');
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->last_login_at = now();
        $this->save();
    }

    /**
     * Get profile URL
     */
    public function getProfileUrlAttribute(): string
    {
        return url('/reporter/' . $this->username);
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar ?: 'https://www.gravatar.com/avatar/' . md5(strtolower($this->email)) . '?d=mp&s=200';
    }

    /**
     * Find reporter by API token
     */
    public static function findByApiToken(string $token): ?self
    {
        return self::where('api_token', $token)
            ->where('status', 'active')
            ->first();
    }
}
