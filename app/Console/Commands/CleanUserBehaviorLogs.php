<?php

namespace App\Console\Commands;

use App\Models\UserBehaviorLog;
use Illuminate\Console\Command;

class CleanUserBehaviorLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'behavior-logs:clean {--days=90 : Number of days to retain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove user behavior logs older than the retention period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->subDays(max(1, $days));

        $count = UserBehaviorLog::query()
            ->where('created_at', '<', $threshold)
            ->delete();

        $this->info("Deleted {$count} user behavior log records older than {$days} days.");

        return self::SUCCESS;
    }
}
