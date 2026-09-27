#!/bin/bash
# Manage the Laravel queue worker via systemd.

cd /home/genznewz/htdocs/genznewz.com

SERVICE_NAME="genznewz-queue-worker.service"

if ! command -v systemctl >/dev/null 2>&1; then
    echo "systemctl is not available on this host."
    exit 1
fi

systemctl daemon-reload
systemctl restart "$SERVICE_NAME"
systemctl --no-pager --full status "$SERVICE_NAME"
