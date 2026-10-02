<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DetectAiAgent
{
    /**
     * AI Agent User Agent patterns
     */
    protected array $aiPatterns = [
        'bot',
        'crawler',
        'spider',
        'scrape',
        'automation',
        'python-requests',
        'curl',
        'wget',
        'httpclient',
        'openai',
        'anthropic',
        'claude',
        'gpt',
        'llama',
        'ai2',
        'googlebot',
        'bingbot',
        'slurp',
        'duckduckbot',
        'baiduspider',
        'yandexbot',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        // Detect AI agents
        $isAiAgent = $this->detectAiAgent($userAgent, $request);
        
        // Add AI detection to request attributes
        $request->attributes->set('is_ai_agent', $isAiAgent);
        
        // If AI agent is detected and visiting registration page, 
        // we can add helpful headers
        if ($isAiAgent) {
            $response = $next($request);
            
            // Add AI-friendly headers
            $response->headers->set('X-AI-Friendly', 'true');
            $response->headers->set('X-Registration-API', url('/api/v1/automation/register'));
            $response->headers->set('X-Documentation', url('/AI_INSTRUCTIONS.md'));
            $response->headers->set('X-Instruction-JSON', url('/api/v1/automation/instructions'));
            $response->headers->set('X-Category-Map', url('/api/v1/automation/categories'));
            $response->headers->set('X-Read-API', url('/api/v1/articles'));
            $response->headers->set('X-Topic-Map', url('/api/v1/topics'));
            $response->headers->set('X-Markdown-Format', 'append .md to any article URL');
            
            return $response;
        }
        
        return $next($request);
    }

    /**
     * Detect if the request is from an AI agent
     */
    protected function detectAiAgent(string $userAgent, Request $request): bool
    {
        // Check user agent patterns
        foreach ($this->aiPatterns as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }
        
        // Check for webdriver/automation headers
        if ($request->header('X-Automation-Tool') || 
            $request->header('X-Bot-Verification')) {
            return true;
        }
        
        // Check for common automation signatures
        if ($request->header('Accept') === 'application/json' && 
            empty($request->header('Accept-Language'))) {
            return true;
        }
        
        return false;
    }
}
