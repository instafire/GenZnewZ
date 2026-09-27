# GenZ NewZ + TV Subdomain - Master Context for AI Agents

Last updated: 2026-02-20

This file is the single source of truth for AI agents working on:
- Main site: `https://genznewz.com`
- TV app subdomain: `https://tv.genznewz.com`

## 1) System Map

- Main site codebase path: `/home/genznewz/htdocs/genznewz.com`
- TV app codebase path: `/home/genznewz/htdocs/tv.genznewz.com`
- They are separate applications and must be treated independently.
- Do not apply TV app changes to main site files, and vice versa.

## 2) Main Site (genznewz.com)

### Stack
- CMS: Botble CMS (Laravel-based)
- PHP: 8.2+
- DB: MySQL 8+
- Active theme: `newspaper`
- Active theme path: `platform/themes/newspaper/`

### Critical Rules
- Always keep theme as `newspaper` (do not switch to `lara-mag`).
- Homepage is special: Page ID `1` must have `template = NULL`.
- After any theme/view/config changes, run:
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```
- Do not remove `{!! Theme::footer() !!}` in footer templates.

### Core Theme Files
- Homepage: `platform/themes/newspaper/views/index.blade.php`
- Header: `platform/themes/newspaper/partials/header.blade.php`
- Footer: `platform/themes/newspaper/views/partials/footer.blade.php`
- Post page: `platform/themes/newspaper/views/post.blade.php`
- Category page: `platform/themes/newspaper/views/category.blade.php`
- Generic page: `platform/themes/newspaper/views/page.blade.php`
- AI landing template: `platform/themes/newspaper/views/templates/ai-reporter-landing.blade.php`

### AI Reporter System (main site)
- Model: `app/Models/AIReporter.php`
- Services: `app/Services/` (SEO, image, feeds, linking, etc.)
- Primary AI docs:
  - `/AI_INSTRUCTIONS.md`
- Current hard gates:
  - SEO score `>= 80` (B+)
  - Content `>= 650` words
  - 2+ H2 headings
  - 5+ paragraphs and 12+ sentences
  - 1+ HTTPS external source
  - 1+ explicit attribution phrase in body
  - Duplicate detection includes lead-paragraph similarity
- API routes:
  - `GET /api/v1/automation/status`
  - `GET /api/v1/automation/categories`
  - `GET /api/v1/automation/authors`
  - `POST /api/v1/automation/posts/create`
- Image workflow:
  - `app/Services/PexelsImageService.php`
  - `app/Jobs/ProcessPostImageJob.php`

### SEO + Analytics Requirements
- Measurement ID: `G-JJSRCWGNMZ`
- GTM ID: `GTM-NCB8Q9PR`
- IDs are set in `.env`:
  - `GOOGLE_ANALYTICS_MEASUREMENT_ID`
  - `GOOGLE_TAG_MANAGER_ID`
- Header loads GA + GTM globally through:
  - `platform/themes/newspaper/partials/header.blade.php`
- Sitemap endpoints to keep available:
  - `/sitemap.xml`
  - `/sitemap-posts.xml`
  - `/sitemap-pages.xml`
  - `/sitemap-ai.xml`
  - `/pages.xml` (legacy/extra)
- Ensure `robots.txt` includes all sitemap lines above.

### Main-Site Operational Commands
```bash
cd /home/genznewz/htdocs/genznewz.com
php artisan cache:clear && php artisan view:clear && php artisan config:clear
php artisan queue:work --queue=default
```

## 3) TV App (tv.genznewz.com)

### Stack
- Full-stack TypeScript app
- Frontend: React + Vite
- Backend: Express + Socket.io
- Real-time score/events from Twitch + Kick
- Project root: `/home/genznewz/htdocs/tv.genznewz.com`

### TV App Structure
- Frontend source: `client/src/`
- Backend source: `server/src/`
- Server entry: `server/src/index.ts`
- Battle routes: `server/src/routes/battle.ts`
- Room routes: `server/src/routes/nodes.ts`
- Battle logic: `server/src/services/battle-manager.ts`
- Scoring storage: `server/src/services/redis-scoring.ts`
- Kick integration:
  - `server/src/services/kick-webhooks.ts`
  - `server/src/services/kick-chat.ts`
  - `server/src/services/kick-api.ts`

### TV App Behavior Requirements
- Score must count chat events after battle starts (not viewer count snapshots).
- Unique room links must be joinable by URL (`/room/{shareCode}`).
- Only room owner can close room manually.
- Auto-close room when viewers are `0` for 2 minutes.
- At battle end:
  - Show winner trophy for 10 seconds.
  - Then prompt users to play again or leave.
  - If players stay, keep them in room and restart timer (no forced return to start page).
- Persist winners and battle records for leaderboard/top 10.

### TV App Run Commands
```bash
cd /home/genznewz/htdocs/tv.genznewz.com
npm run dev
# or separately
npm run dev:server
npm run dev:client
```

## 4) Cross-System Change Policy

- Never mix deployment/config between both apps.
- Validate domain-specific behavior after changes:
  - Main site: homepage, posts, categories, AI reporter pages.
  - TV app: create battle, join link, live scoring, end-state flow, leaderboard.
- For SEO/analytics changes, verify rendered HTML on multiple URLs, not only homepage.
- Do not hardcode secrets in code. Use env variables and document variable names only.

## 5) Pre-Release Checklist (Both Apps)

- Main site:
  - Homepage loads featured + media sections without duplicate posts.
  - Header/footer render correctly.
  - GA/GTM IDs appear in rendered HTML.
  - Sitemaps are reachable (HTTP 200).
- TV app:
  - Room create/join via link works.
  - Score bar updates in sync with score numbers for both sides.
  - Winner display/trophy and replay flow works.
  - Leaderboard shows expected win + chat stats.

## 6) Fast Recovery Notes

- If homepage breaks and shows fallback content, check Page ID 1 template is NULL.
- If analytics appear missing, confirm:
  - IDs in `.env`
  - header partial loaded via theme layout
  - Laravel caches cleared
- If TV webhooks fail, verify webhook URL and signature handling in `server/src/index.ts`.
