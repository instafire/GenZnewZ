# Rocket Loader A/B Plan — Disabling in Cloudflare

**Status:** Draft, ready to execute. Owner: site admin (Cloudflare dashboard access required).
**Why:** Lab evidence says Rocket Loader now costs more than it saves on this site.

## Evidence (why disable it)

Measured across Lighthouse mobile runs (Sept 2026) and headless traces:

- Rocket Loader injects itself and adds **its own long task (~343 ms)** on mobile throttling.
- In one outlier run it contributed a **973 ms task** re-serializing script execution after DOMContentLoaded.
- It **re-orders execution**: every script gets rewritten to `type="…-javascript"` and re-dispatched post-DOM — this is what made the old inline handlers, `article-tools`, and gallery JS (all now deferred or gated at the source) hard to reason about.
- Its benefit (deferring blocking scripts) is now **redundant**: cookie-consent.js, news-chat.js, comment.js are external+deferred; article-tools is post-only; gallery JS is gated; jQuery is removed; GA4 is deferred (boots on first interaction / 4 s idle).
- Field metrics no longer depend on lab TBT: GA4 reports **INP**, and the deferred GA4 path keeps gtag off the load-critical path.

## Method: before/after with weekday-matched comparison

Cloudflare's Rocket Loader toggle is zone-wide (no native 50/50 split on one zone), so a true randomized experiment isn't available without splitting traffic at the app layer. Use a **7-day-before / 7-day-after** design with weekday matching to absorb day-of-week traffic patterns.

### Metrics (field, the scoreboard)

| Metric | Source | Cadence |
|---|---|---|
| p75 INP, LCP, CLS | GA4 `web_vitals` events (once custom dims/metrics registered — see DEPLOY_CHECKLIST) | daily export |
| p75 field data | CrUX via PageSpeed Insights API (free API key) — `https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://genznewz.com/&strategy=mobile` | before + after snapshots |
| Engagement guardrails | GA4: bounce rate, engaged sessions | daily |
| UX breakage guardrails | Clarity: rage clicks, dead clicks, JS-error rate | daily |
| Lab spot check | Lighthouse mobile ×3 median (TBT informational only) | before + after |

### Baseline capture (7 days)

1. Register the GA4 custom definitions first (they only process data from registration forward):
   **Admin → Custom definitions → Create custom dimension** ×3 — `web_vitals_metric_name`, `web_vitals_metric_rating`, `web_vitals_metric_id` (Event-scoped) — and **Custom metrics** ×1 — `web_vitals_metric_value` (Standard unit).
2. Record daily: GA4 `web_vitals` event counts + p75 per metric (Explore → Free form, dimension `web_vitals_metric_name`, metric `web_vitals_metric_value`, drill `web_vitals_metric_rating`; use date comparison or export).
3. Snapshot CrUX p75 (LCP/INP/CLS, mobile) — note CrUX needs ~28 days of data and updates slowly; treat as confirmation, not the primary signal.
4. Save a Cloudflare settings snapshot (Speed → Optimization screen) and note the current toggle state.
5. Optionally automate the toggle via API for a crisp timestamp: `PATCH /zones/{zone_id}/settings/rocket_loader` with `{"value":"off"}` (needs a token with Zone Settings edit). Otherwise toggle manually and record the exact time.

### Toggle + immediate smoke (day 0, low-traffic window)

Toggle: **Cloudflare dashboard → Speed → Optimization → Contentful → Rocket Loader → Off.**

Within 15 minutes, run the smoke checks (headless capture works for this — see `/tmp/jqtest/capture.js` pattern):

- Homepage + one article: HTTP 200, all first-party scripts execute (no console errors), comment POST still works.
- GA4 still beacons (network shows `/g/collect`), Clarity still loads.
- No CSP violations in console (CSP is independent of Rocket Loader; nothing should change).

### Watch (days 0–3)

- GA4 real-time: `web_vitals` events still flowing; no drop in engaged sessions.
- Clarity: watch for dead-click/rage-click spikes (execution-order changes can break handlers).
- Laravel + nginx logs: no new errors.

### Decide (day 7)

Weekday-matched comparison (e.g., Mon-vs-Mon … Sun-vs-Sun) on p75 INP and LCP:

- **Keep off** if: p75 INP improves ≥10% (or is already <200 ms) AND guardrails flat AND no UX breakage.
- **Re-enable** if: any guardrail regresses (bounce +>5% relative, rage clicks up, comment submissions down) or field metrics worsen. Rollback is one toggle — nothing in the codebase depends on Rocket Loader being either on or off (verified: all scripts are standard `src` tags now).
- **Inconclusive** if: traffic too low for a signal — then extend the after-window to 14 days or accept the lab evidence and keep it off (the theoretical case for off is strong).

## Known confounds & notes

- Content/seasonality changes between weeks are not controlled by this design — note any major content pushes or marketing activity in both windows.
- The deferred GA4 loader's 4 s idle fallback means some lab Lighthouse traces still show gtag tasks; ignore lab TBT for the decision — INP is the field truth.
- If AdSense/monetization is enabled later, re-check ad-viewability metrics after toggling: Rocket Loader can change ad script timing in both directions.
- This document pairs with `DEPLOY_CHECKLIST.md` (deploy contract) — no code deploy is required for this experiment.
