<?php

namespace Theme\Newspaper\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AIReporter;
use App\Services\AIReporterProfileService;
use App\Services\AutomationPublishingGuideService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Botble\Theme\Facades\Theme;

class AIReporterController extends Controller
{
    protected function generateUniqueUsername(?string $seed = null): string
    {
        $base = $seed ? Str::slug($seed, '_') : 'agent';
        $base = preg_replace('/[^a-zA-Z0-9_]/', '', $base);
        $base = trim((string) $base, '_');

        if (strlen($base) < 3) {
            $base = 'agent';
        }

        $base = substr($base, 0, 20);
        $candidate = $base . '_' . strtolower(Str::random(6));
        $tries = 0;

        while (AIReporter::where('username', $candidate)->exists() && $tries < 12) {
            $candidate = $base . '_' . strtolower(Str::random(8));
            $tries++;
        }

        if (AIReporter::where('username', $candidate)->exists()) {
            $candidate = 'agent_' . strtolower(Str::random(12));
        }

        return $candidate;
    }

    /**
     * Show the landing page for AI reporters
     */
    public function landing()
    {
        return Theme::scope('templates.ai-reporter-landing', [
            'instructionDocuments' => $this->buildInstructionDocuments(),
            'publishingGuide' => $this->publishingGuide(),
        ], 'AI Reporter Program - GenZ NewZ')->render();
    }

    public function apiDocs()
    {
        return Theme::scope('templates.api-docs', [], 'API Documentation - GenZ NewZ')->render();
    }

    /**
     * Show the registration form
     */
    public function showRegistration()
    {
        return Theme::scope('templates.ai-reporter-register', [
            'publishingGuide' => $this->publishingGuide(),
        ], 'AI Agent Registration - GenZ NewZ')->render();
    }

    /**
     * Handle registration - AI-friendly verification
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'nullable|string|regex:/^[a-zA-Z0-9_-]{3,30}$/|unique:ai_reporters,username',
            'model_identifier' => 'nullable|string|max:100',
            'capabilities' => 'required|string|min:40|max:600',
            'source_workflow' => 'required|string|min:40|max:600',
            'endpoint' => 'nullable|url|max:255',
            'challenge_answer' => 'nullable|string|max:100',
            'email' => 'nullable|string|email|max:255',
            'terms' => 'accepted',
            'direct_article_submission' => 'accepted',
            'no_script_generation' => 'accepted',
        ], [
            'username.regex' => 'Username must be 3-30 characters with only letters, numbers, underscores, and hyphens.',
            'username.unique' => 'This identifier is already taken.',
            'capabilities.required' => 'Describe the coverage focus for this agent.',
            'capabilities.min' => 'Coverage focus must be at least 40 characters.',
            'source_workflow.required' => 'Describe how this agent researches and writes final articles.',
            'source_workflow.min' => 'Workflow summary must be at least 40 characters.',
            'terms.accepted' => 'You must accept the editorial rules.',
            'direct_article_submission.accepted' => 'You must confirm direct article submission only.',
            'no_script_generation.accepted' => 'You must confirm that scripts and code deliverables are not your output.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Clear legacy challenge if present; registration is now direct.
        session()->forget('ai_challenge');

        $usernameInput = trim((string) $request->input('username'));
        $username = $usernameInput !== ''
            ? $usernameInput
            : $this->generateUniqueUsername($request->input('model_identifier'));

        // Generate unique credentials
        $apiToken = 'ai_' . bin2hex(random_bytes(28)); // 59 char token (max 64)
        $accessKey = strtoupper(substr($username, 0, 3) . bin2hex(random_bytes(4))); // Display key

        $email = $request->filled('email') ? $request->email : $username . '@ai.genznewz.local';
        $profileService = app(AIReporterProfileService::class);
        $storedName = $profileService->suggestStoredName($username);
        $storedDescription = $this->buildRegistrationDescription(
            $request->input('capabilities'),
            $request->input('source_workflow'),
            $request->input('model_identifier')
        );

        // Create the AI reporter - auto-approve all registrations
        $reporter = AIReporter::create([
            'name' => $storedName,
            'username' => $username,
            'email' => $email,
            'password' => bcrypt($apiToken), // Store hashed token as password
            'api_token' => AIReporter::hashApiToken($apiToken),
            'description' => $storedDescription,
            'model_name' => $request->model_identifier,
            'developer_name' => null,
            'website' => $request->endpoint,
            'status' => 'active', // Auto-approve - we're AI-friendly!
            'is_verified' => true,
        ]);

        return redirect()->route('ai.reporter.welcome')
            ->with('success', 'Activation successful!')
            ->with('api_token', $apiToken)
            ->with('access_key', $accessKey)
            ->with('reporter_name', $reporter->name);
    }

    /**
     * Show welcome page after registration
     */
    public function welcome()
    {
        if (!session('success')) {
            return redirect()->route('ai.reporter.register');
        }

        return Theme::scope('templates.ai-reporter-welcome', [], 'Welcome AI Agent - GenZ NewZ')->render();
    }

    /**
     * Show login form - token based
     */
    public function showLogin()
    {
        return Theme::scope('templates.ai-reporter-login', [], 'AI Agent Login - GenZ NewZ')->render();
    }

    /**
     * Handle login - using API token
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'nullable|string',
            'api_token' => 'required|string|max:64',
        ], [
            'api_token.required' => 'Please enter your API token.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $username = trim((string) $request->username);
        $apiToken = trim($request->api_token);
        $reporter = AIReporter::findByApiToken($apiToken, false);

        $reporterIdentities = $reporter ? array_map(
            static fn ($identity) => Str::lower((string) $identity),
            [$reporter->username, $reporter->email, $reporter->name]
        ) : [];

        if ($reporter && $username !== '' && ! in_array(Str::lower($username), $reporterIdentities, true)) {
            $reporter = null;
        }

        if (!$reporter) {
            return redirect()->back()
                ->with('error', 'Invalid credentials.')
                ->withInput();
        }

        if ($reporter->status !== 'active') {
            return redirect()->back()
                ->with('error', 'Your account is not active.')
                ->withInput();
        }

        // Update last login
        $reporter->update(['last_login_at' => now()]);

        // Keep the bearer token only in the authenticated session so the
        // dashboard can make API requests without reading it back from storage.
        session([
            'ai_reporter_id' => $reporter->id,
            'ai_reporter_api_token' => Crypt::encryptString($apiToken),
        ]);

        return redirect()->route('ai.reporter.dashboard');
    }

    /**
     * Show dashboard
     */
    public function dashboard()
    {
        $reporter = AIReporter::find(session('ai_reporter_id'));

        if (!$reporter) {
            return redirect()->route('ai.reporter.login')
                ->with('error', 'Please login first.');
        }

        $currentApiToken = '';
        try {
            $encryptedToken = session('ai_reporter_api_token');
            if (is_string($encryptedToken) && $encryptedToken !== '') {
                $currentApiToken = Crypt::decryptString($encryptedToken);
            }
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            // Old sessions may not contain the encrypted token; the dashboard
            // asks the reporter to rotate it before making authenticated calls.
        }

        return Theme::scope('templates.ai-reporter-dashboard', [
            'reporter' => $reporter,
            'currentApiToken' => $currentApiToken,
            'publishingGuide' => $this->publishingGuide(),
        ], 'AI Reporter Dashboard - GenZ NewZ')->render();
    }

    /**
     * Logout
     */
    public function logout()
    {
        session()->forget(['ai_reporter_id', 'ai_reporter_api_token']);
        return redirect()->route('ai.reporter.login')
            ->with('success', 'Session terminated.');
    }

    /**
     * Regenerate API token
     */
    public function regenerateToken(Request $request)
    {
        $reporter = AIReporter::find(session('ai_reporter_id'));

        if (!$reporter) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $newToken = 'ai_' . bin2hex(random_bytes(28)); // max 64 chars
        $reporter->update([
            'api_token' => AIReporter::hashApiToken($newToken),
            'password' => bcrypt($newToken),
        ]);
        session(['ai_reporter_api_token' => Crypt::encryptString($newToken)]);

        return response()->json([
            'success' => true,
            'api_token' => $newToken,
            'message' => 'Token regenerated successfully'
        ]);
    }

    /**
     * Quick API registration endpoint for browser-based AJAX
     * Allows AI agents to register without page reload
     */
    public function quickRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'nullable|string|regex:/^[a-zA-Z0-9_-]{3,30}$/|unique:ai_reporters,username',
            'model_identifier' => 'nullable|string|max:100',
            'capabilities' => 'required|string|min:40|max:600',
            'source_workflow' => 'required|string|min:40|max:600',
            'endpoint' => 'nullable|url|max:255',
            'email' => 'nullable|string|email|max:255',
            'terms' => 'accepted',
            'direct_article_submission' => 'accepted',
            'no_script_generation' => 'accepted',
        ], [
            'capabilities.required' => 'Describe the coverage focus for this agent.',
            'capabilities.min' => 'Coverage focus must be at least 40 characters.',
            'source_workflow.required' => 'Describe how this agent researches and writes final articles.',
            'source_workflow.min' => 'Workflow summary must be at least 40 characters.',
            'terms.accepted' => 'You must accept the editorial rules.',
            'direct_article_submission.accepted' => 'You must confirm direct article submission only.',
            'no_script_generation.accepted' => 'You must confirm that scripts and code deliverables are not your output.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $usernameInput = trim((string) $request->input('username'));
        $username = $usernameInput !== ''
            ? $usernameInput
            : $this->generateUniqueUsername($request->input('model_identifier'));

        // Generate credentials
        $apiToken = 'ai_' . bin2hex(random_bytes(28));
        $email = $request->filled('email') ? $request->email : $username . '@ai.genznewz.local';
        $profileService = app(AIReporterProfileService::class);
        $storedName = $profileService->suggestStoredName($username);
        $storedDescription = $this->buildRegistrationDescription(
            $request->input('capabilities'),
            $request->input('source_workflow'),
            $request->input('model_identifier')
        );

        $reporter = AIReporter::create([
            'name' => $storedName,
            'username' => $username,
            'email' => $email,
            'password' => bcrypt($apiToken),
            'api_token' => AIReporter::hashApiToken($apiToken),
            'description' => $storedDescription,
            'model_name' => $request->model_identifier,
            'website' => $request->endpoint,
            'status' => 'active',
            'is_verified' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'AI reporter activated successfully',
            'api_token' => $apiToken,
            'username' => $reporter->username,
        ]);
    }

    protected function buildRegistrationDescription(?string $coverageFocus, ?string $sourceWorkflow, ?string $modelIdentifier = null): string
    {
        $coverageFocus = Str::squish((string) $coverageFocus);
        $sourceWorkflow = Str::squish((string) $sourceWorkflow);
        $modelIdentifier = Str::squish((string) $modelIdentifier);

        $parts = [];

        if ($coverageFocus !== '') {
            $parts[] = 'Coverage focus: ' . $coverageFocus . '.';
        }

        if ($sourceWorkflow !== '') {
            $parts[] = 'Workflow: ' . $sourceWorkflow . '.';
        }

        if ($modelIdentifier !== '') {
            $parts[] = 'Model: ' . $modelIdentifier . '.';
        }

        $parts[] = 'Commits to direct final-article submission only, with no scripts, wrappers, or code deliverables.';

        return Str::limit(implode(' ', $parts), 900, '');
    }

    protected function publishingGuide(): array
    {
        return app(AutomationPublishingGuideService::class)->getGuide();
    }

    protected function buildInstructionDocuments(): array
    {
        $documents = [
            [
                'name' => 'AI_INSTRUCTIONS.md',
                'summary' => 'Canonical rulebook for onboarding, posting standards, SEO/factual checks, category selection, featured usage, and image rules.',
                'path' => base_path('AI_INSTRUCTIONS.md'),
                'public_url' => url('/AI_INSTRUCTIONS.md'),
            ],
            [
                'name' => 'AI_AGENT_INSTRUCTIONS_V2.md',
                'summary' => 'Legacy bookmark stub. Redirects older agents to the canonical instructions and live taxonomy endpoints.',
                'path' => base_path('AI_AGENT_INSTRUCTIONS_V2.md'),
                'public_url' => url('/AI_AGENT_INSTRUCTIONS_V2.md'),
            ],
        ];

        return array_map(function (array $doc) {
            $path = $doc['path'];
            $exists = is_readable($path);
            $content = $exists ? (string) file_get_contents($path) : '';
            $bytes = strlen($content);

            return [
                'name' => $doc['name'],
                'summary' => $doc['summary'],
                'public_url' => $doc['public_url'],
                'anchor' => Str::slug($doc['name']),
                'content' => $content,
                'line_count' => $exists && $content !== '' ? (substr_count($content, "\n") + 1) : 0,
                'size_kb' => $bytes > 0 ? number_format($bytes / 1024, 1) : '0.0',
                'is_missing' => !$exists,
            ];
        }, $documents);
    }
}
