@php
    use FriendsOfBotble\Comment\Forms\Fronts\CommentForm;
    use FriendsOfBotble\Comment\Models\Comment;
    use FriendsOfBotble\Comment\Support\CommentHelper;
    
    $comments = collect();
    $commentCount = 0;
    
    if(is_plugin_active('fob-comment')) {
        Theme::asset()->add('fob-comment-css', asset('vendor/core/plugins/fob-comment/css/comment.css'), version: '1.1.19');
        Theme::asset()->container('footer')->add('fob-comment-js', asset('vendor/core/plugins/fob-comment/js/comment.js'), [], version: '1.2.0');
        Theme::registerToastNotification();

        // Comments styles extracted from the former inline <style> block (2026-09).
        // dep: theme-dark-css so it loads after every head stylesheet, matching
        // the original in-body inline position (renders before post.css).
        $commentsCssPath = platform_path('themes/newspaper/public/css/comments.css');
        Theme::asset()->usePath()->add(
            'comments-css',
            'css/comments.css',
            ['theme-dark-css'],
            [],
            file_exists($commentsCssPath) ? (string) filemtime($commentsCssPath) : null
        );
        
        // Get real comments from database - all approved comments
        $allComments = Comment::where('reference_type', 'Botble\\Blog\\Models\\Post')
            ->where('reference_id', $post->id)
            ->where('status', 'approved')
            ->whereNull('reply_to')
            ->with(['replies' => function($q) {
                $q->where('status', 'approved');
            }])
            ->orderBy('created_at', 'desc')
            ->get();
            
        $commentCount = $allComments->count();
        // The Reader Picks tab is only meaningful when a moderator has actually
        // selected something, so it is hidden otherwise.
        $readerPickCount = $allComments
            ->filter(fn ($comment) => (bool) $comment->getMetaData('reader_pick', true))
            ->count();
        $comments = $allComments; // All comments for display
        $initialComments = $allComments->take(5); // First 5 to show
        $hasMore = $allComments->count() > 5;
    }
@endphp

@if(is_plugin_active('fob-comment'))
    {{-- comment.js reads window.fobComment.listUrl; the plugin's own view normally outputs this. --}}
    <script>
        window.fobComment = {
            listUrl: {{ Js::from(route('fob-comment.public.comments.index', [
                'reference_type' => $post::class,
                'reference_id' => $post->id,
            ])) }},
        };
    </script>

    {{-- NYT-Style Comments Section --}}
    <div class="nyt-comments-section" id="comments">
        {{-- Comments Header --}}
        <div class="nyt-comments-header">
            <div class="nyt-comments-header-main">
                <h3 class="nyt-comments-title">
                    Comments <span class="nyt-comments-count">{{ $commentCount }}</span>
                </h3>
                
                @if($commentCount > 0)
                <div class="nyt-comments-tabs">
                    <button class="nyt-tab nyt-tab--active" data-tab="all">All</button>
                    @if($readerPickCount > 0)
                        <button class="nyt-tab" data-tab="reader-picks">Reader Picks ({{ $readerPickCount }})</button>
                    @endif
                </div>
                @endif
            </div>
            
            @if($commentCount > 0)
            <div class="nyt-comments-sort">
                <label>Sort by:</label>
                <select id="comment-sort">
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                </select>
            </div>
            @endif
        </div>

        {{-- Comments List - NOW AT TOP --}}
        <div class="nyt-comments-list-container">
            @if($comments->count() > 0)
                <div class="nyt-comments-list" id="comments-list">
                    @foreach($comments as $index => $comment)
                        @php
                            // Reader Picks are chosen by moderators, not invented. A
                            // fabricated recommend count on a news site is a fake
                            // trust signal, so the UI only reports what is real:
                            // moderator selection, the author, and the timestamp.
                            $isReaderPick = (bool) $comment->getMetaData('reader_pick', true);
                            $isHidden = $index >= 5; // Hide after first 5
                        @endphp
                        
                        <div class="nyt-comment-card {{ $isReaderPick ? 'nyt-comment--reader-pick' : '' }} {{ $isHidden ? 'nyt-comment--hidden' : '' }}" 
                             data-date="{{ $comment->created_at->timestamp }}"
                             data-index="{{ $index }}">
                            <div class="nyt-comment-avatar">
                                {{ strtoupper(substr($comment->name, 0, 1)) }}
                            </div>
                            
                            <div class="nyt-comment-content">
                                <div class="nyt-comment-meta">
                                    <span class="nyt-comment-author">{{ $comment->name }}</span>
                                    @if ($isReaderPick)
                                        <span class="nyt-comment-pick-badge">Reader Pick</span>
                                    @endif
                                    <span class="nyt-comment-date">{{ $comment->created_at->format('M. j') }}</span>
                                </div>
                                
                                <div class="nyt-comment-body">
                                    {!! $comment->formatted_content !!}
                                </div>
                                
                                <div class="nyt-comment-actions">
                                    <button class="nyt-action-btn nyt-reply-btn" data-comment-id="{{ $comment->id }}">
                                        Reply
                                    </button>
                                    
                                    @if($comment->replies->count() > 0)
                                        <button class="nyt-action-btn nyt-view-replies-btn" data-comment-id="{{ $comment->id }}">
                                            {{ $comment->replies->count() }} {{ $comment->replies->count() == 1 ? 'Reply' : 'Replies' }}
                                        </button>
                                    @endif
                                </div>
                                
                                {{-- Replies --}}
                                @if($comment->replies->count() > 0)
                                    <div class="nyt-comment-replies" id="replies-{{ $comment->id }}" style="display: none;">
                                        @foreach($comment->replies as $reply)
                                            <div class="nyt-reply-card">
                                                <div class="nyt-reply-avatar">
                                                    {{ strtoupper(substr($reply->name, 0, 1)) }}
                                                </div>
                                                <div class="nyt-reply-content">
                                                    <div class="nyt-reply-meta">
                                                        <span class="nyt-reply-author">{{ $reply->name }}</span>
                                                        <span class="nyt-reply-date">{{ $reply->created_at->format('M. j') }}</span>
                                                    </div>
                                                    <div class="nyt-reply-body">
                                                        {!! $reply->formatted_content !!}
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                
                {{-- Load More Button --}}
                @if($hasMore)
                    <div class="nyt-comments-load-more">
                        <button class="nyt-load-more-btn" id="load-more-comments">
                            Load more comments ({{ $comments->count() - 5 }} more)
                        </button>
                    </div>
                @endif
            @else
                <div class="nyt-comments-empty">
                    <p>No comments yet. Be the first to share your thoughts!</p>
                </div>
            @endif
        </div>

        {{-- Comment Form - NOW AT BOTTOM --}}
        <div class="nyt-comment-form-section">
            <div class="nyt-comment-form-card">
                <h4 class="nyt-form-title">Leave a comment</h4>
                <p class="nyt-form-subtitle">Share your thoughts. Your email will not be published.</p>
                {!! CommentForm::createWithReference($post)->renderForm() !!}
            </div>
        </div>
    </div>


    <script>
        // Tab switching
        document.querySelectorAll('.nyt-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.nyt-tab').forEach(t => t.classList.remove('nyt-tab--active'));
                this.classList.add('nyt-tab--active');
                
                const tabName = this.dataset.tab;
                const cards = document.querySelectorAll('.nyt-comment-card');
                
                cards.forEach(card => {
                    if (card.classList.contains('nyt-comment--hidden')) return; // Respect load more
                    
                    if (tabName === 'reader-picks') {
                        card.style.display = card.classList.contains('nyt-comment--reader-pick') ? 'flex' : 'none';
                    } else {
                        card.style.display = 'flex';
                    }
                });
            });
        });

        // Sorting
        const sortSelect = document.getElementById('comment-sort');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                const sortBy = this.value;
                const list = document.querySelector('.nyt-comments-list');
                const cards = Array.from(list.querySelectorAll('.nyt-comment-card:not(.nyt-comment--hidden)'));
                
                cards.sort((a, b) => {
                    if (sortBy === 'oldest') {
                        return parseInt(a.dataset.date) - parseInt(b.dataset.date);
                    }

                    return parseInt(b.dataset.date) - parseInt(a.dataset.date);
                });
                
                cards.forEach(card => list.appendChild(card));
            });
        }

        // Load more comments
        const loadMoreBtn = document.getElementById('load-more-comments');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                const hiddenComments = document.querySelectorAll('.nyt-comment-card.nyt-comment--hidden');
                
                // Show 5 more comments
                let count = 0;
                hiddenComments.forEach(comment => {
                    if (count < 5) {
                        comment.classList.remove('nyt-comment--hidden');
                        comment.style.display = 'flex';
                        count++;
                    }
                });
                
                // Update button or hide if no more
                const remaining = document.querySelectorAll('.nyt-comment-card.nyt-comment--hidden').length;
                if (remaining === 0) {
                    this.style.display = 'none';
                } else {
                    this.textContent = `Load more comments (${remaining} more)`;
                }
            });
        }

        // Toggle replies
        document.querySelectorAll('.nyt-view-replies-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const commentId = this.dataset.commentId;
                const repliesDiv = document.getElementById('replies-' + commentId);
                if (repliesDiv) {
                    const isVisible = repliesDiv.style.display !== 'none';
                    repliesDiv.style.display = isVisible ? 'none' : 'block';
                    this.textContent = isVisible 
                        ? this.textContent.replace('Hide', 'View').replace('hide', 'view')
                        : this.textContent.replace('View', 'Hide').replace('view', 'hide');
                }
            });
        });
    </script>
@else
    <div class="nyt-comments-section">
        <div class="nyt-comments-empty">
            <p>Comments are currently unavailable.</p>
        </div>
    </div>
@endif
