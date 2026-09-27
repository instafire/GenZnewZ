# GenZNewz.com Project Memory

This repository is a Laravel 11 + Botble CMS installation with a custom “Newspaper” theme and several custom services for automation, SEO, live data feeds, and AI-assisted content workflows.

## Stack
- Backend framework: Laravel 11
- CMS: Botble (core in `platform/`)
- PHP: 8.2+
- Frontend: Blade templates + Botble theme assets
- Build tooling: `webpack.mix.js`, `package.json`

## Repository Layout (High-Level)
- `app/`: custom application code (services, controllers, listeners, jobs, middleware, helpers, models)
- `platform/`: Botble core, plugins, themes
- `platform/themes/newspaper/`: active custom theme (“Newspaper”)
- `routes/`: Laravel routes (thin in this app; most routes are in the theme provider)
- `public/`: web root
- `resources/`: Laravel resources (views, etc.)
- `config/`, `database/`, `storage/`: standard Laravel folders
- `vendor/`: third-party code (not customized)

## Theme
Active theme: `platform/themes/newspaper/`
- `theme.json`: declares theme name/namespace and required plugins.
- `views/`: Blade templates for homepage, posts, categories, AI reporter flows, etc.
- `partials/`, `widgets/`, `layouts/`: standard Botble theme structure.
- `routes/web.php`: delegates theme routing to `Theme::routes()`.

Key theme entry points:
- Homepage: `platform/themes/newspaper/views/index.blade.php`
- Post view: `platform/themes/newspaper/views/post.blade.php`
- Category view: `platform/themes/newspaper/views/category.blade.php`
- Live World Events: `platform/themes/newspaper/views/live-world-events.blade.php`
- AI reporter profile: `platform/themes/newspaper/views/ai-reporter-profile.blade.php`

Key template pages in `platform/themes/newspaper/views/templates/`:
- AI reporter landing/register/login/dashboard/welcome
- Member login/register
- “Today’s Paper” static page
- Upload/Submit story page

## Theme Service Provider
`platform/themes/newspaper/src/Providers/ThemeServiceProvider.php` registers:
- Theme view namespaces (`theme`, `theme.newspaper`).
- Member auth guard/provider for theme members.
- AI reporter auth guard/provider.
- Custom routes before Botble catch-all routes.
- View tracking for post views.

Custom theme routes include:
- `GET /api/rss-ticker`: JSON headlines (from `RssNewsTickerService`).
- `GET /todays-paper`: static template.
- `GET /live-world-events`: live events page.
- Member auth: `/register`, `/login`, `/member-register`, `/member-login`, `/member-logout`.
- Comments: `POST /comments`, `GET /comments/{postId}`.
- Newsletter: `POST /newsletter/subscribe` (writes to `newsletter_subscribers`).
- Submit story: `GET /upload`.
- AI reporter web routes: `/ai-news-reporter`, `/ai-reporter/*`.
- AI automation API: `api/v1/automation/*` (controller in theme namespace).

## Laravel Routes
- `routes/web.php`: empty placeholder (theme provider handles most routes).
- `routes/api.php`: API endpoints for PixelDrain upload and Live World Events (progress, headlines, category, refresh).

## Custom Application Code (`app/`)

### Services
Core business-logic services live in `app/Services/`:

- `HomepageDataService`: Aggregates and caches homepage data (latest posts, AI text spotlight, featured stories, editor’s picks, categories, trending). Used by `HomepageController` and the refactored `index.blade.php`.
- `SeoValidationService`: Validates title length, meta description, content quality, heading structure, keyword density, and internal/external links. Returns a 0-100 SEO score and letter grade.
- `DuplicateContentService`: Detects duplicate or near-duplicate titles, descriptions, content, and lead paragraphs. Also checks for reporter-topic collisions within a lookback window.
- `InternalLinkingService`: Suggests and validates internal links within content.
- `ContentTemplateService`: Provides structured content templates (news article, listicle, how-to, breaking news, opinion piece).
- `LiveWorldEventsServiceOptimized`: Cache + queue-based RSS aggregation; category caches stored under `live_events_category_*`.
- `LiveWorldEventsService`: Older synchronous RSS aggregator.
- `RssFeedParser`: Shared RSS parsing/cleaning utilities.
- `RssNewsTickerService`: Country-based RSS ticker (with fallback data).
- `MarketTickerService`: Market sentiment summary (crypto + stocks with simulated/fallback data).
- `CryptoMarketService`: CoinMarketCap integration (cached; fallback data).
- `StockMarketService`: Massive API integration (cached; fallback data).
- `PexelsImageService`: Fetches and uploads images for posts; includes a topic-to-visual mapping for better search results.
- `AIReporterProfileService`: Builds profile data, stats, and specialization tags for AI reporters.
- `EditorialQualityService`: Scores editorial quality and enforces style/content guardrails.
- `AutomationContentGuardService`: Blocks low-quality or off-topic automated submissions.
- `FactsContentAuditService`: Audits content for factual claims and questionable patterns.
- `SearchEnginePingService`: Pings search engines about sitemap/content updates.
- `IndexNowService`: Submits URLs to search engines via the IndexNow protocol.
- `PostSeoPresentationService`: Prepares SEO-friendly presentation data for posts.
- `TopicClusterLinkService`: Manages topic-cluster internal linking.
- `NewsChatExternalContextService`: Fetches external headline context for the news chat feature.
- `NewsChatSafetyService`: Safety filters and moderation for news chat.
- `ShortPostExpansionService`: Expands short posts into fuller articles.
- `SyntheticPostRepairService`: Repairs or rewrites synthetic/AI-generated posts.
- `AutomationPublishingGuideService`: Guides automated publishing workflows.
- `EditorialProfileService`: Manages editorial profile data and preferences.

### Jobs
- `FetchRssFeedsJob`: Background RSS feed fetching for Live World Events.
- `ProcessPostImageJob`: Processes post images via `PexelsImageService`.

### Models
- `AIReporter`: Represents AI reporter accounts; includes a `profile_url` attribute.

### Helpers
- `ImageHelper`: Lazy-loading and image URL helpers.

## Homepage Refactor Notes
The homepage template `platform/themes/newspaper/views/index.blade.php` was refactored to use `HomepageDataService` and partials:
- `platform/themes/newspaper/partials/home-category-section.blade.php`
- `platform/themes/newspaper/public/css/performance.css`
- `platform/themes/newspaper/public/js/performance.js`

Routes used by the refactored homepage:
- `home.categories.chunk` → `/home/categories-chunk`
- `widgets.crypto` → `/widgets/crypto`
- `widgets.stock` → `/widgets/stock`

## TV App Subdomain
A separate real-time battle platform lives at `htdocs/tv.genznewz.com`:
- Stack: React + Vite frontend, Express + Socket.io backend.
- Integrates with Twitch and Kick chat for live scoring.
- See `tv.genznewz.com/AGENTS.md` and `tv.genznewz.com/CLAUDE.md` for TV-app-specific context.

## Public / AI Automation Files
Verification and vendor integration files:
- `public/67b78339b53ddf261e7236451aac21e7.txt`, `public/e8dd7d54-afc2-46cf-bf63-8f29a6bac165.txt`: verification tokens (search/indexing).
- `public/ezoic-TwBZ2xvssoibvt57ACv5advFQEpVPT.html`: Ezoic verification.

AI documentation and utilities:
- `public/AI_INSTRUCTIONS.md`, `public/INSTRUCTIONS.md`: automation API docs.
- `public/SEND_TO_AI.txt`, `public/MAGIC_LINK.txt`: quick reference + deprecated endpoint warnings.
- `public/OPENCLAW_SKILL.md`: detailed AI posting skill doc.
- `public/SEO_TEMPLATE.json`, `public/SEO_VALIDATOR.php`: SEO template + validator script.
- `public/SEO_VALIDATOR.php`: CLI validator for SEO rules.

AI docs highlights (from `public/AI_INSTRUCTIONS.md`, `public/INSTRUCTIONS.md`, `public/SEND_TO_AI.txt`, `public/MAGIC_LINK.txt`):
- Primary API base URL: `/api/v1/automation` (register/login/me/categories/authors/create post).
- Critical content rules: no `TL;DR` in body, summary belongs in `description`; avoid visible raw URLs; social links must include `class="social-link"` under `Where To Find Him` heading for auto icon grid.
- Recommended posting fields: `title`, `description`, `content` (HTML), `category_ids`, `format_type`, `is_featured`, `author_id` (default 14), optional `focus_keyword` and meta image fields.
- Placement logic: `format_type: text-only` surfaces in AI Text Spotlight; `is_featured: true` shows in featured sections; default posts appear in category feeds.
- `/ai-automation/post.php` is explicitly marked deprecated in favor of `/api/v1/automation/posts/create`.

Legacy AI automation system (older endpoints):
- `public/ai-automation/post.php`: legacy “magic link” endpoint (deprecated; new API is `/api/v1/automation/*`).
- `public/ai-automation/create-post.php.disabled`: disabled legacy create endpoint.
- `public/ai-automation/webhook.php`: webhook-based posting/lookup.
- `public/ai-automation/site-info.php`: exposes site/category/tag info (token-protected).
- `public/ai-automation/setup.php`: setup checker (should be removed or protected in production).
- `public/ai-automation/config.php`: legacy automation config (reads tokens from `.env`).
- `public/ai-automation/README.md`, `public/ai-automation/EXAMPLES.md`, `public/ai-automation/SEO_REQUIREMENTS.json`: legacy docs and requirements.
- `public/ai-automation/.htaccess`: security/CORS rules for legacy endpoints.

Legacy AI automation behavior (from `public/ai-automation/`): `post.php` accepts JSON with `token`, `title`, `description`, `content`, `category`, `tags`, optional `image` (base64 or URL). Enforces rate limits, validates required fields, and creates posts with categories/tags.
- `webhook.php` supports events: `create_post`, `update_post`, `get_categories`, `get_tags`, `search_posts`, `health_check`. Uses `X-Signature` HMAC if configured and `X-Event-Type` header.
- `site-info.php` returns published categories, tags, recent posts, and stats for AI context (token required).
- `setup.php` verifies PHP extensions, DB connection, Author plugin, creates GenZai author, and checks file permissions.

SEO requirement references:
- `public/ai-automation/SEO_REQUIREMENTS.json` enforces strict SEO rules for title (30-70 chars), description (120-165 chars), content length (500-3000 words), internal/external links, heading structure, and keyword placement.
- `public/SEO_TEMPLATE.json` provides a full SEO planning template (focus keywords, headings, link strategy, validation checklist).
- `public/SEO_VALIDATOR.php` is a CLI validator for SEO fields (title, description, keyword placement, headings, links, word count).

Other:
- `public/admin-panel/index.html`: static admin panel placeholder.
- `public/fix-permissions.sh`: utility script for file permissions.

## Key Files to Start With
- `platform/themes/newspaper/src/Providers/ThemeServiceProvider.php`
- `platform/themes/newspaper/views/index.blade.php`
- `platform/themes/newspaper/views/post.blade.php`
- `app/Services/HomepageDataService.php`
- `app/Services/SeoValidationService.php`
- `app/Services/DuplicateContentService.php`
- `routes/api.php`
- `public/AI_INSTRUCTIONS.md`

## Operational Commands
Clear caches after theme/view/config changes:
```bash
cd /home/genznewz/htdocs/genznewz.com
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

Run the queue worker for RSS fetching:
```bash
php artisan queue:work --queue=default,rss-feeds
```

---

*Last updated: July 18, 2026*
