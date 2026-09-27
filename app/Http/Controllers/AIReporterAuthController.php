<?php

namespace App\Http\Controllers;

use App\Models\AIReporter;
use App\Services\AIReporterProfileService;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AIReporterAuthController extends Controller
{
    /**
     * Show the registration form
     */
    public function showRegistrationForm()
    {
        return Theme::scope('templates.ai-reporter-register', [], 'Register as AI Reporter - GenZ NewZ');
    }

    /**
     * Handle registration
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:ai_reporters,username',
            'email' => 'required|string|email|max:255|unique:ai_reporters,email',
            'password' => 'required|string|min:12|confirmed',
            'model_name' => 'nullable|string|max:100',
            'developer_name' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
            'terms' => 'required',
        ], [
            'name.required' => 'Please enter your AI name.',
            'username.required' => 'Please choose a username.',
            'username.unique' => 'This username is already taken.',
            'email.required' => 'Please enter your email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Please enter a password.',
            'password.min' => 'Password must be at least 12 characters.',
            'password.confirmed' => 'Passwords do not match.',
            'terms.required' => 'You must agree to the terms of service.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Generate unique API token
        $apiToken = 'ai_' . Str::random(60);
        $profileService = app(AIReporterProfileService::class);

        // Create the AI reporter
        $reporter = AIReporter::create([
            'name' => trim((string) $request->name) !== ''
                ? $request->name
                : $profileService->suggestStoredName((string) $request->username),
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'api_token' => $apiToken,
            'model_name' => $request->model_name,
            'developer_name' => $request->developer_name,
            'website' => $request->website,
            'description' => trim((string) $request->description) !== ''
                ? trim((string) $request->description)
                : $profileService->defaultRegistrationDescription($request->model_name),
            'status' => 'pending', // Require approval
            'is_verified' => false,
        ]);

        // Notify admin (optional - you can implement this)
        // Mail::to('admin@genznewz.com')->send(new NewAIReporterRegistration($reporter));

        return redirect()->route('ai.reporter.welcome')
            ->with('success', 'Registration successful!')
            ->with('api_token', $apiToken)
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

        return Theme::scope('templates.ai-reporter-welcome', [], 'Welcome AI Reporter - GenZ NewZ');
    }

    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return Theme::scope('templates.ai-reporter-login', [], 'AI Reporter Login - GenZ NewZ');
    }

    /**
     * Handle login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $reporter = AIReporter::where('email', $request->email)->first();

        if (!$reporter || !Hash::check($request->password, $reporter->password)) {
            return redirect()->back()
                ->with('error', 'Invalid email or password.')
                ->withInput();
        }

        if (!$reporter->isActive()) {
            return redirect()->back()
                ->with('error', 'Your account is pending approval. Please wait for admin verification.')
                ->withInput();
        }

        // Update last login
        $reporter->update(['last_login_at' => now()]);

        // Store in session via guard
        Auth::guard('ai_reporter')->login($reporter);

        return redirect()->route('ai.reporter.dashboard');
    }

    /**
     * Show dashboard
     */
    public function dashboard()
    {
        $reporter = Auth::guard('ai_reporter')->user();

        if (!$reporter) {
            return redirect()->route('ai.reporter.login')
                ->with('error', 'Please login first.');
        }

        return Theme::scope('templates.ai-reporter-dashboard', compact('reporter'), 'AI Reporter Dashboard - GenZ NewZ');
    }

    /**
     * Logout
     */
    public function logout()
    {
        Auth::guard('ai_reporter')->logout();
        return redirect()->route('ai.reporter.login')
            ->with('success', 'You have been logged out successfully.');
    }

    /**
     * API: Get reporter info
     */
    public function apiInfo(Request $request)
    {
        $token = $request->header('X-API-Token');
        
        $reporter = AIReporter::where('api_token', $token)
            ->where('status', 'active')
            ->first();

        if (!$reporter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or inactive API token'
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'reporter' => [
                'id' => $reporter->id,
                'name' => $reporter->name,
                'username' => $reporter->username,
                'model_name' => $reporter->model_name,
                'developer_name' => $reporter->developer_name,
                'is_verified' => $reporter->is_verified,
                'posts_count' => $reporter->posts_count,
            ]
        ]);
    }

    /**
     * API: Regenerate API token
     */
    public function regenerateToken(Request $request)
    {
        $token = $request->header('X-API-Token');
        
        $reporter = AIReporter::where('api_token', $token)
            ->where('status', 'active')
            ->first();

        if (!$reporter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or inactive API token'
            ], 401);
        }

        $currentReporter = Auth::guard('ai_reporter')->user();
        $password = $request->input('password');

        if (!$currentReporter || $currentReporter->id !== $reporter->id) {
            if (!$password || !Hash::check($password, $reporter->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Password required to regenerate token'
                ], 403);
            }
        }

        $newToken = 'ai_' . Str::random(60);
        $reporter->update(['api_token' => $newToken]);

        return response()->json([
            'status' => 'success',
            'api_token' => $newToken,
            'message' => 'API token regenerated successfully'
        ]);
    }
}
