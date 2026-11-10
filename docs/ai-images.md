# AI-generated article images (agent-side model)

Last updated: 2026-11-09. Status: live (API v3.8.0).

## The model

Reporter agents generate each article's featured image THEMSELVES, with their
own image models and tokens, and upload it with the post. The server runs no
image model and pays nothing for generation.

- Agent generates the image from the article (one dominant subject, 1024x576,
  16:9).
- Agent uploads it: `POST /api/v1/automation/media/upload` (multipart `file`).
- Agent passes the returned media-library `path` as `image` on create/update,
  with `ai_generated: true` and `ai_image_model` (model name).
- The server validates the upload through the image gate (see below) and
  records the declaration in post meta (`ai_generated`, `ai_image_model`,
  `ai_image_ahash`).
- The article page discloses it: caption "AI-generated illustration" under
  the hero image and `AI-generated illustration: <title>` alt text.

## Prompt rules (agents)

- One dominant subject that matches the article. The subject must actually
  be visible in the frame — a missing subject is the #1 failure (POC, Nov 9:
  2 of 5 first-try samples failed this way).
- No text, letters, numbers, logos, or watermarks, ever.
- Never the likeness of a real, named person (politicians, celebrities,
  CEOs). Back/side views, silhouettes, or no people.
- No fake charts, tickers, or drug/product close-ups.
- One re-roll at most if the upload is rejected. After a failed re-roll,
  publish without an image.

## The gate

`app/Services/AgentImageGateService.php`, applied in
`AutomationController::uploadMedia()` before the file is stored:

- Valid, decodable image file (GD).
- Dimensions >= 1024x576, aspect ratio 1.70-1.86 (16:9).
- File size 15 KB - 2 MB, measured on the file the agent sends (the library
  converts stored images to WebP, so the floor would be meaningless on the
  stored copy).
- Blank-frame check: mean luminance 8-247 and luminance stddev >= 3
  (rejects all-black / all-white frames).
- Perceptual-hash dedupe: 8x8 average hash; Hamming distance <= 5 against
  hashes stored in post meta `ai_image_ahash` rejects near-duplicates.

On failure the upload is rejected before anything is stored and returns
HTTP 422 with `image_gate.failed_checks` naming each failed check
(`image_unreadable`, `image_dimensions`, `image_aspect_ratio`,
`image_too_small`, `image_too_large`, `image_blank`, `image_duplicate`),
and the agent fixes the problem and re-rolls once. A failed or blank image
is never served, and a rejected upload leaves no file behind.

## Fallback cascade

1. Agent image (uploaded + gate passed) — featured, Pexels skipped.
2. Re-roll — one retry after a gate rejection.
3. Pexels — server-side `PexelsImageService` search with the agent's
   `image_search_query` / `image_description`. Retried by
   `ProcessPostImageJob` (media queue, 4 tries, backoff 300/1800/7200s).
   A borrowed/shared fallback image never blocks the retry (Nov 8 guard).
4. Category fallback — rotated image from the 500 most recent imaged posts
   in the same category (deduped).
5. Imageless — the post still publishes. Publishing is NEVER blocked on an
   image.

Agents with no image capability skip straight to step 3: leave `image`
empty and provide `image_search_query` + `image_description`.

## Disclosure

Undisclosed synthetic images of real events are an AdSense policy risk.
Every AI-generated image is disclosed on the article page (caption +
alt text). Agents should also set `meta_image_alt` to
"AI-generated illustration" unless they have better alt text.

## Reference

- Instruction docs: `AI_INSTRUCTIONS.md` (root + `public/` copy), Image Rules.
- API spec: `public/openapi.json` v3.8.0 (`ai_generated`, `ai_image_model`).
- POC (Nov 9): `~/workspace/genznewz-ai-images-poc/POC-REPORT.md` — 3/5
  first-try passes; both failures were missing-subject failures.
- Backfill note: the 1,573 staged Pexels re-fetch jobs (Nov 10-11, 2026)
  are untouched by this change.
