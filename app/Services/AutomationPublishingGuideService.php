<?php

namespace App\Services;

use Botble\Blog\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AutomationPublishingGuideService
{
    public function getGuide(): array
    {
        return Cache::remember('automation_publishing_guide_v1', now()->addMinutes(10), function () {
            if (! Schema::hasTable('categories')) {
                return $this->emptyGuide();
            }

            $categories = Category::query()
                ->select(['id', 'name', 'description', 'parent_id', 'order'])
                ->with('slugable')
                ->where('status', 'published')
                ->orderBy('parent_id')
                ->orderBy('order')
                ->orderBy('name')
                ->get();

            $categoriesById = $categories->keyBy('id');
            $childrenByParent = $categories->groupBy('parent_id');

            $topLevelCategories = $childrenByParent
                ->get(0, collect())
                ->values()
                ->map(fn (Category $category) => $this->transformCategory($category, $childrenByParent, $categoriesById))
                ->all();

            $childCategories = $categories
                ->filter(fn (Category $category) => (int) $category->parent_id !== 0)
                ->values()
                ->map(fn (Category $category) => $this->transformCategory($category, $childrenByParent, $categoriesById))
                ->all();

            $allCategories = $categories
                ->values()
                ->map(fn (Category $category) => $this->transformCategory($category, $childrenByParent, $categoriesById))
                ->all();

            return [
                'generated_at' => now()->toIso8601String(),
                'taxonomy' => [
                    'all_count' => count($allCategories),
                    'top_level_count' => count($topLevelCategories),
                    'child_count' => count($childCategories),
                    'selection_rules' => [
                        'Fetch the live category map before writing. Do not guess the taxonomy from memory.',
                        'Choose the single best-fit primary category for the story angle. Add extra categories only when the story genuinely spans multiple beats.',
                        'If a child category is selected, the API automatically attaches its parent category so homepage topic placement still works.',
                        'When no child category fits exactly, use the nearest top-level category instead of forcing a weak match.',
                    ],
                    'top_level_categories' => $topLevelCategories,
                    'child_categories' => $childCategories,
                    'all_categories' => $allCategories,
                ],
                'formats' => [
                    [
                        'key' => 'default',
                        'label' => 'Standard story',
                        'use_when' => 'Use for most reported articles, explainers, and breaking-news writeups.',
                        'placement' => 'Eligible for homepage latest stories, topic pages, editor-style surfaces, and trending placements.',
                    ],
                    [
                        'key' => 'text-only',
                        'label' => 'AI text spotlight',
                        'use_when' => 'Use for strong text-led coverage where article still stands without a visual-first treatment.',
                        'placement' => 'Homepage has a dedicated AI text-only surface. These posts are excluded from the image-led featured rail.',
                    ],
                    [
                        'key' => 'video',
                        'label' => 'Video-led story',
                        'use_when' => 'Use when the article is built around video coverage and should fit video templates.',
                        'placement' => 'Can flow into video-specific templates and the Videos lane when category fit is correct.',
                    ],
                ],
                'featured_guidance' => [
                    'Set `is_featured=true` only for high-priority stories that should compete for the homepage featured rail.',
                    'Homepage featured rail prioritizes published featured posts with real images. If too few exist, the site backfills with other strong image posts.',
                    'Image-less stories do not qualify for the homepage featured rail, so featured candidates should always include strong image guidance.',
                    'Text-only stories can still be important, but they surface in the AI text block rather than the image-led featured rail.',
                ],
                'site_features' => [
                    [
                        'name' => 'Homepage latest stories',
                        'summary' => 'Newest published articles surface here first regardless of category.',
                        'agent_action' => 'Keep titles/descriptions tight because these cards are often the first reader touchpoint.',
                    ],
                    [
                        'name' => 'Homepage featured stories',
                        'summary' => 'Image-led top rail for featured stories and strong fallback image posts.',
                        'agent_action' => 'Use `is_featured=true` sparingly and always pair it with good image guidance.',
                    ],
                    [
                        'name' => 'AI text-only spotlight',
                        'summary' => 'Separate homepage block for text-only AI stories.',
                        'agent_action' => 'Use `format_type=text-only` only when text-first presentation is intentional.',
                    ],
                    [
                        'name' => 'Editor-style picks and trending',
                        'summary' => 'Freshly updated stories and high-view stories can surface beyond the main hero area.',
                        'agent_action' => 'Update existing posts when coverage evolves instead of duplicating the same story.',
                    ],
                    [
                        'name' => 'Topic strips and category pages',
                        'summary' => 'Top-level categories drive homepage topic strips and dedicated `/topic/...` pages.',
                        'agent_action' => 'Put the story in the right category so it lands in the correct lane.',
                    ],
                    [
                        'name' => 'Today\'s Paper',
                        'summary' => 'Curated reading surface for broader daily coverage.',
                        'agent_action' => 'Write stories as complete reader-facing articles, not internal notes or update fragments.',
                    ],
                    [
                        'name' => 'Live World Events',
                        'summary' => 'Live event coverage exists as a dedicated site surface.',
                        'agent_action' => 'Use precise sourcing and updates when covering evolving world events or conflicts.',
                    ],
                    [
                        'name' => 'Video lanes',
                        'summary' => 'Video pages and templates exist separately from standard text/article views.',
                        'agent_action' => 'Use `format_type=video` only when the story truly belongs in that presentation.',
                    ],
                ],
                'submission_tips' => [
                    'Validate finished copy before create or update calls.',
                    'Use update endpoint for ongoing stories so views, topic relevance, and accountability stay attached to one post.',
                    'Include image fields even when the API can rewrite weak prompts, because better prompts improve homepage-ready visuals.',
                ],
            ];
        });
    }

    protected function emptyGuide(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'taxonomy' => [
                'all_count' => 0,
                'top_level_count' => 0,
                'child_count' => 0,
                'selection_rules' => [
                    'Fetch the live category map before writing. Do not guess the taxonomy from memory.',
                    'Choose the single best-fit primary category for the story angle.',
                ],
                'top_level_categories' => [],
                'child_categories' => [],
                'all_categories' => [],
            ],
            'formats' => [
                [
                    'key' => 'default',
                    'label' => 'Standard story',
                    'use_when' => 'Use for most reported articles, explainers, and breaking-news writeups.',
                    'placement' => 'Eligible for homepage latest stories and topic pages.',
                ],
                [
                    'key' => 'text-only',
                    'label' => 'AI text spotlight',
                    'use_when' => 'Use for strong text-led coverage.',
                    'placement' => 'Eligible for the homepage text-only surface.',
                ],
                [
                    'key' => 'video',
                    'label' => 'Video-led story',
                    'use_when' => 'Use when the article is built around video coverage.',
                    'placement' => 'Eligible for video-specific templates.',
                ],
            ],
            'featured_guidance' => [
                'Set is_featured=true only for high-priority stories.',
                'Featured stories should include strong image guidance.',
            ],
            'site_features' => [],
            'submission_tips' => [
                'Validate finished copy before create or update calls.',
                'Use the update endpoint for ongoing stories instead of creating duplicates.',
            ],
        ];
    }

    protected function transformCategory(Category $category, Collection $childrenByParent, Collection $categoriesById): array
    {
        $parent = $category->parent_id ? $categoriesById->get((int) $category->parent_id) : null;
        $children = $childrenByParent
            ->get((int) $category->id, collect())
            ->values()
            ->map(function (Category $child) {
                return [
                    'id' => (int) $child->id,
                    'name' => (string) $child->name,
                    'slug' => (string) optional($child->slugable)->key,
                    'description' => $this->normalizeDescription($child->description),
                ];
            })
            ->all();

        $slug = (string) optional($category->slugable)->key;
        $homeCategory = $parent ?: $category;

        return [
            'id' => (int) $category->id,
            'name' => (string) $category->name,
            'slug' => $slug,
            'description' => $this->normalizeDescription($category->description),
            'parent_id' => (int) $category->parent_id,
            'parent_name' => $parent ? (string) $parent->name : null,
            'is_top_level' => (int) $category->parent_id === 0,
            'children' => $children,
            'home_section_category' => (string) $homeCategory->name,
            'home_section_slug' => (string) optional($homeCategory->slugable)->key,
            'topic_url' => $slug !== '' ? url('/topic/' . $slug) : null,
            'assignment_rule' => $parent
                ? 'Use for narrower stories inside this lane. Parent category is auto-attached on submit.'
                : 'Use as primary category when no child category is more precise.',
        ];
    }

    protected function normalizeDescription(?string $description): ?string
    {
        $description = Str::squish((string) $description);

        return $description !== '' ? $description : null;
    }
}
