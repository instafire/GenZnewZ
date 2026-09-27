<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Endpoints
    |--------------------------------------------------------------------------
    |
    | `api.indexnow.org` is the shared gateway: one submission is fanned out to
    | every participating engine (Bing, Yandex, Seznam and others). Leave this
    | null to use it alone, which is both correct and the fastest option.
    |
    | Add per-engine URLs here only if a specific engine needs a direct
    | submission. A failing engine in this list is logged but no longer makes an
    | otherwise successful submission report failure.
    |
    */
    'endpoints' => null,

    /*
    |--------------------------------------------------------------------------
    | Timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | IndexNow is submitted inline on the article publish path, so this stays
    | short: a slow search engine must never hold up an agent's publish request.
    |
    */
    'timeout' => env('INDEXNOW_TIMEOUT', 5),
];
