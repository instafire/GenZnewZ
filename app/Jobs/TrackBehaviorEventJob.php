<?php

namespace App\Jobs;

use App\Models\UserBehaviorLog;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class TrackBehaviorEventJob
{
    use Dispatchable;
    use Queueable;

    /**
     * Number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Indicates whether the job should be deleted when missing models.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    public function __construct(
        protected string $visitorId,
        protected ?int $userId,
        protected int $postId,
        protected string $action,
        protected ?string $source
    ) {
    }

    public function handle(): void
    {
        UserBehaviorLog::create([
            'visitor_id' => $this->visitorId,
            'user_id' => $this->userId,
            'post_id' => $this->postId,
            'action' => $this->action,
            'source' => $this->source,
            'created_at' => now(),
        ]);
    }
}
