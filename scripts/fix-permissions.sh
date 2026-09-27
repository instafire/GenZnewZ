#!/bin/bash

# Fix Permissions Script for GenZNewz
# Run this if you get permission denied errors or 500 errors

echo "=========================================="
echo "GenZNewz Permission Fix Script"
echo "=========================================="

# Check if running as root
if [ "$(id -u)" != "0" ]; then
    echo "Warning: This script should be run as root for best results"
    echo "Trying with sudo..."
    sudo bash "$0" "$@"
    exit $?
fi

cd /home/genznewz/htdocs/genznewz.com

echo ""
echo "Step 1: Checking current permissions..."
ROOT_DIRS=$(find storage -type d -user root 2>/dev/null | wc -l)
if [ "$ROOT_DIRS" -gt 0 ]; then
    echo "Found $ROOT_DIRS directories owned by root - fixing..."
fi

echo ""
echo "Step 2: Fixing ownership..."
chown -R genznewz:genznewz storage/
chown -R genznewz:genznewz bootstrap/cache
chown -R genznewz:genznewz vendor/

echo ""
echo "Step 3: Setting directory permissions..."
find storage -type d -exec chmod 775 {} \;
find bootstrap/cache -type d -exec chmod 775 {} \;

echo ""
echo "Step 4: Setting file permissions..."
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;

echo ""
echo "Step 5: Setting special permissions..."
chmod -R 775 storage/logs
chmod -R 775 storage/framework/cache
chmod -R 775 storage/framework/sessions
chmod -R 775 storage/framework/views
chmod -R 775 storage/framework/testing

echo ""
echo "Step 6: Verifying fix..."
ROOT_DIRS_AFTER=$(find storage -type d -user root 2>/dev/null | wc -l)
if [ "$ROOT_DIRS_AFTER" -eq 0 ]; then
    echo "✓ All directories now owned by genznewz"
else
    echo "Warning: $ROOT_DIRS_AFTER directories still owned by root"
fi

echo ""
echo "Step 7: Testing website..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://genznewz.com/)
if [ "$HTTP_CODE" = "200" ]; then
    echo "✓ Website is responding with HTTP 200"
else
    echo "✗ Website returned HTTP $HTTP_CODE"
fi

echo ""
echo "=========================================="
echo "Permission fix complete!"
echo "=========================================="
echo ""
echo "IMPORTANT: Always use this command to run artisan:"
echo "  cd /home/genznewz/htdocs/genznewz.com"
echo "  sudo -u genznewz php artisan [command]"
echo ""
echo "Or use the safe artisan wrapper:"
echo "  ./artisan-safe [command]"
echo ""
