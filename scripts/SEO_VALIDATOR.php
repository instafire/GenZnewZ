<?php
/**
 * GenZ NewZ - SEO Content Validator
 * 
 * Use this script to validate your article before submitting via API
 * Run: php SEO_VALIDATOR.php
 */

class SEOValidator {
    
    public function validate($data) {
        $errors = [];
        $warnings = [];
        
        // 1. Validate SEO Title (50-60 chars)
        if (isset($data['seo_title'])) {
            $length = strlen($data['seo_title']);
            if ($length < 50 || $length > 60) {
                $errors[] = "SEO Title must be 50-60 characters. Current: {$length} chars";
            }
            if (substr_count($data['seo_title'], '🔥') + substr_count($data['seo_title'], '🤯') > 1) {
                $warnings[] = "SEO Title has excessive emojis (max 1 recommended)";
            }
        } else {
            $errors[] = "Missing required field: seo_title";
        }
        
        // 2. Validate Meta Description (150-160 chars)
        if (isset($data['meta_description'])) {
            $length = strlen($data['meta_description']);
            if ($length < 150 || $length > 160) {
                $errors[] = "Meta Description must be 150-160 characters. Current: {$length} chars";
            }
        } else {
            $errors[] = "Missing required field: meta_description";
        }
        
        // 3. Validate Focus Keyword (no slang)
        if (isset($data['focus_keyword'])) {
            $slangWords = ['vibes', 'slay', 'FR', 'no cap', 'tbh', 'ngl', 'lowkey', 'highkey', 'rent free'];
            foreach ($slangWords as $slang) {
                if (stripos($data['focus_keyword'], $slang) !== false) {
                    $errors[] = "Focus keyword contains slang: '{$slang}'. Use professional terms.";
                }
            }
        } else {
            $errors[] = "Missing required field: focus_keyword";
        }
        
        // 4. Check keyword appears in meta description
        if (isset($data['focus_keyword']) && isset($data['meta_description'])) {
            if (stripos($data['meta_description'], $data['focus_keyword']) === false) {
                $errors[] = "Focus keyword MUST appear in meta description";
            }
        }
        
        // 5. Validate content has proper headings
        if (isset($data['content'])) {
            // Check for H1 (should NOT be present - CMS adds it)
            if (preg_match('/<h1[^>]*>/i', $data['content'])) {
                $errors[] = "Content contains <h1> tag. Remove it - CMS adds H1 automatically from title.";
            }
            
            // Check for H2
            if (!preg_match('/<h2[^>]*>/i', $data['content'])) {
                $warnings[] = "No H2 headings found. Add at least 2-4 H2 sections.";
            }
            
            // Check for internal links
            if (!preg_match('/genznewz\.com/', $data['content'])) {
                $warnings[] = "No internal links to genznewz.com found. Add 2-3 internal links.";
            }
            
            // Check word count
            $wordCount = str_word_count(strip_tags($data['content']));
            if ($wordCount < 150) {
                $errors[] = "Content too short: {$wordCount} words. Minimum: 150 words.";
            }
            
            // Check first paragraph for keyword
            if (isset($data['focus_keyword'])) {
                $firstPara = preg_match('/<p[^>]*>(.*?)<\/p>/is', $data['content'], $matches) ? $matches[1] : '';
                $first100Words = implode(' ', array_slice(explode(' ', strip_tags($firstPara)), 0, 100));
                if (stripos($first100Words, $data['focus_keyword']) === false) {
                    $errors[] = "Focus keyword MUST appear in first 100 words of content.";
                }
            }
        }
        
        // 6. Validate image alt text
        if (isset($data['image_alt_text'])) {
            $wordCount = str_word_count($data['image_alt_text']);
            if ($wordCount < 3 || $wordCount > 15) {
                $warnings[] = "Image alt text should be 5-10 words. Current: {$wordCount} words.";
            }
            if (strtolower($data['image_alt_text']) === 'image' || strtolower($data['image_alt_text']) === 'photo') {
                $errors[] = "Image alt text is too generic. Describe the actual image content.";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
}

// Example usage
if (php_sapi_name() === 'cli') {
    echo "=== GenZ NewZ SEO Validator ===\n\n";
    
    $validator = new SEOValidator();
    
    // Example test data
    $testData = [
        'seo_title' => 'AI Art Tools 2026: Top 5 Apps Gen Z Artists Love',
        'meta_description' => 'Discover the top 5 AI art tools revolutionizing creativity for Gen Z artists in 2026. From Midjourney to DALL-E, explore features.',
        'focus_keyword' => 'AI art tools 2026',
        'content' => '<p>Artificial intelligence has transformed how Gen Z creates art. AI art tools in 2026 offer unprecedented creative possibilities for young artists worldwide.</p><h2>Top AI Art Tools</h2><p>Here are the best tools available.</p>',
        'image_alt_text' => 'Gen Z artist using AI art generation software'
    ];
    
    $result = $validator->validate($testData);
    
    if ($result['valid']) {
        echo "✅ VALIDATION PASSED! Ready to submit.\n";
    } else {
        echo "❌ VALIDATION FAILED - Fix these errors:\n";
        foreach ($result['errors'] as $error) {
            echo "  • {$error}\n";
        }
    }
    
    if (!empty($result['warnings'])) {
        echo "\n⚠️  Warnings (recommended fixes):\n";
        foreach ($result['warnings'] as $warning) {
            echo "  • {$warning}\n";
        }
    }
    
    echo "\n=== Usage in Your Code ===\n";
    echo "require_once 'SEO_VALIDATOR.php';\n";
    echo "\$validator = new SEOValidator();\n";
    echo "\$result = \$validator->validate(\$yourArticleData);\n";
    echo "if (\$result['valid']) { /* Submit to API */ }\n";
}
