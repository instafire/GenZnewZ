{{-- Agent-facing resources live here, at the bottom of every page, so they
     never compete with reader-facing news above the fold. --}}
{!! Theme::partial('ai-agent-hub') !!}

<footer class="newspaper-footer">
    <div class="footer-container">
        {{-- Top Section --}}
        <div class="footer-top">
            <div class="footer-brand">
                <a href="{{ url('/') }}" class="footer-logo-grid-link" aria-label="GenZ NewZ Home">
                    <span class="footer-logo-frame">
                        <div class="gzn-logo footer-gzn-logo" aria-hidden="true">
                            <div class="gzn-left">
                                <div class="gzn-gen">
                                    <div class="gzn-curve"></div>
                                    <span class="gzn-gen-text">GEN</span>
                                </div>
                                <div class="gzn-newz-bar">
                                    <div class="gzn-marquee" aria-hidden="true">
                                        <span class="gzn-newz-outline">NEWZ</span>
                                        <span class="gzn-newz-solid">NEWZ</span>
                                        <span class="gzn-newz-outline">NEWZ</span>
                                        <span class="gzn-newz-solid">NEWZ</span>
                                        <span class="gzn-newz-outline">NEWZ</span>
                                        <span class="gzn-newz-solid">NEWZ</span>
                                    </div>
                                </div>
                            </div>
                            <div class="gzn-right">
                                <span class="gzn-z-text">Z</span>
                            </div>
                        </div>
                    </span>
                </a>
                <p class="footer-tagline">News for the Next Generation</p>
            </div>
            
            <div class="footer-social">
                <!-- Social links temporarily disabled -->
                <!-- Instagram -->
                <!-- TikTok -->
                <!-- YouTube -->
            </div>
        </div>
        
        {{-- Categories Grid --}}
        <div class="footer-categories">
            {{-- News & Politics --}}
            <div class="footer-column">
                <p class="footer-column-title">News & Politics</p>
                <ul class="footer-links">
                    <li><a href="{{ url('/topic/politics') }}">Politics</a></li>
                    <li><a href="{{ url('/topic/world-society') }}">The World</a></li>
                    <li><a href="{{ url('/topic/war') }}">War</a></li>
                    <li><a href="{{ url('/topic/canada') }}">Canada</a></li>
                    <li><a href="{{ url('/topic/social-justice') }}">Social Justice</a></li>
                    <li><a href="{{ url('/topic/human-rights') }}">Human Rights</a></li>
                    <li><a href="{{ url('/topic/youth-activists') }}">Youth Activists</a></li>
                </ul>
            </div>
            
            {{-- Tech & Science --}}
            <div class="footer-column">
                <p class="footer-column-title">Tech & Science</p>
                <ul class="footer-links">
                    <li><a href="{{ url('/topic/tech-games') }}">Tech & Games</a></li>
                    <li><a href="{{ url('/topic/ai-news') }}">AI News</a></li>
                    <li><a href="{{ url('/topic/latest-gadgets') }}">Latest Gadgets</a></li>
                    <li><a href="{{ url('/topic/climate-emergency') }}">Climate Emergency</a></li>
                    <li><a href="{{ url('/topic/deep-dives') }}">Deep Dives</a></li>
                    <li><a href="{{ url('/topic/conspiracies') }}">Conspiracies</a></li>
                </ul>
            </div>
            
            {{-- Culture --}}
            <div class="footer-column">
                <p class="footer-column-title">Culture</p>
                <ul class="footer-links">
                    <li><a href="{{ url('/topic/culture') }}">Culture</a></li>
                    <li><a href="{{ url('/topic/music') }}">Music</a></li>
                    <li><a href="{{ url('/topic/movies') }}">Movies</a></li>
                    <li><a href="{{ url('/topic/anime-animation') }}">Anime & Animation</a></li>
                    <li><a href="{{ url('/topic/internet-famous') }}">Internet Famous</a></li>
                    <li><a href="{{ url('/topic/streetwear') }}">Streetwear</a></li>
                    <li><a href="{{ url('/topic/aesthetics') }}">Aesthetics</a></li>
                </ul>
            </div>
            
            {{-- Lifestyle --}}
            <div class="footer-column">
                <p class="footer-column-title">Lifestyle</p>
                <ul class="footer-links">
                    <li><a href="{{ url('/topic/life-hacks') }}">Life Hacks</a></li>
                    <li><a href="{{ url('/topic/fashion') }}">Fashion</a></li>
                    <li><a href="{{ url('/topic/travel') }}">Travel</a></li>
                    <li><a href="{{ url('/topic/cooking-and-recipes') }}">Cooking</a></li>
                    <li><a href="{{ url('/topic/mind-body') }}">Mind & Body</a></li>
                    <li><a href="{{ url('/topic/sexual-wellness') }}">Sexual Wellness</a></li>
                    <li><a href="{{ url('/topic/productivity') }}">Productivity</a></li>
                </ul>
            </div>
            
            {{-- Money --}}
            <div class="footer-column">
                <p class="footer-column-title">Money & Career</p>
                <ul class="footer-links">
                    <li><a href="{{ url('/topic/career-path') }}">Career Path</a></li>
                    <li><a href="{{ url('/topic/investing-genz') }}">Investing</a></li>
                    <li><a href="{{ url('/topic/side-hustles') }}">Side Hustles</a></li>
                    <li><a href="{{ url('/topic/crypto') }}">Crypto</a></li>
                </ul>
            </div>
            
            {{-- Media & Fun --}}
            <div class="footer-column">
                <p class="footer-column-title">Media & Fun</p>
                <ul class="footer-links">
                    <li><a href="{{ url('/topic/trending-now') }}">Trending</a></li>
                    <li><a href="{{ url('/topic/videos') }}">Videos</a></li>
                    <li><a href="{{ url('/topic/podcasts') }}">Podcasts</a></li>
                    <li><a href="{{ url('/topic/irl-streams') }}">IRL Streams</a></li>
                    <li><a href="{{ url('/topic/quizzes') }}">Quizzes</a></li>
                    <li><a href="{{ url('/topic/horoscopes') }}">Horoscopes</a></li>
                    <li><a href="{{ url('/topic/sports') }}">Sports</a></li>
                </ul>
            </div>
        </div>
        
        {{-- Bottom Section --}}
        <div class="footer-bottom">
            <div class="footer-legal">
                <a href="{{ url('/about-us') }}">About Us</a>
                <a href="{{ url('/our-team') }}">Our Team</a>
                <a href="{{ url('/editorial-policy') }}">Editorial Policy</a>
                <a href="{{ url('/contact') }}">Contact</a>
                <a href="{{ url('/privacy-policy') }}">Privacy</a>
                <a href="{{ url('/terms-of-service') }}">Terms</a>
                <a href="{{ url('/cookie-policy') }}">Cookies</a>
                <a href="{{ url('/dmca') }}">DMCA</a>
                <a href="/press">Press Kit</a>
            </div>
            <p class="footer-external-links">
                External references:
                <a href="https://www.reuters.com/" target="_blank" rel="noopener noreferrer nofollow">Reuters</a>
                <span>•</span>
                <a href="https://apnews.com/" target="_blank" rel="noopener noreferrer nofollow">AP News</a>
            </p>
            <p class=footer-badge>
                <a href=https://aiagentsdirectory.com/agent/genznewz target=_blank rel=noopener title=Discover GenZnewZ on AI Agents Directory>
                    <img src=https://aiagentsdirectory.com/featured-badge.svg?v=2024 alt=GenZnewZ - Featured on AI Agents Directory width=200 height=50 loading=lazy />
                </a>
            </p>
            <p class="footer-disclosure">
                We use Microsoft Clarity to understand how readers interact with the site through analytics, heatmaps, and session replay.
                <a href="{{ url('/privacy-policy') }}">Privacy Policy</a>
                <span>•</span>
                <a href="https://privacy.microsoft.com/en-us/privacystatement" target="_blank" rel="noopener noreferrer nofollow">Microsoft Privacy Statement</a>
            </p>
            <p class="footer-copyright">© {{ date('Y') }} GenZ NewZ. All rights reserved.</p>
        </div>
    </div>
</footer>


{!! Theme::partial('news-chat-widget') !!}


{!! Theme::footer() !!}

</body>
</html>
