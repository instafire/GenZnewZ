<?php

namespace App\Services;

use App\Services\HomepageDataService;
use Botble\Base\Facades\MetaBox;
use Botble\Blog\Models\Post;
use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PexelsImageService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.pexels.com/v1';
    protected string $rateLimitCacheKey = 'pexels_rate_limit_cooldown';

    /**
     * Topic-to-visual mapping: translates abstract article subjects into
     * concrete, visually searchable terms that Pexels understands well.
     * Keys are matched against the cleaned title words.
     */
    protected array $topicMap = [
        // Sports
        'super bowl'        => 'american football stadium crowd',
        'nfl'               => 'american football game',
        'nba'               => 'basketball game arena',
        'premier league'    => 'soccer football match stadium',
        'la liga'           => 'soccer football stadium',
        'champions league'  => 'soccer football stadium night',
        'cricket'           => 'cricket sport match',
        'olympics'          => 'olympic games athlete',
        'olympia'           => 'olympic games athlete',
        'winter olympics'   => 'winter sports snow athlete',
        'skiing'            => 'skiing snow mountain sport',
        'snowboard'         => 'snowboarding winter sport',
        'golf'              => 'golf course sport',
        'tennis'            => 'tennis court match',
        'boxing'            => 'boxing ring fight',
        'mma'               => 'mixed martial arts fight',
        'ufc'               => 'mixed martial arts fight',
        'nrl'               => 'rugby league football sport',
        'rugby'             => 'rugby sport match',
        'f1'                => 'formula one racing car',
        'world cup'         => 'soccer football world cup stadium',
        'halftime show'     => 'concert stage performance lights',

        // Crypto / Finance
        'bitcoin'           => 'bitcoin cryptocurrency digital',
        'btc'               => 'bitcoin cryptocurrency',
        'ethereum'          => 'cryptocurrency blockchain technology',
        'eth'               => 'cryptocurrency blockchain',
        'crypto'            => 'cryptocurrency trading digital finance',
        'xrp'               => 'cryptocurrency trading chart',
        'solana'            => 'cryptocurrency blockchain network',
        'sol'               => 'cryptocurrency blockchain',
        'dogecoin'          => 'cryptocurrency meme coin digital',
        'doge'              => 'cryptocurrency meme coin',
        'cardano'           => 'cryptocurrency blockchain',
        'ada'               => 'cryptocurrency blockchain',
        'chainlink'         => 'blockchain network technology',
        'link'              => 'blockchain technology',
        'defi'              => 'decentralized finance technology',
        'nft'               => 'digital art technology',
        'meme coin'         => 'cryptocurrency trading chart',
        'stock market'      => 'stock market trading screen',
        'trade setup'       => 'stock market trading chart screen',
        'pump'              => 'cryptocurrency trading chart green',
        // Tech
        'openai'            => 'artificial intelligence technology robot',
        'chatgpt'           => 'artificial intelligence chatbot computer',
        'ai'                => 'artificial intelligence technology',
        'tiktok'            => 'smartphone social media video',
        'instagram'         => 'smartphone social media photography',
        'meta'              => 'social media technology smartphone',
        'metamask'          => 'cryptocurrency wallet smartphone payment card',
        'debit card'        => 'payment card fintech digital wallet',
        'payment card'      => 'payment card fintech digital wallet',
        'teen safety'       => 'teen smartphone parent online safety',
        'parental controls' => 'parent child smartphone safety',
        'parents'           => 'parent teenager smartphone family',
        'twitch'            => 'gaming live streaming setup',
        'youtube'           => 'video content creation camera',
        'spotify'           => 'music headphones streaming',
        'apple'             => 'technology gadget modern',
        'tesla'             => 'electric car automotive',
        'rivian'            => 'electric vehicle suv',
        'deepfake'          => 'digital face technology screen',
        'cybersecurity'     => 'computer security hacking screen',
        'smartphone'        => 'smartphone technology modern',
        'robot'             => 'robot technology future',
        'gaming'            => 'gaming computer esports',
        'streaming'         => 'streaming video entertainment screen',
        'viewbot'           => 'computer streaming fake screen',
        'web3'              => 'blockchain technology digital',

        // Politics / World
        'trump'             => 'united states politics white house',
        'biden'             => 'united states politics government',
        'macron'            => 'france politics european government',
        'election'          => 'voting election democracy ballot',
        'parliament'        => 'parliament government politics building',
        'congress'          => 'united states congress government',
        'protest'           => 'protest rally crowd demonstration',
        'war'               => 'military conflict world map',
        'military'          => 'military defense soldiers',
        'iran'              => 'middle east diplomacy world politics',
        'russia'            => 'russia politics kremlin',
        'china'             => 'china skyline city modern',
        'ukraine'           => 'ukraine flag solidarity',
        'eu'                => 'european union flags brussels',
        'nato'              => 'military alliance defense',
        'sanctions'         => 'international politics diplomacy',
        'diplomacy'         => 'diplomacy handshake government',
        'immigration'       => 'immigration border people',
        'refugee'           => 'refugee humanitarian crisis',
        'bomb'              => 'explosion conflict destruction',
        'mosque'            => 'mosque islamic architecture',
        // Weather / Disasters
        'fire'              => 'wildfire fire smoke',
        'flood'             => 'flooding water disaster',
        'earthquake'        => 'earthquake destruction rubble',
        'hurricane'         => 'hurricane storm weather',
        'tornado'           => 'tornado storm weather',
        'cold warning'      => 'extreme cold winter frozen ice',
        'heatwave'          => 'hot summer sun heat',
        'drought'           => 'dry cracked earth drought',
        'smoke'             => 'wildfire smoke haze city',

        // Culture / Entertainment
        'fashion'           => 'fashion runway model clothing',
        'streetwear'        => 'street fashion urban style clothing',
        'sneakers'          => 'sneakers shoes fashion footwear',
        'luxury'            => 'luxury fashion designer clothing',
        'music'             => 'music concert performance stage',
        'album'             => 'music vinyl record studio',
        'concert'           => 'concert live music stage crowd',
        'tour'              => 'concert tour music stage',
        'movie'             => 'cinema movie film',
        'film'              => 'cinema movie film camera',
        'tv show'           => 'television entertainment screen',
        'netflix'           => 'streaming entertainment screen night',
        'bbc'               => 'television media broadcast',
        'rapper'            => 'rapper hip hop music performance',
        'singer'            => 'singer music performance microphone',
        'band'              => 'music band performance stage',

        // Health / Lifestyle
        'mental health'     => 'mental health wellness mindfulness',
        'anxiety'           => 'mental health stress wellness',
        'sleep'             => 'sleep bedroom rest peaceful',
        'tired'             => 'fatigue tired coffee exhaustion',
        'morning routine'   => 'morning sunrise routine coffee',
        'workout'           => 'fitness gym workout exercise',
        'diet'              => 'healthy food nutrition diet',
        'hobby'             => 'creative hobby leisure craft',
        'friends'           => 'friendship people socializing group',
        'doom scrolling'    => 'smartphone screen night phone addiction',
        'side hustle'       => 'laptop working freelance business',
        'degree'            => 'university graduation education',
        'career'            => 'office work career professional',

        // Gen Z specific
        'gen z'             => 'young people diverse generation',
        'millennial'        => 'young adults people diverse',
        'viral'             => 'smartphone social media trending',
        'meme'              => 'internet culture social media funny',
        'aesthetic'         => 'aesthetic lifestyle design beautiful',
        'cottagecore'       => 'cottage countryside nature flowers pastoral',
        'quiet quitting'    => 'office work balance leaving',
        'bed rotting'       => 'cozy bed relaxing bedroom comfort',
        'soft life'         => 'peaceful lifestyle luxury comfort',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.pexels.api_key', env('PEXELS_API_KEY', ''));
    }

    public function isCoolingDown(): bool
    {
        return (int) Cache::get($this->rateLimitCacheKey, 0) > time();
    }

    /**
     * Search Pexels for a photo.
     * 
     * @param string $query Search query
     * @param string $orientation Image orientation (landscape, portrait, square)
     * @param int $perPage Number of results to fetch
     * @param int $page Page number for pagination (allows getting different results)
     * @return ?array
     */
    public function searchPhoto(string $query, string $orientation = 'landscape', int $perPage = 5, int $page = 1): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Pexels: API key not configured');
            return null;
        }

        $cooldownUntil = (int) Cache::get($this->rateLimitCacheKey, 0);
        if ($cooldownUntil > time()) {
            return null;
        }

        // Include page in cache key to allow different results
        $cacheKey = 'pexels_v3_' . md5($query . $orientation . $perPage . $page);

        return Cache::remember($cacheKey, 1800, function () use ($query, $orientation, $perPage, $page) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $this->apiKey,
                ])->get($this->baseUrl . '/search', [
                    'query' => $query,
                    'orientation' => $orientation,
                    'per_page' => $perPage,
                    'page' => $page,
                    'size' => 'large',
                ]);

                if ($response->failed()) {
                    if ($response->status() === 429) {
                        Cache::put($this->rateLimitCacheKey, time() + 300, 300);
                        Log::warning('Pexels: Rate limit reached, entering cooldown', [
                            'query' => $query,
                        ]);

                        return null;
                    }

                    Log::error('Pexels: API request failed', [
                        'status' => $response->status(),
                        'query' => $query,
                    ]);
                    return null;
                }

                $data = $response->json();

                if (empty($data['photos'])) {
                    return null;
                }

                // Return all photos so caller can choose
                return [
                    'photos' => array_map(function($photo) {
                        return [
                            'id' => $photo['id'],
                            'url' => $photo['src']['large2x'] ?? $photo['src']['large'],
                            'original_url' => $photo['src']['original'],
                            'photographer' => $photo['photographer'],
                            'photographer_url' => $photo['photographer_url'],
                            'pexels_url' => $photo['url'],
                            'alt' => $photo['alt'] ?? '',
                        ];
                    }, $data['photos']),
                    'total_results' => $data['total_results'] ?? 0,
                ];
            } catch (\Exception $e) {
                Log::error('Pexels: Exception during search', [
                    'message' => $e->getMessage(),
                    'query' => $query,
                ]);
                return null;
            }
        });
    }

    /**
     * Build the best possible Pexels search query from article metadata.
     *
     * Strategy:
     * 1. Use custom AI-provided query if available
     * 2. Check title against topic map for known visual translations
     * 3. Extract the core subject nouns from title
     * 4. Use description for additional context clues
     * 5. Use category as a visual domain hint
     */
    public function buildSearchQuery(
        string $title,
        ?string $description = null,
        ?string $category = null,
        ?string $customQuery = null,
        ?string $visualDescription = null,
        ?string $focusKeyword = null
    ): array
    {
        $queries = [];

        // PRIORITY 1: Use custom query from AI agent if provided
        if (!empty($customQuery)) {
            $cleanCustomQuery = $this->cleanText($customQuery);
            if ($cleanCustomQuery !== '' && !$this->isGenericVisualHint($cleanCustomQuery)) {
                $queries[] = $cleanCustomQuery;
            }

            if (!empty($visualDescription)) {
                $visualHint = $this->extractSubjectKeywords($this->cleanText($visualDescription), 3);
                if ($visualHint) {
                    $queries[] = trim($cleanCustomQuery . ' ' . $visualHint);
                }
            }
        }

        $titleLower = $this->cleanText($title);
        $focusLower = $this->cleanText((string) $focusKeyword);

        if ($focusLower !== '') {
            $focusTopicQuery = $this->matchTopicMap($focusLower);
            if ($focusTopicQuery) {
                $queries[] = $focusTopicQuery;
            }

            $focusKeywords = $this->extractSubjectKeywords($focusLower, 4);
            if ($focusKeywords) {
                $queries[] = $focusKeywords;
            }
        }

        // PRIORITY 2: Check topic map (multi-word matches first, then single)
        $topicQuery = $this->matchTopicMap(trim($titleLower . ' ' . $focusLower));
        if ($topicQuery) {
            $queries[] = $topicQuery;
        }

        // PRIORITY 3: Extract core subject keywords from title
        $subjectKeywords = $this->extractSubjectKeywords($titleLower);
        if ($subjectKeywords) {
            $queries[] = $subjectKeywords;
        }

        // PRIORITY 4: Subject keywords + category
        $categoryVisual = $this->mapCategoryToVisual($category);
        if ($subjectKeywords && $categoryVisual) {
            $queries[] = trim($subjectKeywords . ' ' . $categoryVisual);
        }

        if ($visualDescription) {
            $visualHint = $this->extractSubjectKeywords($this->cleanText($visualDescription), 4);
            if ($visualHint) {
                $queries[] = $visualHint;

                if ($categoryVisual) {
                    $queries[] = trim($visualHint . ' ' . $categoryVisual);
                }
            }
        }

        // PRIORITY 5: Description keywords + category
        if ($description) {
            $descLower = $this->cleanText($description);
            $descriptionHint = $this->extractSubjectKeywords($descLower, 2);
            if ($descriptionHint && $categoryVisual) {
                $queries[] = trim($descriptionHint . ' ' . $categoryVisual);
            }
        }

        // LAST RESORT: Just the category visual
        if ($categoryVisual) {
            $queries[] = $categoryVisual;
        }

        return array_slice(array_values(array_unique(array_filter($queries))), 0, 5);
    }

    public function prepareImageGuidance(
        string $title,
        ?string $description = null,
        ?string $category = null,
        ?string $focusKeyword = null,
        ?string $customQuery = null,
        ?string $visualDescription = null
    ): array {
        $cleanCustomQuery = $this->cleanText((string) $customQuery);
        $cleanVisualDescription = $this->cleanText((string) $visualDescription);
        $cleanFocusKeyword = $this->cleanText((string) $focusKeyword);
        $titleLower = $this->cleanText($title);
        $descriptionLower = $this->cleanText((string) $description);
        $categoryVisual = $this->mapCategoryToVisual($category);

        $generatedSearchQuery = $cleanCustomQuery;
        if ($generatedSearchQuery === '' || $this->isGenericVisualHint($generatedSearchQuery)) {
            $generatedSearchQuery = $this->matchTopicMap(trim(implode(' ', array_filter([
                $cleanFocusKeyword,
                $titleLower,
                $descriptionLower,
            ]))));
        }

        if (!$generatedSearchQuery) {
            $generatedSearchQuery = $this->extractSubjectKeywords(trim(implode(' ', array_filter([
                $cleanFocusKeyword,
                $titleLower,
            ]))), 4);
        }

        if (!$generatedSearchQuery && $categoryVisual) {
            $generatedSearchQuery = $categoryVisual;
        }

        if ($cleanVisualDescription === '' || $this->isGenericVisualHint($cleanVisualDescription)) {
            $cleanVisualDescription = $this->buildVisualDescription(
                $titleLower,
                $descriptionLower,
                $categoryVisual,
                $cleanFocusKeyword,
                $generatedSearchQuery
            );
        }

        $queries = $this->buildSearchQuery(
            $title,
            $description,
            $category,
            $generatedSearchQuery,
            $cleanVisualDescription,
            $focusKeyword
        );

        return [
            'search_query' => $generatedSearchQuery,
            'visual_description' => $cleanVisualDescription,
            'queries' => $queries,
            'context' => trim(implode(' ', array_filter([
                $generatedSearchQuery,
                $cleanVisualDescription,
                $categoryVisual,
                $this->extractSubjectKeywords($descriptionLower, 4),
            ]))),
        ];
    }

    /**
     * Score how well a candidate photo matches article context.
     */
    protected function scoreCandidate(array $candidate, string $context): float
    {
        $alt = $this->cleanText((string) ($candidate['alt'] ?? ''));
        if ($alt === '') {
            return 0.0;
        }

        $contextTokens = $this->keywordSet($context);
        $altTokens = $this->keywordSet($alt);

        if (empty($contextTokens) || empty($altTokens)) {
            return 0.0;
        }

        $overlap = array_intersect($contextTokens, $altTokens);
        $score = count($overlap) / max(1, count($contextTokens));

        // Strong boost when the alt contains multi-word phrase from context
        if (str_contains($alt, $context)) {
            $score += 0.35;
        }

        return $score;
    }

    protected function keywordSet(string $text): array
    {
        $clean = $this->cleanText($text);
        if ($clean === '') {
            return [];
        }

        $tokens = preg_split('/\s+/', $clean);
        $tokens = array_values(array_filter($tokens, fn($t) => strlen($t) >= 3));

        return array_values(array_unique($tokens));
    }

    /**
     * Match title against the topic map. Checks multi-word keys first.
     */
    protected function matchTopicMap(string $titleLower): ?string
    {
        $keys = array_keys($this->topicMap);
        usort($keys, fn($a, $b) => strlen($b) - strlen($a));

        foreach ($keys as $key) {
            if (str_contains($titleLower, $key)) {
                return $this->topicMap[$key];
            }
        }

        return null;
    }

    /**
     * Extract the most meaningful subject nouns from text.
     */
    protected function extractSubjectKeywords(string $text, int $maxWords = 3): string
    {
        $stopWords = [
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            'of', 'with', 'by', 'from', 'is', 'are', 'was', 'were', 'be', 'been',
            'has', 'have', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
            'should', 'may', 'might', 'can', 'this', 'that', 'these', 'those',
            'it', 'its', 'how', 'what', 'when', 'where', 'who', 'which', 'why',
            'not', 'no', 'just', 'about', 'up', 'out', 'so', 'if', 'than',
            'into', 'over', 'after', 'before', 'between', 'through',
            'here', 'there', 'your', 'you', 'our', 'we', 'they', 'them',
            'breaking', 'update', 'latest', 'now', 'today', 'report', 'reports',
            'says', 'said', 'according', 'amid', 'vs', 'more', 'also', 'still',
            'new', 'top', 'best', 'most', 'all', 'every', 'each',
            'announces', 'announced', 'reveals', 'revealed', 'faces', 'facing',
            'hits', 'gets', 'goes', 'going', 'makes', 'made', 'takes', 'taken',
            'comes', 'coming', 'finds', 'found', 'shows', 'shown', 'keeps',
            'major', 'big', 'huge', 'massive', 'key', 'full', 'complete',
            'historic', 'dramatic', 'shocking', 'controversial', 'exclusive',
            'inside', 'actually', 'really', 'truly', 'totally', 'literally',
            'official', 'results', 'guide', 'setup', 'alert', 'warning',
            'threatens', 'urges', 'demands', 'blocks', 'orders', 'accuse',
            'accuses', 'claims', 'denies', 'challenges', 'defeats', 'wins',
            'loses', 'breaks', 'records', 'shocks', 'pumps', 'pumping',
            'drops', 'dropped', 'falls', 'falling', 'rises', 'rising',
            'growing', 'grows', 'heats', 'heating', 'escalate', 'escalates',
            'dominates', 'targets', 'returns', 'ends', 'begins', 'starts',
            'rugged', 'banned', 'delayed', 'cancelled', 'scrapped',
            'early', 'perfect', 'real', 'dead', 'secret', 'quiet',
            'peak', 'tired', 'awesome', 'horror', 'dangerous',
        ];

        $words = preg_split('/\s+/', $text);
        $keywords = [];

        foreach ($words as $word) {
            $clean = preg_replace('/[^a-z]/', '', $word);
            if (strlen($clean) < 3) continue;
            if (in_array($clean, $stopWords)) continue;
            if (preg_match('/\d/', $word)) continue;
            $keywords[] = $clean;
        }

        $keywords = array_unique($keywords);
        $keywords = array_slice(array_values($keywords), 0, $maxWords);

        return implode(' ', $keywords);
    }

    protected function isGenericVisualHint(string $text): bool
    {
        if ($text === '') {
            return true;
        }

        return preg_match('/\b(image|photo|picture|featured image|news image|article image|relevant image|cover image|thumbnail|breaking news)\b/i', $text) === 1;
    }

    protected function buildVisualDescription(
        string $titleLower,
        string $descriptionLower,
        ?string $categoryVisual,
        string $focusKeywordLower,
        ?string $searchQuery
    ): string {
        $parts = array_filter([
            $this->matchTopicMap(trim(implode(' ', array_filter([$focusKeywordLower, $titleLower, $descriptionLower])))),
            $searchQuery,
            $categoryVisual,
            $this->extractSubjectKeywords($descriptionLower, 4),
        ]);

        $description = implode(' ', array_values(array_unique($parts)));

        return trim(Str::limit($description, 220, ''));
    }

    /**
     * Map category names to visual search terms.
     */
    protected function mapCategoryToVisual(?string $category): ?string
    {
        if (!$category) return null;

        $map = [
            'AI News'       => 'artificial intelligence technology',
            'Tech & Games'  => 'technology',
            'Sports'        => 'sport',
            'Culture'       => 'culture lifestyle',
            'Climate Emergency' => 'climate environment crisis',
            'The World'     => 'world news globe',
            'Politics'      => 'government politics',
            'Mind & Body'   => 'health wellness',
            'Music'         => 'music',
            'Fashion'       => 'fashion style',
            'Finance'       => 'finance business',
            'Crypto'        => 'cryptocurrency',
            'Entertainment' => 'entertainment',
            'Science'       => 'science research',
            'Science & Space' => 'science space research',
            'Programming'   => 'software code computer',
            'Education'     => 'education university',
            'Travel'        => 'travel destination',
            'Food'          => 'food cooking',
            'Business'      => 'business office',
        ];

        return $map[$category] ?? strtolower($category);
    }

    /**
     * Clean text: lowercase, strip emojis, special characters, numbers.
     */
    protected function cleanText(string $text): string
    {
        $text = preg_replace('/[\x{1F000}-\x{1F9FF}]|[\x{2600}-\x{27BF}]|[\x{FE00}-\x{FEFF}]/u', '', $text);
        $text = preg_replace('/[^a-zA-Z\s]/', ' ', strtolower($text));
        $text = preg_replace('/\s+/', ' ', trim($text));
        return $text;
    }

    /**
     * Fetch a Pexels image and upload it to Botble's media library.
     * Now supports custom search queries from AI agents.
     * Uses pagination to find unique images.
     * 
     * @param string $title Article title
     * @param string|null $description Article description
     * @param string|null $category Article category
     * @param string|null $customQuery Custom search query from AI agent
     * @return ?array
     */
    public function fetchAndUploadImage(
        string $title,
        ?string $description = null,
        ?string $category = null,
        ?string $customQuery = null,
        ?string $visualDescription = null,
        ?string $focusKeyword = null
    ): ?array {
        $guidance = $this->prepareImageGuidance(
            $title,
            $description,
            $category,
            $focusKeyword,
            $customQuery,
            $visualDescription
        );

        $queries = $guidance['queries'];
        $customQuery = $guidance['search_query'] ?: $customQuery;
        $visualDescription = $guidance['visual_description'] ?: $visualDescription;

        if (empty($queries)) {
            Log::warning('Pexels: Could not build any search query', ['title' => $title]);
            return null;
        }

        // Get all existing photo IDs to avoid duplicates
        $existingPhotoIds = \DB::table('meta_boxes')
            ->where('reference_type', 'Botble\Blog\Models\Post')
            ->where('meta_key', 'pexels_photo_id')
            ->pluck('meta_value')
            ->flatMap(fn ($value) => $this->extractStoredPhotoIds($value))
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->toArray();

        // Try each query with pagination and relevance scoring.
        $photo = null;
        $usedQuery = '';
        $bestScore = 0.0;
        $usedCustomQueryRanking = false;
        $context = trim($this->cleanText($guidance['context'] ?? implode(' ', array_filter([$customQuery, $visualDescription, $title, $description, $category]))));
        
        foreach ($queries as $queryIndex => $query) {
            // Try multiple pages to find a unique image
            for ($page = 1; $page <= 3; $page++) {
                $result = $this->searchPhoto($query, 'landscape', 10, $page);
                
                if (!$result || empty($result['photos'])) {
                    break;
                }
                
                // Pick the best scoring unique candidate on this page.
                foreach ($result['photos'] as $candidate) {
                    if (in_array((string) $candidate['id'], $existingPhotoIds, true)) {
                        continue;
                    }

                    $score = $this->scoreCandidate($candidate, $context);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $photo = $candidate;
                        $usedQuery = $query;
                    }
                }

                if (!$photo && $queryIndex === 0 && !empty($customQuery)) {
                    foreach ($result['photos'] as $candidate) {
                        if (!in_array((string) $candidate['id'], $existingPhotoIds, true)) {
                            $photo = $candidate;
                            $usedQuery = $query . ' (custom-query ranked)';
                            $usedCustomQueryRanking = true;
                            break;
                        }
                    }
                }

                if ($bestScore >= 0.28) {
                    break 2;
                }
                
                usleep(100000); // Small delay between pages
            }
        }

        // Relevance threshold. If we found a weak match, try category visual fallback.
        if ($photo && $bestScore < 0.12 && $category && !$usedCustomQueryRanking) {
            $fallback = $this->searchPhoto($this->mapCategoryToVisual($category) ?? $category, 'landscape', 10, 1);
            if ($fallback && !empty($fallback['photos'])) {
                foreach ($fallback['photos'] as $candidate) {
                    if (!in_array((string) $candidate['id'], $existingPhotoIds, true)) {
                        $candidateScore = $this->scoreCandidate($candidate, $context);
                        if ($candidateScore >= $bestScore) {
                            $photo = $candidate;
                            $bestScore = $candidateScore;
                            $usedQuery = (string) ($this->mapCategoryToVisual($category) ?? $category);
                        }
                    }
                }
            }
        }

        if (!$photo) {
            Log::warning('Pexels: No image found after all attempts', [
                'title' => $title,
                'queries_tried' => $queries,
            ]);
            return null;
        }

        Log::info('Pexels: Found image', [
            'query' => $usedQuery,
            'photo_id' => $photo['id'],
            'title' => $title,
            'is_unique' => !in_array($photo['id'], $existingPhotoIds),
            'relevance_score' => $bestScore,
            'alt' => $photo['alt'] ?? '',
        ]);

        // Download and upload
        try {
            $response = Http::timeout(30)->get($photo['url']);

            if ($response->failed() || !$response->body()) {
                Log::error('Pexels: Failed to download image', ['url' => $photo['url']]);
                return null;
            }

            $body = $response->body();
            $contentType = strtolower((string) $response->header('Content-Type'));

            if (!str_starts_with($contentType, 'image/')) {
                Log::error('Pexels: Downloaded content is not an image', [
                    'url' => $photo['url'],
                    'content_type' => $contentType,
                ]);
                return null;
            }

            $extension = 'jpg';
            if (str_contains($contentType, 'png')) {
                $extension = 'png';
            } elseif (str_contains($contentType, 'webp')) {
                $extension = 'webp';
            }

            $tempDirectory = storage_path('app/tmp');
            if (!is_dir($tempDirectory)) {
                mkdir($tempDirectory, 0755, true);
            }

            $tempPath = $tempDirectory . '/pexels-' . $photo['id'] . '.' . $extension;
            file_put_contents($tempPath, $body);

            $result = RvMedia::uploadFromPath($tempPath, 0, 'pexels');

            @unlink($tempPath);

            if (!empty($result['error'])) {
                Log::error('Pexels: Failed to upload image', [
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
                return null;
            }

            $mediaUrl = $result['data']->url ?? null;

            if (!$mediaUrl) {
                Log::error('Pexels: Upload succeeded but no URL returned');
                return null;
            }

            return [
                'media_url' => $mediaUrl,
                'photographer' => $photo['photographer'],
                'photographer_url' => $photo['photographer_url'],
                'pexels_url' => $photo['pexels_url'],
                'pexels_photo_id' => $photo['id'],
            ];
        } catch (\Exception $e) {
            Log::error('Pexels: Exception during upload', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function assignImageToPost(Post $post, array $result): void
    {
        $post->image = $result['media_url'];
        $post->saveQuietly();

        MetaBox::saveMetaBoxData($post, 'pexels_photographer', $result['photographer']);
        MetaBox::saveMetaBoxData($post, 'pexels_photographer_url', $result['photographer_url']);
        MetaBox::saveMetaBoxData($post, 'pexels_photo_url', $result['pexels_url']);
        MetaBox::saveMetaBoxData($post, 'pexels_photo_id', (string) $result['pexels_photo_id']);

        // Keep the homepage cache key aligned with HomepageDataService.
        Cache::forget(HomepageDataService::CACHE_KEY);
    }

    protected function extractStoredPhotoIds(mixed $value): array
    {
        if (is_array($value)) {
            $ids = [];

            foreach ($value as $item) {
                foreach ($this->extractStoredPhotoIds($item) as $id) {
                    $ids[] = $id;
                }
            }

            return array_values(array_filter($ids));
        }

        if (is_numeric($value)) {
            return [(string) $value];
        }

        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
            return $this->extractStoredPhotoIds($decoded);
        }

        preg_match_all('/\d+/', $value, $matches);

        return array_map('strval', $matches[0] ?? []);
    }
}
