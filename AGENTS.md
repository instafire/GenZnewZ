# GenZ NewZ - AI Agent Documentation

## ⚠️ CRITICAL INFORMATION - READ FIRST

### Project Overview
- **Website**: https://genznewz.com
- **CMS**: Botble CMS (Laravel-based)
- **Active Theme**: `newspaper` (located at `platform/themes/newspaper/`)
- **DO NOT** switch to `lara-mag` theme - it is an old backup theme
- **PHP Version**: 8.2+
- **Database**: MySQL 8.0+

### Important Rules
1. **Always use the `newspaper` theme** - it's the current active theme with custom styling
2. **Never delete or overwrite** files without checking backups first
3. **Always clear caches** after making changes: `php artisan view:clear && php artisan cache:clear`
4. **Homepage is special** - Page ID 1 should NOT have a template set (keep it null)
5. **Logo is stored** at `storage/app/public/400px100px.png` and set via theme_option('logo')
6. **Run EVERY artisan command as `genznewz`, never as root**: `sudo -u genznewz php artisan ...`
   — root-owned cache files make PHP-FPM 500 the whole site. Also **never run `config:cache`**
   (breaks the test suite). Full runbook: `DEPLOY_CHECKLIST.md`
   (also covers: `.htaccess` is dead — headers go in `SetSecurityHeaders.php`; theme assets deploy
   to BOTH `platform/.../public` and `public/themes/...`; CSS cascade order is load-bearing)

---

## 📁 Directory Structure

### Theme Files (CRITICAL - Only edit these)
```
platform/themes/newspaper/
├── views/
│   ├── index.blade.php              # HOMEPAGE - Main landing page
│   ├── post.blade.php               # Single article page
│   ├── category.blade.php           # Category listing page
│   ├── author.blade.php             # Author profile page
│   ├── page.blade.php               # Static pages
│   ├── search.blade.php             # Search results
│   ├── tag.blade.php                # Tag listing
│   ├── live-world-events.blade.php  # Live events page
│   ├── post-video.blade.php         # Video post template
│   ├── category-videos.blade.php    # Video category template
│   ├── partials/                    # View partials
│   │   ├── header.blade.php         # Site header (contains logo)
│   │   ├── footer.blade.php         # Site footer
│   │   ├── breadcrumbs.blade.php    # Breadcrumb navigation
│   │   ├── comments.blade.php       # Comments section
│   │   ├── image.blade.php          # Optimized image component
│   │   ├── schema.blade.php         # SEO schema markup
│   │   └── short-codes/             # Shortcode templates
│   ├── templates/                   # Custom page templates
│   │   ├── ai-reporter-landing.blade.php
│   │   ├── ai-reporter-dashboard.blade.php
│   │   ├── ai-reporter-register.blade.php
│   │   ├── todays-paper.blade.php
│   │   ├── login.blade.php
│   │   └── register.blade.php
│   └── widgets/                     # Sidebar widgets
│       ├── crypto-market.blade.php  # Crypto prices widget
│       └── stock-market.blade.php   # Stock prices widget
├── layouts/
│   ├── default.blade.php            # Default page layout
│   └── homepage.blade.php           # Homepage layout
├── partials/                        # Theme partials (duplicated)
├── src/
│   └── Providers/
│       └── ThemeServiceProvider.php # Theme service provider
├── functions/
│   └── functions.php                # Theme functions
├── public/                          # Public assets
│   ├── css/
│   │   └── lara-mag.css            # Main theme stylesheet
│   └── js/
│       └── lara-mag.js             # Main theme javascript
└── theme.json                       # Theme configuration
```

### Custom Application Files
```
app/
├── Services/                        # Custom business logic
│   ├── CryptoMarketService.php      # Fetches crypto prices from CoinMarketCap
│   ├── StockMarketService.php       # Fetches stock data from Massive API
│   ├── PexelsImageService.php       # Fetches images from Pexels API
│   ├── SeoValidationService.php     # SEO scoring for AI content
│   ├── RssNewsTickerService.php     # RSS feed aggregation
│   ├── InternalLinkingService.php   # Auto-generates internal links
│   └── ... (other services)
├── Jobs/                            # Queue jobs
│   ├── FetchRssFeedsJob.php         # Fetches RSS feeds
│   └── ProcessPostImageJob.php      # Assigns Pexels images to posts
├── Models/
│   └── AIReporter.php               # AI reporter model
└── Http/Controllers/
    └── UploadController.php         # File upload handling
```

### Configuration Files
```
config/
└── services.php                     # API keys configuration
    # - massive.api_key (for stocks)
    # - coinmarketcap.api_key (for crypto)
    # - pexels.api_key (for images)
    # - pixeldrain.api_key (for file hosting)
```

### Public Assets
```
public/
├── themes/newspaper/               # Symlink to theme public files
├── storage/                        # Linked to storage/app/public
│   └── ... uploaded images
└── ... other public files
```

---

## 🎨 Theme System

### How Theme Rendering Works
1. **Homepage**: Uses `views/index.blade.php` (NOT a page template)
2. **Posts**: Uses `views/post.blade.php` or `views/post-video.blade.php`
3. **Categories**: Uses `views/category.blade.php`
4. **Pages**: Uses `views/page.blade.php` or custom template from `views/templates/`

### Critical Theme Files

#### Header (`views/partials/header.blade.php`)
- Contains site logo (uses `theme_option('logo')`)
- Main navigation
- Mobile menu
- Dark mode toggle
- User authentication links

#### Footer (`views/partials/footer.blade.php`)
- MUST end with `{!! Theme::footer() !!}` followed by `</body></html>`
- Contains category links, social links, copyright

#### Image Component (`views/partials/image.blade.php`)
- Used for all post images
- Supports WebP format
- Implements lazy loading
- Usage: `@include('theme::partials.image', ['image' => $post->image, 'alt' => $post->name])`

---

## 🖼️ Image System

### Pexels Integration
- **Service**: `app/Services/PexelsImageService.php`
- **API Key**: Stored in `config/services.php` or `.env`
- **Queue Job**: `app/Jobs/ProcessPostImageJob.php`
- **Storage**: Images saved to `storage/app/public/pexels/`
- **Usage**: Automatically assigns images to posts without images

### Image Sizes
- `thumb`: 150x100
- `small`: 300x200
- `medium`: 600x400
- `large`: 900x600
- `xlarge`: 1200x800

### Default Image
- Location: Set via `RvMedia::getDefaultImage()`
- Used when post has no image

---

## 📊 Market Widgets

### Crypto Widget (`views/widgets/crypto-market.blade.php`)
- **Service**: `CryptoMarketService`
- **API**: CoinMarketCap
- **Cache**: 20 minutes
- **Shows**: Top 5 cryptocurrencies with prices and 24h change

### Stock Widget (`views/widgets/stock-market.blade.php`)
- **Service**: `StockMarketService`
- **API**: Massive API
- **Cache**: 20 minutes
- **Shows**: Major indices (S&P 500, NASDAQ, Dow) and popular stocks

---

## 🤖 AI Reporter System

### Authentication
- **Guard**: `ai_reporter`
- **Model**: `app/Models/AIReporter.php`
- **Routes**: Defined in `ThemeServiceProvider.php`

### API Endpoints (`routes/api.php`)
```
GET  /api/v1/automation/status     - Check API status
GET  /api/v1/automation/categories - List categories
GET  /api/v1/automation/authors    - List AI authors
POST /api/v1/automation/posts/create - Create post
```

### Post Creation Flow
1. AI agent sends POST to `/api/v1/automation/posts/create`
2. `AutomationController@createPost` validates and creates post
3. `ProcessPostImageJob` is dispatched to fetch Pexels image
4. Post is published with image

---

## 🗄️ Database Important Tables

### Posts Table
- `format_type`: 'default', 'video', or 'text-only'
- `is_featured`: Boolean for featured posts
- `image`: Path to featured image

### Meta Boxes Table
- Stores custom fields for posts
- `pexels_photo_id`: Prevents duplicate images
- `image_search_query`: Custom search term for images

### Categories Table
- `parent_id`: For hierarchical categories
- `order`: Display order

### Pages Table
- **IMPORTANT**: `template` column controls layout
- Homepage (ID 1) should have `template = NULL`
- Other pages can use templates from `views/templates/`

---

## ⚙️ Configuration

### Theme Options (stored in database)
```php
theme_option('logo')              // Logo image path
theme_option('site_title')        // Site title
theme_option('copyright')         // Footer copyright text
theme_option('primary_color')     // Primary theme color
```

### Environment Variables
```env
AI_AUTOMATION_TOKEN=xxx           # API authentication
AI_WEBHOOK_SECRET=xxx             # Webhook verification
MASSIVE_API_KEY=xxx               # Stock market API
COINMARKETCAP_API_KEY=xxx         # Crypto API
PEXELS_API_KEY=xxx                # Image API
```

---

## 🔧 Common Operations

### Clear All Caches
```bash
cd /home/genznewz/htdocs/genznewz.com
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### Check Theme Status
```bash
php artisan tinker --execute="echo Botble\Theme\Facades\Theme::getThemeName();"
```

### Fix Missing Images
```bash
php artisan queue:work --queue=default
```

### Reset Homepage Template
```bash
php artisan tinker --execute="
\$page = Botble\Page\Models\Page::find(1);
\$page->template = null;
\$page->save();
"
```

---

## 🚨 Common Issues & Solutions

### Issue: Homepage shows only "Welcome to GenZNewz"
**Cause**: Homepage page has template set
**Fix**: 
```bash
php artisan tinker --execute="
\$page = Botble\Page\Models\Page::find(1);
\$page->template = null;
\$page->save();
"
```

### Issue: Footer broken / no closing tags
**Cause**: Footer missing `</body></html>`
**Fix**: Ensure `footer.blade.php` ends with:
```blade
{!! Theme::footer() !!}

</body>
</html>
```

### Issue: Images not displaying
**Check**:
1. Images exist in `storage/app/public/`
2. Symbolic link exists: `public/storage` → `storage/app/public`
3. Image paths are correct in database

### Issue: Logo not showing
**Check**:
1. Logo file exists at `storage/app/public/400px100px.png`
2. `theme_option('logo')` returns correct filename
3. Header uses `RvMedia::getImageUrl(theme_option('logo'))`

### Issue: Market widgets not loading
**Check**:
1. API keys are set in `.env` or `config/services.php`
2. Cache is working (widgets cache for 20 minutes)
3. Check logs: `storage/logs/laravel-*.log`

---

## 📱 Key URLs

### Public Pages
- `/` - Homepage
- `/news` - News listing
- `/live-world-events` - Live events
- `/ai-news-reporter` - AI reporter landing
- `/todays-paper` - Today's paper

### API Endpoints
- `/api/v1/automation/*` - AI automation API
- `/api/rss-ticker` - RSS ticker data

---

## 📝 File Modification Guidelines

### DO:
- Edit files in `platform/themes/newspaper/views/`
- Use `php artisan view:clear` after editing views
- Test changes on homepage, post, and category pages
- Keep backup of original files before major changes

### DON'T:
- Switch to `lara-mag` theme
- Delete files without checking dependencies
- Modify core Botble files in `platform/core/`
- Forget to clear caches after changes
- Remove `{!! Theme::footer() !!}` from footer

---

## 🔗 External Services

### Image APIs
- **Pexels**: Primary image source
- **PixelDrain**: File hosting for uploads

### Market Data
- **CoinMarketCap**: Crypto prices
- **Massive API**: Stock prices

### Analytics
- **Google Analytics**: G-JJSRCWGNMZ
- **Google Tag Manager**: GTM-NCB8Q9PR
- **Google AdSense**: ca-pub-8161801058322526

---

## 🧪 Testing Checklist

After making changes, verify:
- [ ] Homepage displays correctly with Featured Stories
- [ ] Logo appears in header
- [ ] Images load on posts
- [ ] Sidebars show on homepage (Editor's Picks, Crypto/Stock widgets)
- [ ] Footer shows correctly with all links
- [ ] Post pages work
- [ ] Category pages work
- [ ] No 404 errors for CSS/JS files
- [ ] Mobile responsive design works

---

## 📞 Emergency Contacts / Resources

- **Documentation**: https://docs.botble.com/cms/
- **Laravel Docs**: https://laravel.com/docs/11.x
- **Project Root**: `/home/genznewz/htdocs/genznewz.com`
- **Storage**: `/home/genznewz/htdocs/genznewz.com/storage`
- **Logs**: `/home/genznewz/htdocs/genznewz.com/storage/logs/`

---

*Last Updated: February 19, 2026*
*Theme: newspaper (Active)*
*CMS: Botble CMS v7.5.7*
