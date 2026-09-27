#!/bin/bash
# Queue Status Checker Script for GenZ NewZ
# Usage: ./queue-status.sh

cd /home/genznewz/htdocs/genznewz.com

echo "========================================"
echo "  GenZ NewZ - Queue Status"
echo "========================================"
echo ""

echo "📋 Queue Job Counts:"
echo "--------------------"
/usr/bin/php artisan tinker --execute="
\$queues = ['rss-feeds', 'default', 'media'];
foreach (\$queues as \$queue) {
    \$count = DB::table('jobs')->where('queue', \$queue)->count();
    echo str_pad(\$queue, 15) . ': ' . \$count . ' jobs' . PHP_EOL;
}
echo str_repeat('-', 25) . PHP_EOL;
echo 'TOTAL:          ' . DB::table('jobs')->count() . ' jobs' . PHP_EOL;
" 2>/dev/null

echo ""
echo "🔄 Recent Failed Jobs:"
echo "--------------------"
/usr/bin/php artisan queue:failed 2>/dev/null | head -5

echo ""
echo "⏰ Cron Status:"
echo "--------------------"
echo "Last cron run check: $(date)"
echo ""
echo "To manually process queues, run:"
echo "  php artisan queue:work --queue=rss-feeds --tries=2 --timeout=120 --stop-when-empty"
echo ""
