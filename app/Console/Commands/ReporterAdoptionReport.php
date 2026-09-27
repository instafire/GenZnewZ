<?php

namespace App\Console\Commands;

use App\Models\AIReporter;
use Botble\Blog\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Answers "are the agents we onboarded actually publishing?".
 *
 * Registration is cheap and automatic, so the registered count on its own says
 * nothing. This breaks the funnel into the steps that matter - registered,
 * authenticated, published - so a drop-off is visible instead of guessed at.
 */
class ReporterAdoptionReport extends Command
{
    protected $signature = 'ai-reporters:adoption
                            {--idle-days=30 : Treat a reporter with no activity for this long as idle}';

    protected $description = 'Report AI reporter adoption: registered, active and actually publishing';

    public function handle(): int
    {
        $idleDays = max(1, (int) $this->option('idle-days'));
        $idleCutoff = now()->subDays($idleDays);

        $total = AIReporter::query()->count();

        if ($total === 0) {
            $this->warn('No AI reporters have registered yet.');

            return self::SUCCESS;
        }

        $active = AIReporter::query()->where('status', 'active')->count();
        $authenticated = AIReporter::query()->whereNotNull('last_login_at')->count();
        $everPublished = AIReporter::query()->where('posts_count', '>', 0)->count();
        $neverPublished = $total - $everPublished;
        $idle = AIReporter::query()
            ->where(function ($query) use ($idleCutoff): void {
                $query->whereNull('last_login_at')->orWhere('last_login_at', '<', $idleCutoff);
            })
            ->count();

        $this->newLine();
        $this->line('<options=bold>AI reporter adoption</>');

        $this->table(
            ['Stage', 'Count', '% of registered'],
            [
                ['Registered', $total, '100.0%'],
                ['Active (approved)', $active, $this->pct($active, $total)],
                ['Authenticated at least once', $authenticated, $this->pct($authenticated, $total)],
                ['Ever published', $everPublished, $this->pct($everPublished, $total)],
                ['Registered but never authenticated', $total - $authenticated, $this->pct($total - $authenticated, $total)],
                ['Never published', $neverPublished, $this->pct($neverPublished, $total)],
                ['Idle ' . $idleDays . '+ days', $idle, $this->pct($idle, $total)],
            ]
        );

        $this->line('<options=bold>Publishing volume</>');

        $this->table(
            ['Window', 'Articles'],
            [
                ['Last 24 hours', $this->publishedSince(now()->subDay())],
                ['Last 7 days', $this->publishedSince(now()->subDays(7))],
                ['Last 30 days', $this->publishedSince(now()->subDays(30))],
                ['All time', Post::query()->wherePublished()->count()],
            ]
        );

        $recent = AIReporter::query()
            ->whereNotNull('last_login_at')
            ->orderByDesc('last_login_at')
            ->limit(10)
            ->get(['username', 'name', 'model_name', 'posts_count', 'last_login_at']);

        if ($recent->isNotEmpty()) {
            $this->line('<options=bold>Most recent activity</>');

            $this->table(
                ['Reporter', 'Name', 'Model', 'Posts', 'Last seen'],
                $recent->map(fn (AIReporter $reporter): array => [
                    $reporter->username,
                    $reporter->name,
                    $reporter->model_name ?: '-',
                    $reporter->posts_count,
                    Carbon::parse($reporter->last_login_at)->diffForHumans(),
                ])->all()
            );
        }

        $this->verdict($total, $neverPublished, $idle, $idleDays);

        return self::SUCCESS;
    }

    protected function publishedSince(\DateTimeInterface $since): int
    {
        return Post::query()->wherePublished()->where('created_at', '>=', $since)->count();
    }

    protected function pct(int $part, int $whole): string
    {
        return number_format(($part / max(1, $whole)) * 100, 1) . '%';
    }

    protected function verdict(int $total, int $neverPublished, int $idle, int $idleDays): void
    {
        $this->newLine();

        if ($neverPublished === $total) {
            $this->warn('No registered reporter has ever published. That is an onboarding problem, not a supply problem.');

            return;
        }

        if ($neverPublished > ($total / 2)) {
            $this->warn(sprintf(
                '%d of %d reporters have never published. The contract is being read but not acted on - check GET /status and the SEO quality gate.',
                $neverPublished,
                $total
            ));

            return;
        }

        if ($idle > ($total / 2)) {
            $this->warn(sprintf(
                '%d reporters have been idle for %d+ days. Adoption is decaying rather than failing.',
                $idle,
                $idleDays
            ));

            return;
        }

        $this->info('Adoption is healthy.');
    }
}
