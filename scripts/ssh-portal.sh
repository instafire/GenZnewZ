#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/home/genznewz/htdocs/genznewz.com"
cd "$APP_DIR"

# Ignore arbitrary remote commands and always open the portal.
exec php artisan portal:ssh --limit="${SSH_PORTAL_LIMIT:-12}"
