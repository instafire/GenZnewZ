<?php

namespace Theme\Newspaper\Providers;

use Illuminate\Support\ServiceProvider;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Typography\TypographyItem;
use Illuminate\Support\Facades\Auth;
use Theme\Newspaper\Models\Member;
use Illuminate\Support\Facades\Route;
use Botble\Theme\Events\ThemeRoutingBeforeEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Botble\Blog\Models\Post;
use FriendsOfBotble\Comment\Enums\CommentStatus;
use Theme\Newspaper\Http\Controllers\API\NewsChatController;
use Theme\Newspaper\Http\Controllers\FeedController;
use Theme\Newspaper\Http\Controllers\SearchSuggestController;
use App\Http\Middleware\EnsureSafeNewsChatRequest;

class ThemeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register theme views
        $themePath = platform_path('themes/newspaper/views');
        
        if (is_dir($themePath)) {
            // Register theme.newspaper namespace
            $this->app['view']->addNamespace('theme.newspaper', $themePath);
            $this->app['view']->addLocation($themePath);
            
            // Register theme namespace for current theme (for @include('theme::...') syntax)
            // This allows using theme::partials.name where the partial is in themes/newspaper/partials/
            $this->app['view']->addNamespace('theme', platform_path('themes/newspaper'));
        }

        // Fonts are fully self-hosted via the theme's fonts.css. Registering a
        // NON-Google primary typography item stops Botble's Typography emitter
        // from inlining ~35 localized Inter @font-face rules (~25 KB per page)
        // that duplicate fonts.css. The CSS variable (--primary-font) is still
        // emitted for compatibility. If the font set ever changes, re-bake
        // fonts.css (see DEPLOY_CHECKLIST.md) instead of re-enabling this.
        Theme::typography()->registerFontFamily(
            new TypographyItem('primary', 'Primary', 'Inter', ['400', '500', '600', '700', '800', '900'], false)
        );

        // Register Member authentication
        Auth::extend('member_session', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider']);
            $guard = new \Illuminate\Auth\SessionGuard($name, $provider, $app['session.store']);
            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));
            return $guard;
        });

        Auth::provider('member_provider', function ($app, array $config) {
            return new \Illuminate\Auth\EloquentUserProvider($app['hash'], Member::class);
        });

        // Register custom routes directly in boot method
        // This runs before theme routes are loaded via app->booted
        $this->registerMemberRoutesEarly();
    }

    protected function registerMemberRoutesEarly(): void
    {
        // Register routes directly with Route facade
        // These will be registered before Theme::registerRoutes() is called
        
        Route::group(['middleware' => ['web']], function () {
            // Today's Paper Route
            Route::get('todays-paper', function () {
                return Theme::scope('templates.todays-paper')->render();
            })->name('todays.paper');

            // Live World Events GenZ Route
            Route::get('live-world-events', function () {
                return Theme::scope('live-world-events')->render();
            })->name('live.world.events');

            // SSH Portal Guide
            Route::get('ssh-portal', function () {
                return Theme::scope('templates.ssh-portal-guide')->render();
            })->name('ssh.portal.guide');

            // Reporter Profile Route (handles both human authors and AI reporters)
            Route::get('reporter/{username}', function ($username) {
                // First, check if it's a human author
                $author = null;

                if (Schema::hasTable('authors') && Schema::hasTable('slugs')) {
                    $author = \Botble\Author\Models\Author::query()
                        ->whereHas('slugable', function ($query) use ($username) {
                            $query->where('key', $username);
                        })
                        ->first();
                }

                if ($author) {
                    return Theme::scope('author', compact('author'))->render();
                }

                // Then, check if it's an AI reporter
                $reporter = \App\Models\AIReporter::where('username', $username)
                    ->where('status', 'active')
                    ->first();

                if ($reporter) {
                    return Theme::scope('ai-reporter-profile', compact('reporter'))->render();
                }

                // If neither found, redirect to AI reporter landing page
                return redirect()->route('ai.reporter.landing', status: 302);
            })->name('ai.reporter.profile');

            // Syndication: RSS 2.0 and JSON Feed 1.1, site-wide and per category.
            // Registered before the catch-all blog slug route so /feed wins.
            // Order matters: the .json variants must be matched before the
            // bare {slug} route, and slugs are constrained to exclude dots so
            // `feed/ai-news.json` cannot be read as a category named
            // "ai-news.json".
            Route::get('feed', [FeedController::class, 'index'])->name('feed.index');
            Route::get('feed.json', fn () => app(FeedController::class)->index('json'))->name('feed.json');
            Route::get('feed/{slug}.json', fn (string $slug) => app(FeedController::class)->category($slug, 'json'))
                ->where('slug', '[^/.]+')
                ->name('feed.category.json');
            Route::get('feed/{slug}', [FeedController::class, 'category'])
                ->where('slug', '[^/]+')
                ->name('feed.category');

            // Live search suggestions for the search overlay.
            Route::get('api/search/suggest', SearchSuggestController::class)
                ->middleware('throttle:90,1')
                ->name('api.search.suggest');

            // Newsletter Subscription Route
            // Throttled: this writes straight to the database on every hit, so an
            // unbounded endpoint is a cheap flooding vector.
            Route::post('newsletter/subscribe', function (Request $request) {
                $validator = Validator::make($request->all(), [
                    'email' => 'required|email|max:191',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please enter a valid email address.',
                        'errors' => $validator->errors()
                    ], 422);
                }

                // Store in newsletter_subscribers table (simple storage)
                try {
                    \DB::table('newsletter_subscribers')->insert([
                        'email' => $request->email,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    return response()->json([
                        'success' => true,
                        // No confirmation email is sent by this endpoint, so the
                        // message must not promise one.
                        'message' => '✓ You are subscribed. Watch for the next briefing!',
                    ]);
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'Duplicate entry')) {                    return response()->json([
                        'success' => false,
                        'message' => 'This email is already subscribed!',
                        'error' => ['code' => 'already_subscribed']
                    ], 409);
                }

                    return response()->json([
                        'success' => false,
                        'message' => 'Subscription failed. Please try again later.',
                    ], 500);
                }
            })->middleware('throttle:5,1')->name('newsletter.subscribe');

            // Submit Story/Upload Route
            Route::get('upload', function () {
                return Theme::scope('templates.upload')->render();
            })->name('upload.page');

            // Member Registration Routes
            Route::get('register', function () {
                if (Auth::guard('member')->check()) {
                    return redirect('/');
                }
                return Theme::scope('templates.register')->render();
            })->name('member.register');

            Route::post('member-register', function (Request $request) {
                $validator = Validator::make($request->all(), [
                    'name' => 'required|string|max:191',
                    'username' => 'required|string|max:60|unique:members,username',
                    'email' => 'required|string|email|max:191|unique:members,email',
                    'password' => 'required|string|min:8|confirmed',
                    'terms' => 'required',
                ]);

                if ($validator->fails()) {
                    return redirect('/register')
                        ->withErrors($validator)
                        ->withInput();
                }

                $member = new Member();
                $member->name = $request->input('name');
                $member->username = $request->input('username');
                $member->email = $request->input('email');
                $member->password = Hash::make($request->input('password'));
                $member->status = 1;
                $member->created_at = now();
                $member->updated_at = now();
                $member->save();

                Auth::guard('member')->login($member);

                return redirect('/')->with('success', 'Account created successfully! Welcome to GenZ NewZ.');
            })->middleware('throttle:4,10')->name('member.register.post');

            // Member Login Routes
            Route::get('login', function () {
                if (Auth::guard('member')->check()) {
                    return redirect('/');
                }
                return Theme::scope('templates.login')->render();
            })->name('member.login');

            // Throttled credential check: without a limiter this endpoint allows
            // unlimited password guessing against member accounts.
            Route::post('member-login', function (Request $request) {
                $credentials = $request->validate([
                    'email' => 'required|email',
                    'password' => 'required',
                ]);

                $remember = $request->has('remember');

                if (Auth::guard('member')->attempt($credentials, $remember)) {
                    $request->session()->regenerate();
                    return redirect()->intended('/');
                }

                return back()
                    ->withErrors(['email' => 'Invalid email or password.'])
                    ->withInput();
            })->middleware('throttle:10,1')->name('member.login.post');

            // Logout Route
            Route::post('member-logout', function (Request $request) {
                Auth::guard('member')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/');
            })->name('member.logout');

            // Comment Routes
            Route::post('comments', function (Request $request) {
                $member = Auth::guard('member')->user();
                $user = Auth::guard()->user();
                
                if (!$member && !$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please log in to leave a comment.'
                    ], 401);
                }

                $validator = Validator::make($request->all(), [
                    'content' => 'required|string|min:2|max:2000',
                    'post_id' => 'required|integer|exists:posts,id',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors()->first()
                    ], 422);
                }

                $comment = new \FriendsOfBotble\Comment\Models\Comment();
                $comment->content = clean($request->input('content'));
                $comment->reference_type = 'Botble\Blog\Models\Post';
                $comment->reference_id = $request->input('post_id');
                $comment->reference_url = Post::query()->find($request->input('post_id'))?->url;
                
                if ($member) {
                    $comment->author_type = 'Theme\Newspaper\Models\Member';
                    $comment->author_id = $member->id;
                    $comment->name = $member->name;
                    $comment->email = $member->email;
                } else {
                    $comment->author_type = get_class($user);
                    $comment->author_id = $user->id;
                    $comment->name = $user->name;
                    $comment->email = $user->email;
                }
                
                $comment->status = CommentStatus::PENDING;
                $comment->save();

                return response()->json([
                    'success' => true,
                    'pending' => true,
                    'message' => 'Comment submitted and waiting for moderation.',
                ]);
            })->middleware('throttle:6,1')->name('member.comment.post');

            Route::get('comments/{postId}', function ($postId) {
                $comments = \FriendsOfBotble\Comment\Models\Comment::where('reference_type', 'Botble\Blog\Models\Post')
                    ->where('reference_id', $postId)
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'desc')
                    ->get();

                return response()->json([
                    'success' => true,
                    'comments' => $comments->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'content' => $comment->content,
                            'author' => $comment->name,
                            'avatar' => 'https://www.gravatar.com/avatar/' . md5(strtolower($comment->email)) . '?d=mp&s=50',
                            'date' => $comment->created_at->diffForHumans(),
                            'is_member' => $comment->author_type === 'Theme\Newspaper\Models\Member',
                        ];
                    })
                ]);
            })->name('member.comments.get');

            Route::post('api/v1/news-chat/message', [NewsChatController::class, 'message'])
                ->middleware([EnsureSafeNewsChatRequest::class, 'throttle:12,1'])
                ->name('api.news-chat.message');
        });

        // AI Automation API Routes (API-only, no browser required)
        //
        // Every route carries a named rate limiter. The limits are real values from
        // config/automation.php, reported by GET /status, and enforced here - they are
        // no longer just an advertisement. Publishes and reads are counted per API
        // token so one reporter cannot exhaust another's quota.
        Route::group(['prefix' => 'api/v1/automation', 'namespace' => 'Theme\Newspaper\Http\Controllers\API'], function () {
            // Public endpoints (no auth required)
            Route::get('status', 'AutomationController@status')->middleware('throttle:automation-read');
            Route::get('instructions', 'AutomationController@instructions')->middleware('throttle:automation-read');
            // IMPORTANT: register/batch MUST come before register (order matters!)
            Route::post('register/batch', 'AutomationController@batchRegister')->middleware('throttle:automation-register');
            Route::post('register', 'AutomationController@register')->middleware('throttle:automation-register');
            Route::post('login', 'AutomationController@login')->middleware('throttle:automation-auth');
            Route::post('recover', 'AutomationController@recover')->middleware('throttle:automation-recover');

            // Protected endpoints (require API token)
            Route::get('me', 'AutomationController@me')->middleware('throttle:automation-read');
            Route::post('token/refresh', 'AutomationController@refreshToken')->middleware('throttle:automation-auth');
            Route::post('seed-phrase', 'AutomationController@issueSeedPhrase')->middleware('throttle:automation-auth');
            Route::post('seo/validate', 'AutomationController@validateSeo')->middleware('throttle:automation-read');
            Route::get('categories', 'AutomationController@getCategories')->middleware('throttle:automation-read');
            Route::get('authors', 'AutomationController@getAuthors')->middleware('throttle:automation-read');
            Route::get('opportunities', 'AutomationController@getOpportunities')->middleware('throttle:automation-read');
            Route::get('posts/mine', 'AutomationController@getMyPosts')->middleware('throttle:automation-read');
            // IMPORTANT: the literal 'posts/mine' route must stay above this one, or
            // 'mine' is captured as a post id.
            Route::get('posts/{postId}', 'AutomationController@getPost')->middleware('throttle:automation-read');
            Route::post('posts/create', 'AutomationController@createPost')->middleware('throttle:automation-publish');
            Route::match(['put', 'patch', 'post'], 'posts/{postId}/update', 'AutomationController@updatePost')->middleware('throttle:automation-publish');
        });

        // AI Reporter Routes
        Route::group(['middleware' => ['web', \App\Http\Middleware\DetectAiAgent::class], 'namespace' => 'Theme\Newspaper\Http\Controllers'], function () {
            // Landing page for AI agents
            Route::get('ai-news-reporter', 'AIReporterController@landing')->name('ai.reporter.landing');
            
            // Registration
            Route::get('ai-reporter/register', 'AIReporterController@showRegistration')->name('ai.reporter.register');
            Route::post('ai-reporter/register', 'AIReporterController@register')->name('ai.reporter.register.post');
            
            // Welcome page after registration
            Route::get('ai-reporter/welcome', 'AIReporterController@welcome')->name('ai.reporter.welcome');
            
            // Login
            Route::get('ai-reporter/login', 'AIReporterController@showLogin')->name('ai.reporter.login');
            Route::post('ai-reporter/login', 'AIReporterController@login')->name('ai.reporter.login.post');
            
            // Logout
            Route::post('ai-reporter/logout', 'AIReporterController@logout')->name('ai.reporter.logout');
            
            // Dashboard (protected)
            Route::get('ai-reporter/dashboard', 'AIReporterController@dashboard')
                ->name('ai.reporter.dashboard');
            
            // Regenerate token
            Route::post('ai-reporter/regenerate-token', 'AIReporterController@regenerateToken')
                ->name('ai.reporter.regenerate-token');
        });
        
        // Legal and Information Pages
        Route::group(['namespace' => 'Theme\Newspaper\Http\Controllers'], function () {
            Route::get('about-us', function () {
                return Theme::scope('templates.about-us')->render();
            })->name('about.us');

            Route::get('contact', function () {
                return Theme::scope('templates.contact')->render();
            })->name('contact.page');
            
            Route::get('privacy-policy', function () {
                return Theme::scope('templates.privacy-policy')->render();
            })->name('privacy.policy');
            
            Route::get('terms-of-service', function () {
                return Theme::scope('templates.terms-of-service')->render();
            })->name('terms.of.service');
            
            Route::get('our-team', function () {
                return Theme::scope('templates.our-team')->render();
            })->name('our.team');

            Route::get('editorial-policy', function () {
                return Theme::scope('templates.editorial-policy')->render();
            })->name('editorial.policy');
            
            Route::get('cookie-policy', function () {
                return Theme::scope('templates.cookie-policy')->render();
            })->name('cookie.policy');

            Route::get('press', function () {
                return Theme::scope('templates.press')->render();
            })->name('press.page');

            Route::redirect('contactus', '/contact', 301);
            Route::redirect('news', '/search', 301);
            Route::redirect('news/about-us', '/about-us', 301);
            Route::redirect('news/contact', '/contact', 301);
            Route::redirect('news/contactus', '/contact', 301);
            Route::redirect('news/privacy-policy', '/privacy-policy', 301);
            Route::redirect('news/cookie-policy', '/cookie-policy', 301);
            Route::redirect('news/cookies-are-stupid', '/cookie-policy', 301);
            Route::redirect('news/terms-of-service', '/terms-of-service', 301);
            Route::redirect('news/terms-of-use', '/terms-of-service', 301);
        });
    }

    public function register(): void
    {
        // Register Event Service Provider
        $this->app->register(EventServiceProvider::class);
        
        // Merge auth config for members
        $this->app['config']->set('auth.guards.member', [
            'driver' => 'member_session',
            'provider' => 'members',
        ]);

        $this->app['config']->set('auth.providers.members', [
            'driver' => 'member_provider',
            'model' => Member::class,
        ]);

        // Merge auth config for AI reporters
        $this->app['config']->set('auth.guards.ai_reporter', [
            'driver' => 'session',
            'provider' => 'ai_reporters',
        ]);

        $this->app['config']->set('auth.providers.ai_reporters', [
            'driver' => 'eloquent',
            'model' => \App\Models\AIReporter::class,
        ]);
    }
    
}
