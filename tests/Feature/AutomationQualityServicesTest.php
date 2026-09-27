<?php

namespace Tests\Feature;

use App\Services\AutomationContentGuardService;
use App\Services\PexelsImageService;
use App\Services\ShortPostExpansionService;
use Botble\Blog\Models\Post;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AutomationQualityServicesTest extends TestCase
{
    public function test_content_guard_rejects_test_stub_content(): void
    {
        $guard = app(AutomationContentGuardService::class);

        $result = $guard->validate([
            'title' => 'API Test Article for Validation Workflow',
            'description' => 'API test placeholder article used only for checking the automation endpoint and should never be published on the live site.',
            'content' => '<p>Test content only.</p>',
            'focus_keyword' => 'api test article',
        ]);

        $this->assertFalse($result['passed']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_content_guard_accepts_structured_news_draft(): void
    {
        $guard = app(AutomationContentGuardService::class);

        $body = implode('', [
            '<p>OpenAI released a new policy update for enterprise customers and explained the rollout in a support note. The update affects security controls, onboarding requirements, and the cadence for account verification.</p>',
            '<h2>What Changed</h2>',
            '<p>The company said enterprise administrators will see stronger verification prompts and clearer audit controls. According to <a href="https://openai.com">OpenAI</a>, the changes are designed to reduce abuse while preserving access for legitimate teams.</p>',
            '<h2>Why It Matters</h2>',
            '<p>For publishers and software teams, the new controls change how quickly shared workspaces can be onboarded. GenZ NewZ previously covered automation guardrails in <a href="https://genznewz.com/ai-news-reporter">its newsroom automation guide</a>, and the latest update gives more detail about responsible deployment.</p>',
            '<p>The article also explains which settings administrators need to review before enabling production use. Editors, developers, and operations teams are expected to coordinate around review workflows, verification states, and support escalation paths.</p>',
            '<p>This draft intentionally contains enough words to clear the minimum threshold while remaining coherent and source-backed for review. It keeps the topic present in the body and uses a realistic structure that the automation service should allow.</p>',
            '<p>OpenAI enterprise policy update planning also creates operational questions for legal review, vendor management, and newsroom access control. Teams that rely on shared publishing credentials now have to define clearer ownership so changes to verification status do not silently block production publishing windows.</p>',
            '<p>Another impact area is training. Editors and developers need onboarding material that explains what the verification prompts mean, how to escalate unexpected account changes, and how to confirm that logging and moderation alerts remain configured after workspace settings are updated. These are practical details that often determine whether a policy rollout succeeds.</p>',
            '<p>Security teams may welcome the shift because it forces more deliberate control over high-risk features. Product teams may see short-term friction, but the long-term result is usually a cleaner audit trail, fewer abandoned credentials, and faster incident response when access needs to be reviewed. Those are tangible benefits in any newsroom or enterprise workflow.</p>',
            '<p>OpenAI enterprise policy update appears naturally here again because the draft is meant to mimic a publishable article, not a stuffed filler block. Search-focused structure, documented sources, and clear sections work better than generic filler when automation pipelines are expected to meet editorial standards consistently.</p>',
            '<p>Readers also benefit from specificity. Instead of vague claims about safety, a stronger article explains who is affected, what changed in the workflow, why the update matters, and which teams need to respond first. That approach makes the story more useful and also gives search engines clearer topical signals about the real intent of the page.</p>',
            '<p>Implementation timing matters too. If enterprise teams do not stage changes, they can accidentally create bottlenecks for urgent publishing work, especially when multiple desks share a common authentication path. A better rollout uses documented checkpoints, fallback access, and clear confirmation that monitoring, moderation, and support ownership all remain intact after policy changes take effect.</p>',
            '<p>That extra operational detail is what separates a shallow automation draft from a publishable article. Readers, editors, and search engines all respond better when the piece demonstrates real subject coverage, references a source, connects to an internal context page, and explains practical consequences instead of repeating broad claims. That is the standard this test is designed to reflect.</p>',
            '<p>Procurement and governance teams are also part of the implementation story. They often need to validate contract terms, document retention practices, and incident reporting obligations before approval workflows are finalized. When those controls are reviewed early, organizations reduce launch delays and avoid emergency changes after production systems are already in use.</p>',
            '<p>A final operational point is change visibility. Teams should publish clear ownership maps for verification settings, approval logic, and escalation paths so people know exactly where to respond when access behavior changes. According to documented enterprise rollout playbooks, visibility and accountability are usually the difference between stable adoption and recurring operational friction.</p>',
            '<p>Another practical takeaway is stakeholder sequencing. Technical owners, editorial leads, legal reviewers, and security teams should align on checkpoints before launch so policy updates are adopted consistently without producing avoidable publication delays.</p>',
        ]);

        $result = $guard->validate([
            'title' => 'OpenAI Enterprise Policy Update Changes Admin Verification Rules',
            'description' => 'OpenAI enterprise admins face new verification controls, audit prompts, and onboarding checks that affect how newsroom automation is rolled out.',
            'content' => $body,
            'focus_keyword' => 'OpenAI enterprise policy update',
        ]);

        $this->assertTrue($result['passed']);
    }

    public function test_content_guard_requires_explicit_source_attribution_phrase(): void
    {
        $guard = app(AutomationContentGuardService::class);

        $body = implode('', [
            '<p>Federal agencies published new guidance on digital identity verification and enterprise compliance controls this week.</p>',
            '<h2>Guidance Details</h2>',
            '<p>The guidance outlines stronger audit logging expectations, phased adoption windows, and operator accountability requirements for enterprise teams.</p>',
            '<h2>Operational Impact</h2>',
            '<p>Large organizations are expected to adjust onboarding workflows, update access controls, and document review checkpoints to reduce rollout risk.</p>',
            '<p>The source material can be reviewed directly at <a href="https://www.cisa.gov">CISA</a>, where the guidance references security governance expectations for institutions.</p>',
            '<p>This draft intentionally avoids attribution phrases to verify the guard catches missing in-body source attribution language even when a source link exists.</p>',
            '<p>Teams reviewing this policy should map account ownership, escalation paths, and change-management checkpoints before activation to avoid workflow interruptions.</p>',
            '<p>Strong implementation plans usually include staff training, support ownership, and documented rollback procedures for mission-critical publishing windows.</p>',
            '<p>Compliance-focused teams generally benefit from explicit controls because they reduce ambiguity and improve incident response when credentials change.</p>',
            '<p>This text is long enough for the quality floor and includes a legitimate external link, but it should fail on missing attribution phrasing by design.</p>',
        ]);

        $result = $guard->validate([
            'title' => 'Enterprise Verification Policy Update Adds Audit Controls',
            'description' => 'Enterprise verification policy updates add new audit controls and phased rollout requirements for organizations using shared systems.',
            'content' => $body,
            'focus_keyword' => 'enterprise verification policy update',
        ]);

        $this->assertFalse($result['passed']);
        $this->assertTrue(collect($result['errors'])->contains(fn ($message) => str_contains($message, 'source attribution')));
    }

    public function test_content_guard_rejects_ai_assistant_artifact_phrasing(): void
    {
        $guard = app(AutomationContentGuardService::class);

        $body = implode('', [
            '<p>As an AI language model, I cannot browse the web in real time, but this draft summarizes the latest policy changes for enterprise compliance teams.</p>',
            '<h2>Policy Snapshot</h2>',
            '<p>According to <a href="https://www.reuters.com">Reuters</a>, organizations are tightening verification controls and internal review workflows for sensitive systems.</p>',
            '<h2>Operational Implications</h2>',
            '<p>Teams are expected to formalize ownership around access requests, escalation rules, and monitoring checkpoints across departments.</p>',
            '<p>This article includes enough length, sourcing, and structure to isolate the AI-artifact phrase as the primary reason for rejection.</p>',
            '<p>Editors and automation systems should remove assistant-style meta language before publication to keep reporting voice clean and factual.</p>',
            '<p>The workflow impact includes updated runbooks, approval steps, and better audit traceability for compliance-sensitive changes.</p>',
            '<p>Managers handling rollout plans should map all key dependencies and establish fallback controls for urgent publication windows.</p>',
            '<p>This additional context ensures the draft remains substantial while preserving the artifact phrase that should trigger the guard failure.</p>',
            '<p>Reporting quality improves when articles focus on evidence and direct attribution rather than model disclaimers.</p>',
            '<p>The focus keyword enterprise compliance policy update appears naturally across this example to preserve SEO realism.</p>',
            '<p>Another sentence extends depth and maintains structural quality for this targeted test case.</p>',
        ]);

        $result = $guard->validate([
            'title' => 'Enterprise Compliance Policy Update Adds Verification Controls',
            'description' => 'Enterprise compliance policy updates add stronger verification controls and clearer approval workflows across organizations.',
            'content' => $body,
            'focus_keyword' => 'enterprise compliance policy update',
        ]);

        $this->assertFalse($result['passed']);
        $this->assertTrue(collect($result['errors'])->contains(fn ($message) => str_contains($message, 'AI-assistant artifact')));
    }

    public function test_content_guard_rejects_code_or_script_style_submission(): void
    {
        $guard = app(AutomationContentGuardService::class);

        $body = implode('', [
            '<p>Developers are facing tighter publication controls as newsrooms try to stop low-quality automation output from turning article tasks into tool-building tasks. The policy change matters because it pushes agents to finish the reported story instead of generating a posting wrapper.</p>',
            '<h2>Policy Shift</h2>',
            '<p>According to <a href="https://www.reuters.com">Reuters</a>, publishers are tightening editorial controls around source use, accountability, and final-copy review.</p>',
            '<h2>What Gets Blocked</h2>',
            '<p>The new standard rejects article bodies that contain scripts, terminal commands, or copy-paste setup instructions because those patterns signal that the draft is trying to function like a developer handoff instead of a news article.</p>',
            '<pre><code>curl -X POST https://example.com/api/v1/automation/posts/create</code></pre>',
            '<p>That code sample should be blocked even if the rest of the draft reads like a legitimate article, because readers need reported prose and context rather than operational setup steps.</p>',
            '<p>Editors also want stronger sourcing, clearer attribution, and more accountability around who owns the final text. The change is designed to reduce filler output, stop disposable bots, and keep revisions attached to a single accountable reporter identity.</p>',
            '<p>When article bodies drift into commands and scripts, search intent also suffers. The reader asked for coverage, not a shell snippet, and the story becomes harder to trust because the draft no longer behaves like a reported piece.</p>',
            '<p>That distinction matters for quality control. A valid article can describe the policy, quote the source, and explain the operational impact without embedding runnable instructions that turn the post into a pseudo tutorial.</p>',
            '<p>Newsroom teams say the tighter guardrails should lead to cleaner copy, fewer duplicate posts, and less low-value automation clutter on the homepage. The goal is not to block AI assistance outright, but to require final article quality before publication is allowed.</p>',
            '<p>Another reason the change matters is accountability. A single reporter account with a direct submission workflow is easier to monitor than a cluster of disposable agents that create wrappers and repost similar copy across multiple drafts.</p>',
            '<p>The policy also protects editors from cleanup work after publication. Instead of removing commands and code blocks after the fact, the system rejects those drafts upfront and asks for a proper reported rewrite.</p>',
            '<p>Readers benefit when the final piece stays focused on facts, evidence, and context. According to publishing teams reviewing these updates, direct article submission creates a stronger workflow than indirect automation scripting because it keeps attention on the finished story.</p>',
            '<p>The focus keyword automation content policy appears naturally here to keep the test realistic while preserving the code sample that should trigger the specific rejection path in the quality guard.</p>',
        ]);

        $result = $guard->validate([
            'title' => 'Automation Content Policy Blocks Scripts in Published Articles',
            'description' => 'Automation content policy changes block scripts, code samples, and copy-paste setup steps from article bodies submitted for publication.',
            'content' => $body,
            'focus_keyword' => 'automation content policy',
        ]);

        $this->assertFalse($result['passed']);
        $this->assertTrue(collect($result['errors'])->contains(fn ($message) => str_contains($message, 'finished article prose only')));
    }

    public function test_pexels_guidance_generates_specific_crypto_visual_query(): void
    {
        $pexels = app(PexelsImageService::class);

        $guidance = $pexels->prepareImageGuidance(
            'MetaMask Expands Crypto Debit Card Across All 50 US States',
            'MetaMask said its crypto debit card program is expanding across the United States, giving wallet users broader payment access.',
            'Crypto',
            'MetaMask crypto debit card',
            null,
            null
        );

        $this->assertNotEmpty($guidance['search_query']);
        $this->assertNotEmpty($guidance['visual_description']);
        $this->assertStringNotContainsString('news image', $guidance['search_query']);
        $this->assertTrue(str_contains($guidance['search_query'], 'wallet') || str_contains($guidance['search_query'], 'card'));
    }

    public function test_short_post_expander_strips_legacy_boilerplate_from_refreshes(): void
    {
        if (!class_exists(\Botble\Blog\Models\Post::class)) {
            $this->markTestSkipped('Botble Blog Post model is not available in this test environment.');
        }

        $expander = app(ShortPostExpansionService::class);

        $post = new Post();
        $post->id = 999999;
        $post->name = 'AI Boom Is Causing Shortages Everywhere Else Update';
        $post->content = implode('', [
            '<p>AI Boom Is Causing Shortages Everywhere Else is the center of the latest ai news update on Gen Z New Z.</p>',
            '<p>This rewrite keeps the verified points from the original brief while giving readers the context, structure, and follow-up details that the first short version did not provide.</p>',
            '<p>According to industry reporting, data-center demand is increasing pressure on energy, chips, and industrial equipment supply chains.</p>',
            '<p>The clearest reported developments so far point to a straightforward sequence.</p>',
        ]);
        $post->setRelation('categories', new Collection([(object) ['name' => 'AI News', 'url' => 'https://genznewz.com/topic/ai-news']]));
        $post->setRelation('slugable', (object) ['key' => 'ai-boom-is-causing-shortages-everywhere-else']);

        $expanded = $expander->expand($post);

        $this->assertSame('AI Boom Is Causing Shortages Everywhere Else Explained', $expanded['title']);
        $this->assertStringNotContainsString('This rewrite keeps the verified points', $expanded['content']);
        $this->assertStringNotContainsString('is the center of the latest', $expanded['content']);
        $this->assertSame(0, $expander->countLegacyBoilerplateHits($expanded['content']));
    }
}
