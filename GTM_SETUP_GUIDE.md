# Google Tag Manager Setup Guide for GenZ NewZ

## ✅ GTM Code Installed

Google Tag Manager has been installed on your website. The code is present on all pages.

---

## 🔧 Next Steps to Complete Setup

### Step 1: Get Your GTM Container ID

1. Go to https://tagmanager.google.com
2. Sign in with your Google account
3. Create a new account and container (or use existing)
4. Your Container ID looks like: **GTM-ABC123**

### Step 2: Update the Container ID on Your Website

**File to edit:** `/platform/themes/newspaper/partials/header.blade.php`

Replace `GTM-XXXXXX` with your actual Container ID in **TWO** places:

#### Location 1: In the <head> (around line 10)
```javascript
<!-- Replace GTM-XXXXXX with your actual GTM Container ID -->
})(window,document,'script','dataLayer','GTM-ABC123'); // <-- UPDATE THIS
```

#### Location 2: In the <body> noscript (around line 562)
```html
<!-- Replace GTM-XXXXXX with your actual GTM Container ID -->
<iframe src="https://www.googletagmanager.com/ns.html?id=GTM-ABC123" // <-- UPDATE THIS
```

### Step 3: Clear Cache

After updating the Container ID, clear the cache:
```bash
cd /home/genznewz/htdocs/genznewz.com
php artisan cache:clear
php artisan view:clear
```

---

## 📊 What You Can Track with GTM

### Pre-configured Data Layer Events

The following data is automatically pushed to the data layer:

```javascript
// Page Load Event
{
  'gtm.start': timestamp,
  event: 'gtm.js'
}
```

### Recommended Tags to Set Up in GTM

1. **Google Analytics 4 (GA4)**
   - Tag Type: Google Analytics: GA4 Configuration
   - Measurement ID: G-JJSRCWGNMZ
   - Trigger: All Pages

2. **Page View Events**
   - Automatically tracked via GA4 configuration

3. **Custom Events You Can Add:**
   - Article clicks
   - Category navigation
   - Search queries
   - Comment submissions
   - Login/Register events
   - Newsletter signups

---

## 🔗 Quick Commands

### Update Container ID via SSH:
```bash
# Connect to your server
ssh user@genznewz.com

# Navigate to theme directory
cd /home/genznewz/htdocs/genznewz.com/platform/themes/newspaper/partials

# Edit header.blade.php
nano header.blade.php

# Find and replace GTM-XXXXXX with your actual ID (e.g., GTM-ABC123)
# Press Ctrl+W to search, then replace

# Save and exit (Ctrl+X, then Y, then Enter)

# Clear cache
cd /home/genznewz/htdocs/genznewz.com
php artisan cache:clear
php artisan view:clear
```

---

## 🎯 Current Setup Status

| Component | Status | Location |
|-----------|--------|----------|
| GTM Container Script | ✅ Installed | `<head>` (highest priority) |
| GTM Noscript Fallback | ✅ Installed | `<body>` (immediately after opening) |
| Data Layer | ✅ Initialized | Before GTM script |
| GA4 Tag | ✅ Active | Kept for backup/direct tracking |
| Container ID | ⚠️ Pending | Replace GTM-XXXXXX with your ID |

---

## 📝 Important Notes

1. **Both GTM and GA4 tags are present** - This provides redundancy while you migrate to GTM
2. **GA4 tag (G-JJSRCWGNMZ)** can be removed once GA4 is configured through GTM
3. **All pages** automatically include the GTM code via the header template
4. **New pages** will automatically get GTM tracking

---

## 🔍 Verify Installation

After updating your Container ID, verify:

1. **Check page source:** View source on any page, search for "GTM-"
2. **GTM Preview Mode:** In Tag Manager, click "Preview" and enter your site URL
3. **Tag Assistant:** Install Chrome extension "Tag Assistant Legacy" to verify

---

## 📞 Support

- **GTM Help:** https://support.google.com/tagmanager
- **Your Container:** https://tagmanager.google.com
- **Current Code Location:** `platform/themes/newspaper/partials/header.blade.php`

---

*Setup Date: February 6, 2026*  
*GA4 Tracking ID: G-JJSRCWGNMZ*  
*GTM Status: Installed, awaiting Container ID*
