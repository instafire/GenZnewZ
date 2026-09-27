/**
 * web-vitals.js — Core Web Vitals → GA4 dataLayer capture.
 *
 * Measures LCP, CLS and INP with PerformanceObserver and pushes them to
 * window.dataLayer as `web_vitals` events. Reaches GA4 automatically when a
 * GA4 tag is present (gtag.js) or managed by Cloudflare Zaraz (which forwards
 * dataLayer events to configured GA4 destinations). If no GA4 tag exists the
 * pushes are harmless no-ops — the script stays dormant until then.
 *
 * Registered site-wide from config.php (footer container, ~2 KB, no deps).
 * View the data in GA4: Reports → Engagement → Events → web_vitals, or build
 * an Explorations report on the `metric_name` / `metric_value` custom dims.
 * Based on the standard patterns from web.dev/articles/vitals-ga4.
 */
(function () {
    'use strict';

    if (!('PerformanceObserver' in window)) {
        return;
    }

    window.dataLayer = window.dataLayer || [];

    // Canonical gtag command queueing — identical to the official snippet's
    // `function gtag(){dataLayer.push(arguments);}`. Plain gtag.js (no GTM)
    // only transmits Arguments-format commands; {event:...} objects and arrays
    // are GTM conventions it ignores. The object push below is kept for
    // Cloudflare Zaraz / GTM containers, which read that shape instead.
    function gv() {
        window.dataLayer.push(arguments);
    }

    var metric = { name: '', value: 0, id: '' };
    var reported = {};

    function metricId() {
        return 'v3-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
    }

    // One id per page visit stitches the LCP/CLS/INP pushes together in GA4.
    var pageMetricId = metricId();

    function push(name, value, rating) {
        // Only report once per metric, and only if GA4 (or Zaraz) is present.
        if (reported[name] || typeof value !== 'number' || value < 0) {
            return;
        }
        reported[name] = true;
        var rounded = Math.round(name === 'CLS' ? value * 1000 : value);
        var params = {
            web_vitals_metric_name: name,
            web_vitals_metric_value: rounded,
            web_vitals_metric_rating: rating,
            web_vitals_metric_id: pageMetricId,
        };
        // Format 1: gtag command (Arguments) — transmitted by plain gtag.js.
        gv('event', 'web_vitals', params);
        // Format 2: plain object — what GTM containers and Zaraz read as a
        // custom event. Ignored by plain gtag.js, harmless alongside it.
        window.dataLayer.push(Object.assign({ event: 'web_vitals' }, params));
    }

    function rating(name, value) {
        var thresholds = {
            LCP: [2500, 4000],
            CLS: [0.1, 0.25],
            INP: [200, 500],
        };
        var t = thresholds[name];
        if (!t) {
            return 'unknown';
        }
        return value <= t[0] ? 'good' : value <= t[1] ? 'needs-improvement' : 'poor';
    }

    // --- LCP: largest contentful paint, finalized on page hide -------------
    var lcpValue = 0;
    try {
        new PerformanceObserver(function (list) {
            var entries = list.getEntries();
            var last = entries[entries.length - 1];
            if (last) {
                lcpValue = last.startTime;
            }
        }).observe({ type: 'largest-contentful-paint', buffered: true });
    } catch (e) { /* unsupported */ }

    // --- CLS: layout shifts, session-window capped --------------------------
    var clsValue = 0;
    try {
        new PerformanceObserver(function (list) {
            for (var i = 0; i < list.getEntries().length; i++) {
                var entry = list.getEntries()[i];
                if (!entry.hadRecentInput) {
                    clsValue += entry.value;
                }
            }
        }).observe({ type: 'layout-shift', buffered: true });
    } catch (e) { /* unsupported */ }

    // --- INP: worst interaction latency -------------------------------------
    var inpValue = 0;
    try {
        new PerformanceObserver(function (list) {
            for (var i = 0; i < list.getEntries().length; i++) {
                var entry = list.getEntries()[i];
                var duration = entry.duration || 0;
                if (duration > inpValue) {
                    inpValue = duration;
                }
            }
        }).observe({ type: 'event', durationThreshold: 16, buffered: true });
    } catch (e) { /* unsupported */ }

    // --- Flush on page hide (sendBeacon when GA4 provides a transport) ------
    function flush() {
        if (lcpValue > 0) {
            metric.name = 'LCP';
            metric.value = lcpValue;
            push('LCP', lcpValue, rating('LCP', lcpValue));
        }
        push('CLS', clsValue, rating('CLS', clsValue));
        if (inpValue > 0) {
            metric.name = 'INP';
            metric.value = inpValue;
            push('INP', inpValue, rating('INP', inpValue));
        }
    }

    window.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            flush();
        }
    });
    window.addEventListener('pagehide', flush);
})();
