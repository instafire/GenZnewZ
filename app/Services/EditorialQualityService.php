<?php

namespace App\Services;

use Botble\Blog\Models\Post;
use Illuminate\Support\Str;

class EditorialQualityService
{
    protected array $thinLowValuePatterns = [
        '/\bprompt cheat\b/i',
        '/\bsoft life\b/i',
        '/\bsmart life revolution\b/i',
        '/\bgaslighting\b/i',
        '/\bmachine learning is reshaping content creation\b/i',
        '/\bold api\b/i',
        '/\bcomparison testing\b/i',
        '/\bmorning routines\b/i',
    ];

    public function analyzeThinPost(Post $post): array
    {
        $title = (string) $post->name;
        $content = strip_tags((string) $post->content);
        $wordCount = str_word_count($content);
        $links = $this->extractLinks((string) $post->content);
        $externalLinks = $this->countExternalLinks($links);
        $reasons = [];

        if ($wordCount >= 120) {
            return [
                'should_unpublish' => false,
                'reasons' => [],
                'word_count' => $wordCount,
            ];
        }

        if ($title !== '' && $title === strtoupper($title) && preg_match('/[A-Z]/', $title)) {
            $reasons[] = 'all-caps title';
        }

        foreach ($this->thinLowValuePatterns as $pattern) {
            if (preg_match($pattern, $title . "\n" . $content)) {
                $reasons[] = 'matched low-value pattern ' . trim($pattern, '/i');
            }
        }

        if ($wordCount < 80 && $externalLinks === 0 && preg_match('/\b(guide|hacks|revolution|gaslighting|cheat|routine)\b/i', $title)) {
            $reasons[] = 'very thin evergreen/clickbait style post with no sourcing';
        }

        return [
            'should_unpublish' => $reasons !== [],
            'reasons' => array_values(array_unique($reasons)),
            'word_count' => $wordCount,
        ];
    }

    public function shortenTitle(string $title, int $maxLength = 68): string
    {
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = strtr($title, [
            '—' => ' - ',
            '–' => ' - ',
            '“' => '"',
            '”' => '"',
            '’' => "'",
            '‘' => "'",
            '⚡️' => '',
            '♻️' => '',
            '🌐' => '',
            '💰' => '',
            '✨' => '',
            '🔥' => '',
            '🤯' => '',
            '💻' => '',
            '🏭' => '',
            '🤖' => '',
            '📍' => '',
            '🖥️' => '',
            '📚' => '',
            '🔬' => '',
            '👑' => '',
            '🥁' => '',
            '🐎' => '',
            '🦞' => '',
        ]);

        $title = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $title);
        $title = preg_replace('/\s+/', ' ', trim((string) $title));

        $replacements = [
            '/^Are You a Main Character or Just Living in Their Shadow\? Decoding the "Main Character Syndrome" Trend$/i' => 'Main Character Syndrome Trend Explained',
            '/^Police to "Ensure Public Safety" at Christie Pits Park Anti-Immigration Rally & Counter-Demonstrations$/i' => 'Police Brace for Christie Pits Rally and Counter-Protests',
            '/^Jaguar Land Rover \(JLR\), the British luxury carmaker owned by Tata Motors, halted production globally$/i' => 'Jaguar Land Rover Halts Production Worldwide',
            '/^South Korea Asks Trump to Serve as "Peacemaker" with North Korea - A Renewed Diplomatic Overture$/i' => 'South Korea Asks Trump to Mediate with North Korea',
            '/^Top Brass Tussle: Hegseth\'s "Pep Rally" for a New "Department of War" Shakes Up the Pentagon$/i' => 'Hegseth "Department of War" Pitch Shakes the Pentagon',
            '/^This Week in Tech & Games: AI Took Over, New Console Teases, and a Game Just Got a Major Glow-Up$/i' => 'Tech & Games Weekly: AI, Console Teases, and a Major Update',
            '/^This Week in World & Society: Green Wins, Space Race Heats Up, and the Vibe Shift in Fashion$/i' => 'World & Society Weekly: Green Wins, Space Race, and Fashion',
            '/^Say Goodbye to Brittle Nails: Your Gen Z Guide to Growing Long, Strong Claws That Won\'t Break!?$/i' => 'How to Strengthen Brittle Nails',
            '/^OpenAI & Sur Energy Eye \\$25 Billion Data Center in Argentina: A Grand Vision for the Future$/i' => 'OpenAI and Sur Energy Eye USD 25B Data Center in Argentina',
            '/^Sports Are in Their Wild Era .*$/i' => 'Why Sports Are Back at the Center of Gen Z Culture',
            '/^Productivity Is Not a Personality Trait .*$/i' => 'Productivity Is Not a Personality Trait',
            '/^FLA WINS! The Libertadores Tea Is Spilled: Flamengo Snags the First Brazilian \'Four dPeat\' Title!$/i' => 'Flamengo Wins Historic Fourth Straight Libertadores Title',
            '/^Your Hair is Thirsty: The Ultimate Guide to Finessing Dry Hair for Main Character Energy$/i' => 'Dry Hair Guide: How to Restore Moisture',
            '/^Interactive Movies Are About to Take Over - Because Our Attention Spans Are Literally Fighting for Their Lives$/i' => 'Interactive Movies Are About to Take Over',
            '/^James Harden\'s 23-Point Cavaliers Debut: \'I\'ll Figure It Out\' as Cleveland Beats Kings 132-126$/i' => 'James Harden Scores 23 in Cavaliers Debut',
            '/^Nikola Jokic Records 182nd Triple-Double, Passes Oscar Robertson for 2nd All-Time: \'Absolutely Unbelievable\'$/i' => 'Nikola Jokic Passes Oscar Robertson with 182nd Triple-Double',
            '/^Patriots Complete 4-Win to Super Bowl Turnaround: \'15 Minutes\' of Celebration, Then Back to Work$/i' => 'Patriots Complete 4-Win Run to the Super Bowl',
            '/^Manchester United 2-0 Tottenham: Kobbie Mainoo\'s \'Pinpoint\' Passes Lead Fourth Straight Win Under Carrick$/i' => 'Manchester United Beat Tottenham 2-0 for Fourth Straight Win',
            '/^Shai Gilgeous-Alexander Out Through All-Star Break: Thunder MVP Faces 5-Game Absence with Abdominal Strain$/i' => 'Shai Gilgeous-Alexander Out Through All-Star Break',
            '/^Brazil\'s 2026 Carnival is Giving High-Key Main Character Energy & The Tech Tea is Scalding$/i' => 'Brazil\'s 2026 Carnival and the Technology Behind It',
            '/^OpenAI Just Hired the OpenClaw "Genius" & The Future of Personal Agents is Giving Major Proactive Energy$/i' => 'OpenAI Hires OpenClaw Talent to Build Personal Agents',
            '/^The Return of the Rash: Why Mpox 2026 is Hitting Different and How to Lock Down Your Health$/i' => 'Mpox 2026: What to Know About the Outbreak',
            '/^Forget Facelifts: These 8 Non-Surgical Treatments are the Secret to Flawless Skin in 2025$/i' => '8 Non-Surgical Treatments for Better Skin in 2025',
            '/^GenZNewz:\s*/i' => '',
            '/\bYour Complete Guide To\b/i' => 'Guide to',
            '/\bThe Ultimate Guide to\b/i' => 'Guide to',
            '/\bYour Ultimate Gen Z Guide to\b/i' => 'Guide to',
            '/\bYour Gen Z Guide to\b/i' => 'Guide to',
            '/\bWe All Need Right Now\b/i' => 'Worth Watching',
            '/\bRight Now\b/i' => '',
            '/\(You\'re Welcome, Gen Z\)/i' => '',
            '/\bWhat It Means for Gen Z\b/i' => '',
            '/\bAnd Our Jaws Are on the Floor\b/i' => '',
            '/\bIs Giving High-Key Main Character Energy\b/i' => '',
            '/\bSpills the Tea on\b/i' => 'Explains',
            '/\bWhat "Cool Fashion Girls" Don\'t Tell You\b/i' => 'Fashion Style Secrets',
            '/\bThis Week in Tech & Games\b/i' => 'Tech & Games Weekly',
            '/\bThis Week in World & Society\b/i' => 'World & Society Weekly',
            '/\bCrypto Chaos & Comebacks\b/i' => 'Crypto Weekly',
            '/\bfor Main Character Energy\b/i' => '',
            '/\bMain Character Energy\b/i' => '',
            '/\bNo Crumbs\b/i' => '',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $title = preg_replace($pattern, $replacement, $title);
        }

        $title = preg_replace('/\s+/', ' ', trim((string) $title, " -:!?.,"));

        if (mb_strlen($title) <= $maxLength) {
            return $title;
        }

        $parts = preg_split('/\s*(?: - |: |\? |! )\s*/u', $title) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts)));

        if ($parts !== []) {
            $best = $parts[0];

            if (count($parts) > 1) {
                $combined = $parts[0] . ': ' . $parts[1];
                if (mb_strlen($combined) <= $maxLength) {
                    $best = $combined;
                }
            }

            if (mb_strlen($best) <= $maxLength) {
                return $best;
            }
        }

        $words = preg_split('/\s+/', $title) ?: [];
        $dropWords = [
            'really', 'just', 'actually', 'very', 'major', 'ultimate', 'complete', 'hilarious',
            'insane', 'wild', 'guide', 'your', 'our', 'the', 'that', 'this', 'and', 'with',
        ];

        $filtered = [];
        foreach ($words as $word) {
            if (in_array(Str::lower(trim($word, "'\",.!?()")), $dropWords, true)) {
                continue;
            }
            $filtered[] = $word;
        }

        $candidate = trim(implode(' ', $filtered));
        if ($candidate !== '' && mb_strlen($candidate) <= $maxLength) {
            return $candidate;
        }

        $source = $candidate !== '' ? $candidate : $title;
        if (mb_strlen($source) <= $maxLength) {
            return $source;
        }

        $truncated = mb_substr($source, 0, $maxLength);
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > 40) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        return trim($truncated, " -:!?.,");
    }

    public function needsTitlePolish(string $title, int $maxLength = 68): bool
    {
        if (mb_strlen($title) > $maxLength) {
            return true;
        }

        if (preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $title)) {
            return true;
        }

        return str_contains($title, 'GenZNewz:') || str_contains($title, '—') || str_contains($title, '–');
    }

    protected function extractLinks(string $content): array
    {
        preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);

        return $matches[1] ?? [];
    }

    protected function countExternalLinks(array $links): int
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return count(array_filter($links, function (string $link) use ($host) {
            $linkHost = parse_url($link, PHP_URL_HOST);

            if ($linkHost === null) {
                return false;
            }

            return $host === null || strcasecmp($linkHost, (string) $host) !== 0;
        }));
    }
}
