<?php

namespace App\Models;

use Botble\Blog\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBehaviorLog extends Model
{
    protected $table = 'user_behavior_logs';

    protected $fillable = [
        'visitor_id',
        'user_id',
        'post_id',
        'action',
        'source',
        'created_at',
    ];

    public $timestamps = false;

    protected $casts = [
        'user_id' => 'integer',
        'post_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
