# GenZ NewZ — Deploy & Operations Checklist

Last updated: 2026-09-19

## ⚠️ Rule #1: Run everything as `genznewz`, never as root

PHP-FPM runs as **`genznewz`**. If any artisan command, script, or file edit runs as
**root**, Laravel writes root-owned files into `storage/framework/cache` /
`storage/framework/views`. PHP-FPM then cannot overwrite them and every page 500s with:

```
file_put_contents(storage/framework/cache/data/...): Failed to open stream: Permission denied
```

**Always:**

```bash
cd /home/genznewz/htdocs/genznewz.com
sudo -u genznewz php artisan ...        # ✅ every artisan command
sudo -u genznewz vendor/bin/phpunit     # ✅ tests too
```

**Never:** plain `php artisan ...` as root, or leaving root-owned files behind.

### If the site is 500-ing (permission denied)

```bash
cd /home/genznewz/htdocs/genznewz.com
find . -user root | wc -l               # >0 means root-owned files are the cause
find . -user root -exec chown genznewz:genznewz {} +
sudo -u genznewz php artisan view:clear && sudo -u genznewz php artisan cache:clear
curl -s -o /dev/null -w "%{http_code}\n" -H "Host: genznewz.com" http://127.0.0.1:8080/
```

### Cron jobs

All app cron jobs (Laravel scheduler, queue workers, cleanups) live in **genznewz's
crontab** — NOT root's:

```bash
crontab -u genznewz -l     # app jobs live here
crontab -l                 # root's crontab must contain no genznewz.com jobs
```

Do not add app jobs back to root's crontab — that is what created the root-owned
cache files. A root-crontab backup from the 2026-09-19 migration is at
`/home/genznewz/backups/root-crontab-backup-20260919.txt`.

## Serving stack (what actually handles a request)

```
Cloudflare (edge cache) → nginx :443 → nginx :8080 → PHP-FPM :19002
```

Consequences:

- **`.htaccess` files are dead** — Apache is not in the stack. Security headers
  (CSP, HSTS, etc.) are emitted by `app/Http/Middleware/SetSecurityHeaders.php`.
  Update CSP there, never in `.htaccess`.
- **Static files** (`/themes/...`, `/vendor/...`, fonts) are served by nginx
  directly from `public/` with `expires max` — they bypass Laravel entirely.
- **Cloudflare caches aggressively.** To ship changed static assets, change the URL
  (query-string version). There are no Cloudflare API credentials on this box.

## Smoke-test the backend BEFORE checking anything else (catches runtime errors
# that php -l cannot — e.g. calling methods on the wrong object):
curl -s -o /dev/null -w "%{http_code}\n" -H "Host: genznewz.com" http://127.0.0.1:8080/
# Must print 200. If it prints 500, fix immediately — the live site is down.

# Deploy
ing theme changes

The theme exists in two places; **both must be updated**:

| Source (edit this)                      | Served copy (nginx reads this)        |
| --------------------------------------- | ------------------------------------- |
| `platform/themes/newspaper/public/`     | `public/themes/newspaper/public/`     |

```bash
cd /home/genznewz/htdocs/genznewz.com
cp platform/themes/newspaper/public/css/*.css public/themes/newspaper/public/css/
cp platform/themes/newspaper/public/js/*.js   public/themes/newspaper/public/js/
chown -R genznewz:genznewz platform public
sudo -u genznewz php artisan view:cache
find . -user root -exec chown genznewz:genznewz {} +   # sweep, must stay 0
```

> **Never run `php artisan config:cache`** on this app. A cached config overrides
> `phpunit.xml`, so the test suite boots with production settings (this caused 32
> test failures on 2026-09-19). The speed benefit is negligible on this stack —
> `view:cache` is the win; `bootstrap/cache/config.php` must NOT exist.
> If it does: `sudo -u genznewz php artisan config:clear`.

Asset URLs are versioned by the **source-tree** mtime (`platform/...`), so after a
deploy the `?v=` changes automatically and caches bust.

### CSS cascade contract (do not break)

Stylesheet order is load-bearing — registered in
`platform/themes/newspaper/config.php` with dependency chains:

```
fonts.css → theme-header.css → newspaper.css → performance.css → theme-components.css → theme-dark.css
```

Post pages additionally load (registered from the views themselves):

```
… → theme-dark.css → comments.css (deps: theme-dark) → post.css (deps: theme-dark, comments)
```

`comments.css` and `post.css` are extracted from inline `<style>` blocks in
`partials/comments.blade.php` and `views/post.blade.php`. They share selectors
with `theme-dark.css` dark rules, so they MUST load after it — the dependency
chain enforces this. Do not register them without dependencies.

- `theme-header.css` — base/component styles extracted from the old inline header
  CSS. Must load **before** `newspaper.css`/`performance.css`.
- `theme-components.css` — origin badge, footer, news-chat widget, `.sr-only`
  styles extracted from inline `<style>` blocks in those partials. Must load
  **after** `performance.css` and **before** `theme-dark.css` (no selector
  overlap with `theme-dark.css`, verified 2026-09).
- `theme-dark.css` — dark-mode styles. Must load **after** `performance.css`
  (19 dark selectors also exist in `performance.css`; order decides the winner).
- The only inline `<style>` in `header.blade.php` is the dynamic `:root` block
  (colors come from the DB via `theme_option()`). Everything static lives in the
  two extracted sheets. `footer.blade.php`, `news-chat-widget.blade.php`,
  `author-badge.blade.php`, and the cookie-consent plugin view have no inline
  CSS anymore — their styles live in `theme-components.css` and
  `vendor/core/plugins/cookie-consent/css/cookie-notice.css` respectively.
- **Fonts/typography**: all fonts are self-hosted via `fonts.css`. The theme's
  service provider registers a NON-Google primary `TypographyItem` so Botble's
  Typography emitter does NOT inline ~35 localized Inter `@font-face` rules
  (~25 KB) per page. If you change the font set, re-bake `fonts.css`
  (see below) — do not re-enable the Google emitter.
- **Gallery assets are conditional**: the theme's `functions/functions.php`
  must NOT call `Gallery::registerAssets()` globally. The plugin registers its
  masonry/lightgallery/imagesloaded JS (~46 KB) only where galleries render
  (galleries view, single-gallery service, all-galleries shortcode). If a page
  using galleries breaks its lightbox, add `Gallery::registerAssets()` to that
  view — not to the global booted closure.
- **Inline scripts**: the news-chat widget and cookie-consent handlers live in
  `js/news-chat.js` and `vendor/core/plugins/cookie-consent/js/cookie-consent.js`
  (deferred), not inline. Don't reintroduce inline handlers — they run on the
  main thread during load and were the largest mobile-TBT long tasks.
- **GA4**: `google_tag_manager_id` = `G-JJSRCWGNMZ` (DB setting) renders a
  **deferred loader** (boots gtag on first interaction or 4s idle — dataLayer
  commands queue and replay, so nothing is lost). `web-vitals.js` pushes
  LCP/CLS/INP as `web_vitals` events.
  - dataLayer pushes MUST be Arguments-format commands
    (`function gv(){dataLayer.push(arguments);} gv('event', …)`) — plain
    gtag.js (no GTM) ignores arrays and `{event:…}` objects (verified
    empirically; the object push is kept only for GTM/Zaraz consumers).
  - Custom definitions (create in GA4 UI once — data before registration is
    not retro-processed): dimensions `web_vitals_metric_name`,
    `web_vitals_metric_rating`, `web_vitals_metric_id` (Event-scoped) +
    metric `web_vitals_metric_value` (Standard). Report: Explore → Free form.
  - Verified end-to-end Sept 19 2026: `POST /g/collect` with
    `en=web_vitals`, `ep.web_vitals_metric_name=INP`,
    `epn.web_vitals_metric_value=…` (headless Chrome capture).
- **Theme JS has FOUR copies** that must stay identical: the registered source
  `platform/themes/newspaper/public/js/`, the canonical full copy
  `platform/themes/newspaper/assets/js/` (what tests assert), and BOTH deployed
  locations `public/themes/newspaper/js/` + `public/themes/newspaper/public/js/`.
  `ThemeFrontendDeliveryTest::test_deployed_javascript_matches_its_source`
  fails on drift — a real incident: the deployed copy was once a stale compact
  version without dark-mode handling. After editing any theme JS, sync all four
  and run the suite.
- **CSP**: new third-party endpoints go in
  `app/Http/Middleware/SetSecurityHeaders.php`. Currently allowed: Clarity
  (`www.clarity.ms`, `scripts.clarity.ms`, `*.clarity.ms` connect), Cloudflare
  Insights (`static.cloudflareinsights.com`), GA4 + DoubleClick beacons
  (`stats.g.doubleclick.net`, `googleads.g.doubleclick.net`,
  `www.google.com/ads/ga-audiences` connect). Verify new tags with a headless
  console-error capture, not just curl — CSP blocks are silent in HTML.
- **Rocket Loader**: A/B disable plan lives in `ROCKET_LOADER_AB_PLAN.md`.
  All frontend scripts are standard external/deferred tags now — nothing in
  the codebase requires Rocket Loader to be on or off.

### Updating self-hosted fonts

Font files: `platform/themes/newspaper/public/fonts/*.woff2` (latin subsets).

1. Replace/add the `.woff2` file(s) in the **source** dir.
2. Re-bake the version query in `css/fonts.css` — each `url()` carries the font
   file's mtime, and it must match the mtime used by the `<link rel="preload">`
   tags in `header.blade.php` (which read the same source files) or the browser
   downloads each font twice:

   ```bash
   cd platform/themes/newspaper/public/css
   for f in ../fonts/*.woff2; do
     n=$(basename "$f"); m=$(stat -c %Y "$f")
     sed -i "s|$n?v=[0-9]*|$n?v=$m|g" fonts.css
   done
   ```

3. Copy `fonts.css` + fonts to the served copy (see deploy snippet above).
4. Verify dedup (preload URL must equal the CSS URL):

   ```bash
   curl -s -H "Host: genznewz.com" http://127.0.0.1:8080/ | grep -o 'fonts/inter-400.woff2?v=[0-9]*'
   grep -o 'fonts/inter-400.woff2?v=[0-9]*' platform/themes/newspaper/public/css/fonts.css
   ```

### Cache-busting stale Cloudflare assets (no API needed)

Append/advance a query string on the asset URL (e.g. `?v=2` → `?v=3` in
`fonts.css`). CF's cache key includes the query string, so the next request is a
MISS and pulls the fresh bytes from origin.

## Pre-deploy checklist

- [ ] All commands run as `genznewz` (no new root-owned files)
- [ ] `php -l` on every modified PHP/Blade file
- [ ] Theme assets copied to **both** `platform/.../public` and `public/themes/...`
- [ ] `sudo -u genznewz vendor/bin/phpunit` — full suite green (~153 tests, ~30s)
- [ ] `view:cache` rebuilt (as `genznewz`) — and `bootstrap/cache/config.php` does NOT exist (no `config:cache`)
- [ ] `find . -user root | wc -l` → `0`
- [ ] Smoke test through the real stack (cache-busted):
      `curl -s -o /dev/null -w "%{http_code}\n" "https://genznewz.com/?cb=$(date +%s)"`
- [ ] Headers present on live HTML: `content-security-policy`, `strict-transport-security`
- [ ] No `fonts.googleapis.com` / `fonts.gstatic.com` refs in rendered HTML (fonts are self-hosted)

## Quick reference

| Thing                          | Where                                                        |
| ------------------------------ | ------------------------------------------------------------ |
| Security headers (CSP/HSTS)    | `app/Http/Middleware/SetSecurityHeaders.php`                 |
| Theme asset registration       | `platform/themes/newspaper/config.php`                       |
| Self-hosted fonts + font CSS   | `platform/themes/newspaper/public/fonts/`, `public/css/fonts.css` |
| Extracted header CSS           | `css/theme-header.css` (base), `css/theme-dark.css` (dark)   |
| Extracted component CSS        | `css/theme-components.css` (badge, footer, chat, sr-only)    |
| Cookie-consent notice CSS      | `vendor/core/plugins/cookie-consent/css/cookie-notice.css`   |
| Extracted article CSS          | `css/post.css` (registered from `views/post.blade.php`)      |
| Extracted comments CSS         | `css/comments.css` (registered from `partials/comments.blade.php`) || App cron jobs                   | `crontab -u genznewz -l`                                     |
| Rocket Loader A/B plan          | `ROCKET_LOADER_AB_PLAN.md`                                   |
| Headless interaction captures   | `/tmp/jqtest/` (capture.js, ga4-e2e.js — recreate as needed) |
| Theme options (DB `settings`)  | `theme-newspaper-*` keys (e.g. `primary_font`, `custom_header_html`) |
| Pre-migration backups          | `/home/genznewz/backups/`                                    |
