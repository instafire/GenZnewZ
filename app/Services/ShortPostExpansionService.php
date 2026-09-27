<?php

namespace App\Services;

use Botble\Base\Facades\MetaBox;
use Botble\Blog\Models\Post;
use Illuminate\Support\Str;

class ShortPostExpansionService
{
    protected array $referenceMap = [
        359 => ['focus_keyword' => 'Web3 Infrastructure', 'external_url' => 'https://ethereum.org/en/web3/', 'external_label' => 'web3 background and ecosystem coverage'],
        435 => ['focus_keyword' => 'Software development stories', 'external_url' => 'https://www.thoughtworks.com/insights', 'external_label' => 'software engineering analysis'],
        436 => ['focus_keyword' => 'RHIC Final Collisions', 'external_url' => 'https://www.bnl.gov/rhic/', 'external_label' => 'Brookhaven National Laboratory RHIC program'],
        392 => ['focus_keyword' => 'Microsoft Open Sources LiteBox', 'external_url' => 'https://opensource.microsoft.com/', 'external_label' => 'Microsoft open-source coverage'],
        431 => ['focus_keyword' => 'SectorC', 'external_url' => 'https://news.ycombinator.com/', 'external_label' => 'developer community discussion'],
        464 => ['focus_keyword' => 'GitBlack', 'external_url' => 'https://www.archives.gov/', 'external_label' => 'historical archives and records'],
        451 => ['focus_keyword' => 'C Compiler', 'external_url' => 'https://news.ycombinator.com/', 'external_label' => 'developer community discussion'],
        468 => ['focus_keyword' => 'Ukraine War', 'external_url' => 'https://www.reuters.com/world/europe/', 'external_label' => 'Reuters Europe coverage'],
        476 => ['focus_keyword' => 'Super Bowl', 'external_url' => 'https://www.nfl.com/super-bowl/', 'external_label' => 'official NFL Super Bowl hub'],
        479 => ['focus_keyword' => 'Medal Count', 'external_url' => 'https://www.olympics.com/', 'external_label' => 'Olympics medal table coverage'],
        495 => ['focus_keyword' => 'North Carolina vs Duke', 'external_url' => 'https://www.espn.com/mens-college-basketball/', 'external_label' => 'college basketball coverage'],
        497 => ['focus_keyword' => 'James Harden Cavaliers debut', 'external_url' => 'https://www.nba.com/', 'external_label' => 'official NBA coverage'],
        493 => ['focus_keyword' => 'Super Bowl LX', 'external_url' => 'https://www.nfl.com/super-bowl/', 'external_label' => 'official NFL Super Bowl hub'],
        499 => ['focus_keyword' => 'Shai Gilgeous Alexander', 'external_url' => 'https://www.nba.com/', 'external_label' => 'official NBA coverage'],
        503 => ['focus_keyword' => 'Patriots Reach Super Bowl LX', 'external_url' => 'https://www.nfl.com/super-bowl/', 'external_label' => 'official NFL Super Bowl hub'],
        527 => ['focus_keyword' => 'Brad Arnold', 'external_url' => 'https://www.billboard.com/', 'external_label' => 'music industry coverage'],
        541 => ['focus_keyword' => 'Canada Snowboarding Medal Hopes', 'external_url' => 'https://www.olympics.com/', 'external_label' => 'Olympics snowboarding coverage'],
        544 => ['focus_keyword' => 'Election Results', 'external_url' => 'https://www.reuters.com/world/asia-pacific/', 'external_label' => 'Reuters Asia-Pacific coverage'],
        556 => ['focus_keyword' => 'Australia Time', 'external_url' => 'https://www.nfl.com/super-bowl/', 'external_label' => 'official NFL Super Bowl hub'],
        626 => ['focus_keyword' => 'Rivian', 'external_url' => 'https://rivian.com/r2', 'external_label' => 'Rivian R2 information'],
        628 => ['focus_keyword' => 'OpenAI Hardware Device Delayed', 'external_url' => 'https://openai.com/', 'external_label' => 'OpenAI product updates'],
        632 => ['focus_keyword' => 'Norway Dominates Biathlon', 'external_url' => 'https://www.olympics.com/', 'external_label' => 'Olympics biathlon coverage'],
        526 => ['focus_keyword' => 'Milano Cortina snowboarding guide', 'external_url' => 'https://www.olympics.com/', 'external_label' => 'Olympics winter sports coverage'],
        534 => ['focus_keyword' => 'Knicks vs Celtics', 'external_url' => 'https://www.nba.com/', 'external_label' => 'official NBA coverage'],
        576 => ['focus_keyword' => 'Super Bowl viewing guide', 'external_url' => 'https://www.nfl.com/super-bowl/', 'external_label' => 'official NFL Super Bowl hub'],
        581 => ['focus_keyword' => 'Mack Hollins', 'external_url' => 'https://www.nfl.com/', 'external_label' => 'official NFL coverage'],
        582 => ['focus_keyword' => 'Canada snowboarding hopes', 'external_url' => 'https://www.olympics.com/', 'external_label' => 'Olympics competition hub'],
        583 => ['focus_keyword' => 'Toronto Eglinton Crosstown LRT', 'external_url' => 'https://www.metrolinx.com/', 'external_label' => 'Metrolinx project updates'],
        584 => ['focus_keyword' => 'Kid Rock music festival', 'external_url' => 'https://www.livenation.com/', 'external_label' => 'Live Nation event information'],
        585 => ['focus_keyword' => 'Japan election results', 'external_url' => 'https://www.reuters.com/world/', 'external_label' => 'Reuters world coverage'],
        586 => ['focus_keyword' => 'Pacers vs Raptors', 'external_url' => 'https://www.nba.com/', 'external_label' => 'official NBA game coverage'],
        587 => ['focus_keyword' => 'BBC Lord of the Flies Adaptation', 'external_url' => 'https://www.bbc.co.uk/', 'external_label' => 'BBC programming coverage'],
        588 => ['focus_keyword' => 'Scottish Cup draw', 'external_url' => 'https://www.scottishfa.co.uk/', 'external_label' => 'Scottish FA competition updates'],
        589 => ['focus_keyword' => 'Brighton vs Crystal Palace', 'external_url' => 'https://www.premierleague.com/', 'external_label' => 'Premier League match coverage'],
        590 => ['focus_keyword' => 'England vs Nepal', 'external_url' => 'https://www.icc-cricket.com/', 'external_label' => 'official ICC tournament coverage'],
        591 => ['focus_keyword' => 'Mia Brookes', 'external_url' => 'https://www.olympics.com/', 'external_label' => 'Olympics snowboarding coverage'],
        592 => ['focus_keyword' => 'Zoi Sadowski-Synnott', 'external_url' => 'https://www.olympics.com/', 'external_label' => 'Olympics snowboarding coverage'],
        593 => ['focus_keyword' => 'Afghanistan vs New Zealand', 'external_url' => 'https://www.icc-cricket.com/', 'external_label' => 'official ICC tournament coverage'],
        594 => ['focus_keyword' => 'Ryan Fox', 'external_url' => 'https://www.pgatour.com/', 'external_label' => 'PGA Tour event coverage'],
        595 => ['focus_keyword' => 'Daniel Hillier', 'external_url' => 'https://www.europeantour.com/dpworld-tour/', 'external_label' => 'DP World Tour coverage'],
        596 => ['focus_keyword' => 'Wanaka Earthquake', 'external_url' => 'https://www.geonet.org.nz/', 'external_label' => 'GeoNet earthquake updates'],
        597 => ['focus_keyword' => 'Super Bowl Australia time', 'external_url' => 'https://www.nfl.com/super-bowl/', 'external_label' => 'official NFL Super Bowl coverage'],
        598 => ['focus_keyword' => 'Payne Haas', 'external_url' => 'https://www.nrl.com/', 'external_label' => 'official NRL coverage'],
        599 => ['focus_keyword' => 'ChatGPT Caricature Feature', 'external_url' => 'https://openai.com/', 'external_label' => 'OpenAI product updates'],
        600 => ['focus_keyword' => 'Sydney Protests', 'external_url' => 'https://www.abc.net.au/news/', 'external_label' => 'ABC News Australia coverage'],
        601 => ['focus_keyword' => 'Brisbane Sherwood Fire', 'external_url' => 'https://www.qfes.qld.gov.au/', 'external_label' => 'Queensland Fire and Emergency Services updates'],
        603 => ['focus_keyword' => 'Quiet luxury trend', 'external_url' => 'https://www.vogue.com/fashion', 'external_label' => 'fashion industry coverage'],
        604 => ['focus_keyword' => 'Vintage streetwear', 'external_url' => 'https://www.highsnobiety.com/', 'external_label' => 'streetwear coverage'],
        532 => ['focus_keyword' => 'Madison Chock and Evan Bates', 'external_url' => 'https://www.olympics.com/', 'external_label' => 'Olympics figure skating coverage'],
        579 => ['focus_keyword' => 'Liverpool vs Man City', 'external_url' => 'https://www.premierleague.com/', 'external_label' => 'Premier League coverage'],
    ];

    protected array $titleOverrides = [
        453 => 'Software Factories and the Agentic Moment in AI Development',
        454 => 'FDA Warns Consumers About Fake Ozempic and Counterfeit GLP-1 Drugs',
        435 => 'Software Development Stories From 25 Years in Tech',
        436 => 'RHIC Final Collisions End 25 Years of Particle Physics',
        464 => "GitBlack Maps the Untold History of America's Foundation",
        495 => 'North Carolina vs Duke: Tar Heels Win 71-68',
        497 => 'James Harden Cavaliers Debut Ends in a Win',
        392 => 'Microsoft Open Sources LiteBox for Security Focused Systems',
        526 => 'Milano Cortina Snowboarding Guide and Viewing Info',
        499 => 'Shai Gilgeous Alexander Out Through All Star Break',
        503 => 'Patriots Reach Super Bowl LX After Stunning Four-Win Run',
        527 => 'Brad Arnold and 3 Doors Down Trend Again in 2026',
        541 => 'Canada Snowboarding Medal Hopes for Milano Cortina 2026',
        576 => 'Super Bowl Viewing Guide for the 2026 Game',
        582 => 'Canada Snowboarding Hopes for Milano Cortina',
        588 => 'Scottish Cup Draw: Rangers and Celtic Quarter-Finals',
        589 => 'Brighton vs Crystal Palace: Seagulls Win 4-1 Derby',
        593 => 'Afghanistan vs New Zealand: Black Caps Lose T20 Thriller',
        597 => 'Super Bowl Australia Time and 10:30 AM AEDT Kickoff',
        532 => 'Madison Chock and Evan Bates Lead Olympic Ice Dance',
        579 => 'Liverpool vs Man City Ends in a Title-Race Draw',
        587 => 'BBC Lord of the Flies Adaptation Sparks Debate Over Modern Retelling',
        628 => 'OpenAI Hardware Device Delayed Until 2027',
        493 => 'Super Bowl LX 2026: Date, Kickoff Time and Stadium Guide',
        372 => 'Busyness Culture and the Pressure to Look Productive',
        403 => 'Canada Telecom Oligopoly and High Cell Phone Bills',
        437 => 'Why Some Developers Still Build Games in C',
        678 => 'Houseplant Care Basics for Beginner Plant Owners',
        710 => 'Mercury Retrograde Beliefs and Why the Trend Persists',
        1092 => 'Qatar Attack 2026: 16 Injured at Al Udeid Base',
    ];

    protected array $descriptionOverrides = [
        454 => 'The FDA warned consumers about counterfeit Ozempic and fake GLP-1 drugs in the supply chain. Here is what changed, who is affected, and what to check.',
        493 => "Super Bowl LX is scheduled for Sunday, February 9, 2026, at Levi's Stadium. Get the date, kickoff time, broadcast basics and key game context.",
        546 => "The BBC's Lord of the Flies adaptation drew split reactions after its premiere, with debate focused on tone, fidelity to the novel and modern framing.",
        587 => "The BBC's Lord of the Flies adaptation sparked debate over its modern retelling, casting choices and how closely it follows William Golding's novel.",
    ];

    protected array $fallbackOnlyIds = [
        1092,
    ];

    protected array $boilerplateSentencePatterns = [
        '/is the center of the latest/i',
        '/this rewrite keeps the verified points/i',
        '/already had the essential headline facts/i',
        '/in practical terms, that means readers need/i',
        '/the core of .* can be understood through the facts/i',
        '/a stronger timeline for /i',
        '/the underlying evidence around /i',
        '/that broader framing is especially important for search users/i',
        '/the next useful updates are likely to/i',
        '/the most useful way to follow /i',
        '/readers coming back to this page should expect/i',
        '/the difference between a thin recap and a useful explainer/i',
        '/search users/i',
        '/framed together, those details/i',
        '/taken together, those details give the story more shape/i',
        '/the immediate takeaway in /i',
        '/the clearest reported developments/i',
        '/the headline matters because/i',
        '/what matters next is whether the initial takeaway holds/i',
        '/the useful question is whether the next round of reporting/i',
        '/the useful questions are consistent/i',
        '/a stronger article should answer those questions directly/i',
        '/the most meaningful updates will come when/i',
        '/technology stories move fast/i',
        '/world news matters most/i',
        '/entertainment and culture stories tend to keep evolving/i',
        '/results, injuries, scheduling, and standings/i',
        '/readers get more value when/i',
        '/in the case of .* available details already point to the broader frame/i',
        '/the update matters because/i',
        '/for (technology|sports|world-news|culture) readers/i',
        '/for readers, that means/i',
        '/short posts often name the headline event/i',
        '/that extra detail gives the story enough shape/i',
        '/\bis the center\b/i',
        '/stands out because the headline in/i',
        '/readers want to know what changes next/i',
        '/the key signals usually involve/i',
        '/someone looking for /i',
        '/a stronger article therefore needs/i',
    ];

    protected array $legacyMarkerPatterns = [
        '/is the center of the latest/i',
        '/this rewrite keeps the verified points/i',
        '/already had the essential headline facts/i',
        '/in practical terms, that means readers need/i',
        '/the core of .* can be understood through the facts/i',
        '/a stronger timeline for /i',
        '/the underlying evidence around /i',
        '/that broader framing is especially important for search users/i',
        '/the next useful updates are likely to/i',
        '/the most useful way to follow /i',
        '/readers coming back to this page should expect/i',
        '/the difference between a thin recap and a useful explainer/i',
        '/search users/i',
    ];

    public function expand(Post $post): array
    {
        $title = $this->buildTitle($post);
        $category = $post->categories->first();
        $categoryName = $category?->name ?: 'News';
        $categoryUrl = $category?->url ?: url('/');
        $homeUrl = url('/');
        $plain = $this->normalizeSourceText((string) $post->content);
        $sentences = $this->extractSentences($plain);
        $facts = array_slice($sentences, 0, 6);

        $reference = $this->referenceMap[$post->id] ?? [
            'focus_keyword' => $this->deriveFocusKeyword($title),
            'external_url' => 'https://www.reuters.com/world/',
            'external_label' => 'reliable external reporting',
        ];

        $focusKeyword = $reference['focus_keyword'];
        $description = $this->buildDescription($post, $focusKeyword, $facts, $title, $categoryName);
        $content = $this->buildContent(
            $post,
            $title,
            $focusKeyword,
            $categoryName,
            $categoryUrl,
            $homeUrl,
            $reference['external_url'],
            $reference['external_label'],
            $facts,
            $plain
        );

        return [
            'title' => $title,
            'focus_keyword' => $focusKeyword,
            'description' => $description,
            'content' => $content,
        ];
    }

    protected function buildContent(
        Post $post,
        string $title,
        string $focusKeyword,
        string $categoryName,
        string $categoryUrl,
        string $homeUrl,
        string $externalUrl,
        string $externalLabel,
        array $facts,
        string $plain
    ): string {
        $lead = $this->leadParagraph($post, $title, $focusKeyword, $facts, $categoryName);
        $summary = $this->summaryParagraph($post, $focusKeyword, $facts, $title, $categoryName);
        $whatHappened = $this->whatHappenedParagraph($post, $focusKeyword, $facts, $plain, $categoryName);
        $context = $this->contextParagraph($focusKeyword, $categoryName, $facts);
        $timeline = $this->timelineParagraph($post, $focusKeyword, $facts, $plain, $categoryName);
        $meaning = $this->meaningParagraph($focusKeyword, $categoryName, $title);
        $signals = $this->signalsParagraph($focusKeyword, $categoryName, $facts);
        $audience = $this->audienceParagraph($focusKeyword, $categoryName);
        $watch = $this->watchParagraph($focusKeyword, $categoryName, $facts);
        $follow = $this->followParagraph($focusKeyword, $categoryName, $facts);
        $links = $this->linksParagraph($focusKeyword, $categoryName, $categoryUrl, $homeUrl, $externalUrl, $externalLabel);

        return implode("\n", [
            '<p>' . e($lead) . '</p>',
            '<p>' . e($summary) . '</p>',
            '<h2>' . e('Key developments') . '</h2>',
            '<p>' . e($whatHappened) . '</p>',
            '<p>' . e($context) . '</p>',
            '<h2>' . e('How the story developed') . '</h2>',
            '<p>' . e($timeline) . '</p>',
            '<p>' . e($this->evidenceParagraph($post, $focusKeyword, $facts, $plain, $categoryName)) . '</p>',
            '<h2>' . e('Why it matters') . '</h2>',
            '<p>' . e($meaning) . '</p>',
            '<p>' . e($signals) . '</p>',
            '<p>' . e($audience) . '</p>',
            '<h2>' . e('What to watch next') . '</h2>',
            '<p>' . e($watch) . '</p>',
            '<p>' . e($follow) . '</p>',
            '<p>' . $links . '</p>',
        ]);
    }

    protected function leadParagraph(Post $post, string $title, string $focusKeyword, array $facts, string $categoryName): string
    {
        if (! $this->shouldUseExtractedFacts($post, $categoryName, $facts)) {
            $proseTitle = $this->proseTopicTitle($title);

            return trim($proseTitle . ' sits inside a broader ' . Str::lower($categoryName) . ' conversation. ' . $this->fallbackOverview($focusKeyword, $categoryName) . ' That wider frame is what readers need if the topic is going to make sense beyond the headline alone.');
        }

        $leadFact = $this->factSentence($facts, 0, $title . ' is generating fresh attention.');
        $supportingFact = $this->factSentence($facts, 1);
        $categoryAngle = match (Str::lower($categoryName)) {
            'sports' => 'The latest result or selection call matters because it can quickly change the pressure on the next fixture.',
            'ai news', 'tech & games' => 'The update matters because product, platform, or research changes rarely stay isolated for long.',
            'the world', 'politics', 'climate emergency', 'war' => 'The headline matters because the first confirmed details often shape public response and official decision-making.',
            'music', 'movies', 'culture', 'fashion' => 'The headline matters because reaction, reach, and follow-on commentary usually determine whether the moment keeps building.',
            default => 'The headline matters because the first alert usually leaves out the context readers need to judge what actually changed.',
        };

        return trim($leadFact . ' ' . $supportingFact . ' ' . $categoryAngle . ' Taken together, those details give the story more shape than the original short brief alone.');
    }

    protected function summaryParagraph(Post $post, string $focusKeyword, array $facts, string $title, string $categoryName): string
    {
        if (! $this->shouldUseExtractedFacts($post, $categoryName, $facts)) {
            return trim('The immediate value in a fuller article about ' . $this->proseTopicTitle($title) . ' is perspective rather than repetition. ' . $this->fallbackStake($focusKeyword, $categoryName) . ' A useful explainer should connect the topic to real-world consequences, not just restate the hook.');
        }

        $detail = $this->factSentence($facts, 1, $title . ' is still unfolding.');
        $extra = $this->factSentence($facts, 2);
        $categoryContext = match (Str::lower($categoryName)) {
            'sports' => 'For sports readers, that means looking beyond the scoreline to form, availability, and the next competitive consequence.',
            'ai news', 'tech & games' => 'For technology readers, that means separating the headline claim from the product, adoption, and market implications.',
            'the world', 'politics', 'climate emergency', 'war' => 'For world-news readers, that means tying the first bulletin to institutions, response, and likely second-order effects.',
            'music', 'movies', 'culture', 'fashion' => 'For culture readers, that means weighing reaction, reputation, and whether the moment signals a larger shift.',
            default => 'For readers, that means connecting the first headline to the broader context instead of stopping at the alert itself.',
        };

        return trim('The immediate takeaway in ' . $focusKeyword . ' is not only the headline event but the surrounding context. ' . $detail . ' ' . $extra . ' ' . $categoryContext);
    }

    protected function whatHappenedParagraph(Post $post, string $focusKeyword, array $facts, string $plain, string $categoryName): string
    {
        if (! $this->shouldUseExtractedFacts($post, $categoryName, $facts)) {
            return trim('At the center of ' . $focusKeyword . ' are a few practical questions: what changed, who is affected, and which part of the story is actually new. ' . $this->fallbackSequence($focusKeyword) . ' That baseline makes the subject easier to evaluate than a stripped-down alert or a trendy one-liner.');
        }

        $detail = $this->joinFacts($facts, 0, 3, $plain, 260);

        return trim('The clearest reported developments so far point to a straightforward sequence. ' . $detail . ' Read together, those details identify the trigger, the main actors, and the outcome that pushed ' . $focusKeyword . ' into wider view.');
    }

    protected function contextParagraph(string $focusKeyword, string $categoryName, array $facts): string
    {
        $finalFact = $this->factSentence($facts, 3);

        $categoryContext = match (Str::lower($categoryName)) {
            'sports' => 'Results, injuries, scheduling, and standings often shape how quickly the conversation shifts after a single game or competition update.',
            'ai news', 'tech & games' => 'Technology stories move fast, but the real significance usually comes from how a product, feature, or research milestone changes user behavior or industry expectations.',
            'the world', 'politics', 'climate emergency' => 'World news matters most when headline facts are tied to institutions, public response, and the next decisions from authorities or stakeholders.',
            'music', 'movies', 'culture', 'fashion' => 'Entertainment and culture stories tend to keep evolving after the first headline because audience reaction, platform performance, and follow-up commentary all matter.',
            default => 'Readers get more value when the immediate headline is connected to the broader context surrounding the event, response, and likely follow-up.',
        };

        return trim($categoryContext . ' In the case of ' . $focusKeyword . ', the published details already hint at that broader frame. ' . $finalFact . ' That context is what turns a fast alert into a useful explainer.');
    }

    protected function timelineParagraph(Post $post, string $focusKeyword, array $facts, string $plain, string $categoryName): string
    {
        if (! $this->shouldUseExtractedFacts($post, $categoryName, $facts)) {
            return trim('The timeline around ' . $focusKeyword . ' is best understood in stages. First comes the trigger that puts the issue in front of readers. Then comes the reaction from institutions, audiences, or markets. Finally comes the question of whether the early framing holds once better information arrives. That sequence matters because the first interpretation is often incomplete.');
        }

        $opening = $this->factSentence($facts, 0, Str::limit($plain, 150, ''));
        $middle = $this->factSentence($facts, 1);
        $late = $this->factSentence($facts, 2);

        return trim('The timeline around ' . $focusKeyword . ' is easier to follow when the major steps are separated clearly. First, ' . $this->lcfirstSentence($opening) . ' Then, ' . $this->lcfirstSentence($middle) . ' Finally, ' . $this->lcfirstSentence($late) . ' Viewed in order, the story looks less like scattered fragments and more like a developing sequence.');
    }

    protected function evidenceParagraph(Post $post, string $focusKeyword, array $facts, string $plain, string $categoryName): string
    {
        if (! $this->shouldUseExtractedFacts($post, $categoryName, $facts)) {
            return trim('The evidence standard matters here. A credible explainer should separate what is confirmed from what is inferred, identify which claims come from official sources or industry reporting, and make clear where the story is still developing. That discipline is what keeps ' . $focusKeyword . ' useful for readers instead of turning it into pure commentary.');
        }

        $supporting = $this->joinFacts($facts, 3, 2, $plain, 220);

        return trim('The supporting evidence matters as much as the headline itself. ' . $supporting . ' Those additional details help clarify why ' . $focusKeyword . ' matters, what remains uncertain, and which parts of the story deserve the closest scrutiny.');
    }

    protected function meaningParagraph(string $focusKeyword, string $categoryName, string $title): string
    {
        $categoryMeaning = match (Str::lower($categoryName)) {
            'sports' => 'For sports readers, the importance usually comes down to momentum, selection decisions, title or playoff implications, and how one result changes the pressure on the next fixture.',
            'ai news', 'tech & games' => 'For technology readers, the importance comes from adoption, platform behavior, product trust, and whether the update signals a bigger shift beyond the initial announcement.',
            'the world', 'politics', 'climate emergency' => 'For world-news readers, the importance lies in public safety, market reaction, policy consequences, diplomatic fallout, or the quality of the official response.',
            'music', 'movies', 'culture', 'fashion' => 'For culture readers, the importance often lies in reach, audience response, reputation, and whether the moment signals a broader trend rather than a one-day burst of attention.',
            default => 'For readers, the importance comes from understanding how the initial event could shape the next wave of reaction, reporting, or decision-making.',
        };

        return trim($categoryMeaning . ' In the case of ' . $focusKeyword . ', that means looking beyond the headline in "' . $title . '" and focusing on who is affected, what could shift next, and which questions remain unresolved.');
    }

    protected function signalsParagraph(string $focusKeyword, string $categoryName, array $facts): string
    {
        $extraFact = Str::limit((string) ($facts[5] ?? $facts[2] ?? ''), 120, '');

        $signal = match (Str::lower($categoryName)) {
            'sports' => 'The key signals usually involve form, lineup implications, tournament pressure, and whether the latest result changes expectations around the next fixture.',
            'ai news', 'tech & games' => 'The key signals usually involve adoption, product trust, developer response, or whether the update points to a broader platform shift rather than a one-off announcement.',
            'the world', 'politics', 'climate emergency', 'war' => 'The key signals usually involve official response, public safety impact, regional fallout, and whether the next decision by governments or institutions changes the picture again.',
            'business', 'investing genz', 'crypto' => 'The key signals usually involve market confidence, pricing pressure, regulation, and whether the next set of numbers confirms the direction implied by the first headline.',
            default => 'The key signals usually involve whether the initial headline keeps developing into something with wider consequences for readers, institutions, or audiences.',
        };

        return trim($signal . ' In this case, ' . $focusKeyword . ' continues to matter because the first update is only one part of a wider pattern. ' . $extraFact . ' The useful question is whether the next round of reporting confirms that direction or changes it.');
    }

    protected function audienceParagraph(string $focusKeyword, string $categoryName): string
    {
        return trim('For readers trying to make sense of ' . $focusKeyword . ', the useful questions are consistent: what actually happened, how confident the available facts are, and what developments could change the picture next. A stronger article should answer those questions directly instead of repeating the headline in different words.');
    }

    protected function watchParagraph(string $focusKeyword, string $categoryName, array $facts): string
    {
        $watchAngle = match (Str::lower($categoryName)) {
            'sports' => 'The next developments to monitor usually involve official team news, tournament scheduling, standings movement, recovery reports, or selection calls that change the competitive picture.',
            'ai news', 'tech & games' => 'The next developments to monitor usually involve user rollout details, platform reactions, policy clarification, competitive responses, or hard numbers that show whether the change has real staying power.',
            'the world', 'politics', 'climate emergency' => 'The next developments to monitor usually come from officials, emergency services, markets, courts, or diplomatic channels as the immediate consequences become clearer.',
            'music', 'movies', 'culture', 'fashion' => 'The next developments to monitor usually come from audience response, official statements, performance data, reviews, or platform distribution choices.',
            default => 'The next developments to monitor usually come from official statements, follow-up reporting, and any measurable shift in public reaction or stakeholder response.',
        };

        $lastFact = Str::limit((string) ($facts[4] ?? $facts[2] ?? ''), 120, '');

        return trim('For now, ' . $focusKeyword . ' remains worth watching. ' . $watchAngle . ' ' . $lastFact . ' The most meaningful updates will come when official statements, hard data, or follow-up reporting materially change the picture established so far.');
    }

    protected function followParagraph(string $focusKeyword, string $categoryName, array $facts): string
    {
        $checkpoints = match (Str::lower($categoryName)) {
            'sports' => 'lineup decisions, recovery updates, match scheduling, and standings shifts',
            'ai news', 'tech & games' => 'rollout details, user adoption, competitive responses, and technical follow-ups',
            'the world', 'politics', 'climate emergency', 'war' => 'official statements, emergency response, diplomatic moves, and updated casualty or impact assessments',
            'business', 'investing genz', 'crypto' => 'market reaction, regulatory action, earnings context, and shifts in investor positioning',
            default => 'official updates, measurable outcomes, and the next checkpoint that confirms whether the story is expanding or fading',
        };

        return trim('The clearest way to follow ' . $focusKeyword . ' from here is to watch for ' . $checkpoints . '. What matters next is whether the initial takeaway holds once more evidence, reaction, or performance data comes in.');
    }

    protected function linksParagraph(string $focusKeyword, string $categoryName, string $categoryUrl, string $homeUrl, string $externalUrl, string $externalLabel): string
    {
        return 'Readers who want more ' . e(Str::lower($categoryName)) . ' context can follow <a href="' . e($categoryUrl) . '">our ' . e($categoryName) . ' coverage</a>, browse the <a href="' . e($homeUrl) . '">latest GenZ NewZ headlines</a>, and compare this report with <a href="' . e($externalUrl) . '" rel="noopener noreferrer nofollow" target="_blank">' . e($externalLabel) . '</a> for additional official or industry background on ' . e($focusKeyword) . '.';
    }

    protected function buildDescription(Post $post, string $focusKeyword, array $facts, string $title, string $categoryName): string
    {
        if (array_key_exists($post->id, $this->descriptionOverrides)) {
            return trim(Str::limit($this->descriptionOverrides[$post->id], 160, ''));
        }

        if (! $this->shouldUseExtractedFacts($post, $categoryName, $facts)) {
            return trim(Str::limit($this->proseTopicTitle($title) . ' explained with key context on why it matters, who is affected, and which developments are worth watching next.', 160, ''));
        }

        $fact = trim($this->joinFacts($facts, 0, 2, $title, 120));
        $fact = preg_replace('/\s+/', ' ', $fact);
        $candidate = trim($focusKeyword . ': ' . $fact);

        if (mb_strlen($candidate) < 120) {
            $candidate = trim($candidate . ' Get the key facts, the broader context, and the next developments worth tracking.');
        }

        if (mb_strlen($candidate) > 160) {
            $candidate = trim(Str::limit($candidate, 160, ''));
        }

        if (mb_strlen($candidate) < 120) {
            $candidate = trim(Str::limit($focusKeyword . ' explained with the main facts, the broader context, and the next developments to watch.', 160, ''));
        }

        return $candidate;
    }

    protected function buildTitle(Post $post): string
    {
        $hasExplicitOverride = array_key_exists($post->id, $this->titleOverrides);
        $title = $this->sanitizeGeneratedTitle(trim($this->titleOverrides[$post->id] ?? (string) $post->name));

        if (! $hasExplicitOverride && mb_strlen($title) < 45) {
            $title = $this->expandShortTitle($title, $post);
        }

        return trim(Str::limit($this->sanitizeGeneratedTitle($title), 68, ''));
    }

    protected function deriveFocusKeyword(string $title): string
    {
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $title);
        $title = preg_replace('/\s+/', ' ', trim((string) $title));

        $segments = [$title];

        if (str_contains($title, ':')) {
            [$left, $right] = array_pad(array_map('trim', explode(':', $title, 2)), 2, '');
            $segments = array_values(array_filter([$left, $right, $title]));
        }

        foreach ($segments as $segment) {
            $candidate = $this->extractLiteralKeywordCandidate($segment, $title);
            if ($candidate !== null) {
                return $candidate;
            }
        }

        return trim(preg_replace('/\s+/', ' ', (string) $title));
    }

    protected function expandShortTitle(string $title, ?Post $post = null): string
    {
        $normalized = Str::lower($title);
        $slugTitle = $this->slugTitle($post);

        if (str_starts_with($normalized, 'how to make ')) {
            return rtrim($title, ': ') . ': Easy Homemade Recipe';
        }

        if (str_starts_with($normalized, 'how to ')) {
            return rtrim($title, ': ') . ' Guide';
        }

        if ($slugTitle !== null && mb_strlen($slugTitle) > mb_strlen($title)) {
            return $slugTitle;
        }

        if (mb_strlen($title) < 24) {
            return trim($title . ' Explained');
        }

        return trim($title . ' Explained');
    }

    protected function slugTitle(?Post $post): ?string
    {
        $slug = trim((string) ($post?->slugable?->key ?? ''));

        if ($slug === '') {
            return null;
        }

        $slug = preg_replace('/[-_]+/', ' ', $slug);
        $slug = preg_replace('/\s+/', ' ', trim((string) $slug));
        if ($slug === '') {
            return null;
        }

        $title = Str::title($slug);

        return trim(Str::limit($title, 68, ''));
    }

    protected function extractLiteralKeywordCandidate(string $segment, string $fullTitle): ?string
    {
        $segment = preg_replace('/\s+/', ' ', trim($segment));
        if ($segment === '') {
            return null;
        }

        $words = preg_split('/\s+/', $segment) ?: [];

        for ($window = min(6, count($words)); $window >= 2; $window--) {
            for ($offset = 0; $offset <= count($words) - $window; $offset++) {
                $slice = array_slice($words, $offset, $window);
                if ($this->containsProblematicToken($slice)) {
                    continue;
                }

                $candidate = trim(implode(' ', $slice), " \t\n\r\0\x0B\"'“”‘’.,!?():;-");
                if ($candidate === '' || str_word_count($candidate) < 2) {
                    continue;
                }

                if (mb_strlen($candidate) < 10) {
                    continue;
                }

                if (mb_stripos($fullTitle, $candidate) !== false) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    protected function containsProblematicToken(array $slice): bool
    {
        foreach ($slice as $word) {
            $token = trim($word);
            if ($token === '') {
                return true;
            }

            if (preg_match('/[^\pL\pN]/u', $token)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeSourceText(string $content): string
    {
        $text = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\\n", "\\r", "\n", "\r", "\t"], ' ', $text);
        $text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);
        $text = preg_replace('/\s+/', ' ', trim((string) $text));
        $text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);
        $text = preg_replace('/\s+/', ' ', trim((string) $text));

        return $text;
    }

    protected function extractSentences(string $text, int $limit = 6): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text) ?: [];
        $sentences = array_map(fn ($sentence) => $this->normalizeSentence($sentence), $sentences);
        $sentences = array_values(array_filter($sentences, function ($sentence) {
            if (str_word_count($sentence) < 6) {
                return false;
            }

            foreach ($this->boilerplateSentencePatterns as $pattern) {
                if (preg_match($pattern, $sentence)) {
                    return false;
                }
            }

            return true;
        }));

        $unique = [];
        foreach ($sentences as $sentence) {
            $key = mb_strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $sentence));
            $key = preg_replace('/\s+/', ' ', trim((string) $key));

            if ($key === '' || isset($unique[$key])) {
                continue;
            }

            $unique[$key] = $sentence;
        }

        return array_slice(array_values($unique), 0, $limit);
    }

    public function countLegacyBoilerplateHits(string $content): int
    {
        $text = $this->normalizeSourceText($content);
        $hits = 0;

        foreach ($this->legacyMarkerPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $hits++;
            }
        }

        return $hits;
    }

    public function needsLegacyRefresh(Post $post): bool
    {
        if ($this->countLegacyBoilerplateHits((string) $post->content) > 0) {
            return true;
        }

        return (bool) preg_match('/\bUpdate(?:\s+Update)?$/i', (string) $post->name);
    }

    protected function sanitizeGeneratedTitle(string $title): string
    {
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = preg_replace('/\s+/', ' ', trim((string) $title));
        $title = preg_replace('/\s+Update(?:\s+Update)+$/i', ' Update', $title);
        $title = preg_replace('/:\s*What Gen Z Needs to Know$/i', ' Explained', $title);
        $title = preg_replace('/:\s*Everything You Need to Know$/i', ' Guide', $title);
        $title = preg_replace('/\s+Latest Update$/i', '', $title);
        $title = preg_replace('/\s+Update$/i', '', $title);
        $title = preg_replace('/\s+Explained Explained$/i', ' Explained', $title);
        $title = preg_replace('/\s+Guide Guide$/i', ' Guide', $title);

        return trim((string) $title, " -:!?.,");
    }

    protected function factSentence(array $facts, int $index, string $fallback = ''): string
    {
        $sentence = $facts[$index] ?? $fallback;

        return $this->normalizeSentence($sentence);
    }

    protected function joinFacts(array $facts, int $offset, int $length, string $fallback = '', int $maxLength = 220): string
    {
        $slice = array_slice($facts, $offset, $length);
        $slice = array_values(array_filter(array_map(fn ($fact) => $this->normalizeSentence((string) $fact), $slice)));

        if ($slice === []) {
            return Str::limit($this->normalizeSentence($fallback), $maxLength, '');
        }

        return Str::limit(implode(' ', $slice), $maxLength, '');
    }

    protected function normalizeSentence(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', trim((string) $text));
        $text = trim((string) $text, " \t\n\r\0\x0B\"'“”‘’");

        if ($text !== '' && ! preg_match('/[.!?]$/', $text)) {
            $text .= '.';
        }

        return $text;
    }

    protected function lcfirstSentence(string $sentence): string
    {
        $sentence = trim($sentence);

        if ($sentence === '') {
            return 'the picture is still developing.';
        }

        return lcfirst($sentence);
    }

    protected function proseTopicTitle(string $title): string
    {
        $title = preg_replace('/\s+(Explained|Guide)$/i', '', trim($title)) ?: trim($title);

        return trim($title);
    }

    protected function hasSignalFacts(array $facts): bool
    {
        return count($facts) >= 2;
    }

    protected function shouldUseExtractedFacts(Post $post, string $categoryName, array $facts): bool
    {
        if (! $this->hasSignalFacts($facts)) {
            return false;
        }

        if (in_array((int) $post->id, $this->fallbackOnlyIds, true)) {
            return false;
        }

        return in_array(Str::lower($categoryName), [
            'sports',
            'the world',
            'politics',
            'climate emergency',
            'war',
            'business',
            'crypto',
            'investing genz',
        ], true);
    }

    protected function fallbackOverview(string $focusKeyword, string $categoryName): string
    {
        return match (Str::lower($categoryName)) {
            'ai news', 'tech & games' => $focusKeyword . ' touches automation, platform behavior, and the way digital systems influence everyday choices.',
            'career path', 'business', 'investing genz' => $focusKeyword . ' touches work, pay, training, and the way economic pressure changes long-term planning.',
            'mind & body', 'life hacks' => $focusKeyword . ' touches routines, wellbeing, and the gap between online advice and practical reality.',
            'culture', 'music', 'movies', 'fashion' => $focusKeyword . ' touches identity, media behavior, and the way trends move from niche circles into mainstream conversation.',
            default => $focusKeyword . ' raises a larger question that cannot be answered well by a bare headline alone.',
        };
    }

    protected function fallbackStake(string $focusKeyword, string $categoryName): string
    {
        return match (Str::lower($categoryName)) {
            'ai news', 'tech & games' => 'Readers usually want to know how ' . $focusKeyword . ' could affect product design, user trust, competitive pressure, or the pace of adoption.',
            'career path', 'business', 'investing genz' => 'Readers usually want to know how ' . $focusKeyword . ' could affect wages, opportunity, cost of living, or the practical choices people make next.',
            'mind & body', 'life hacks' => 'Readers usually want to know whether ' . $focusKeyword . ' changes health decisions, daily habits, or the quality of advice people rely on.',
            'culture', 'music', 'movies', 'fashion' => 'Readers usually want to know whether ' . $focusKeyword . ' reflects a passing burst of attention or a more durable shift in taste and behavior.',
            default => 'Readers usually want to know what ' . $focusKeyword . ' changes in practice, not just why it trended for a moment.',
        };
    }

    protected function fallbackSequence(string $focusKeyword): string
    {
        return 'The story usually moves from an initial claim or event, to early reaction, to a second round of reporting that clarifies whether the first interpretation was accurate. That is the point where ' . $focusKeyword . ' becomes more than a headline and starts to become a topic readers can actually assess.';
    }

    public function applyToPost(Post $post): array
    {
        $expanded = $this->expand($post);

        $post->name = $expanded['title'];
        $post->description = $expanded['description'];
        $post->content = $expanded['content'];
        $post->saveQuietly();

        MetaBox::saveMetaBoxData($post, 'focus_keyword', $expanded['focus_keyword']);

        return $expanded;
    }
}
