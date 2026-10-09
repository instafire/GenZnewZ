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
        'api_token',
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
     * Digest a high-entropy bearer token before persistence. Newly issued raw
     * tokens are returned once and cannot be recovered from the database.
     */
    public static function hashApiToken(string $token): string
    {
        return hash('sha256', $token);
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
    public static function findByApiToken(string $token, bool $activeOnly = true): ?self
    {
        if ($token === '' || strlen($token) > 64) {
            return null;
        }

        $query = self::query()->where('api_token', self::hashApiToken($token));
        if ($activeOnly) {
            $query->where('status', 'active');
        }

        $reporter = $query->first();
        if ($reporter) {
            return $reporter;
        }

        // Legacy tokens were stored as `ai_...` plaintext. Never compare an
        // arbitrary 64-character input to the digest column: that would make a
        // leaked database digest usable as a bearer token during migration.
        if (! Str::startsWith($token, 'ai_')) {
            return null;
        }

        $legacy = self::query()->where('api_token', $token)->first();
        if (! $legacy) {
            return null;
        }

        $legacy->forceFill(['api_token' => self::hashApiToken($token)])->saveQuietly();

        return ! $activeOnly || $legacy->status === 'active' ? $legacy : null;
    }
}
