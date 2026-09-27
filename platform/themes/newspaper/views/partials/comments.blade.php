@php
    $postId = $post->id ?? 0;
    $member = Auth::guard('member')->user();
    $user = Auth::guard()->user();
    $isLoggedIn = $member || $user;
    
    // Get comments for this post (using correct namespace and status)
    $comments = \FriendsOfBotble\Comment\Models\Comment::where('reference_type', 'Botble\Blog\Models\Post')
        ->where('reference_id', $postId)
        ->where('status', 'approved')
        ->orderBy('created_at', 'desc')
        ->get();
@endphp

<section class="comments-section" id="comments">
    <div class="comments-container">
        <h3 class="comments-title">
            💬 Comments ({{ $comments->count() }})
        </h3>

        @if ($isLoggedIn)
            <div class="comment-form-wrapper">
                <div class="comment-user">
                    @if ($member)
                        <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="comment-avatar">
                        <span class="comment-username">{{ $member->name }}</span>
                    @else
                        <img src="https://www.gravatar.com/avatar/{{ md5(strtolower($user?->email ?? '')) }}?d=mp&s=50" alt="{{ $user?->name ?? 'Guest' }}" class="comment-avatar">
                        <span class="comment-username">{{ $user?->name ?? 'Guest' }}</span>
                    @endif
                </div>
                
                <form id="commentForm" class="comment-form" data-post-id="{{ $postId }}">
                    @csrf
                    <textarea 
                        name="content" 
                        id="commentContent" 
                        class="comment-textarea" 
                        placeholder="Share your thoughts..."
                        rows="4"
                        required
                        maxlength="2000"
                    ></textarea>
                    <div class="comment-form-actions">
                        <span class="char-count"><span id="charCount">0</span> / 2000</span>
                        <button type="submit" class="comment-submit-btn">
                            Post Comment
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="comment-login-prompt">
                <p>📝 Want to join the conversation?</p>
                <div class="comment-login-actions">
                    <a href="{{ url('/login') }}" class="comment-login-btn">Log In</a>
                    <a href="{{ url('/register') }}" class="comment-signup-btn">Sign Up</a>
                </div>
            </div>
        @endif

        <div class="comments-list" id="commentsList">
            @forelse ($comments as $comment)
                <div class="comment-item" data-comment-id="{{ $comment->id }}">
                    <div class="comment-header">
                        <img 
                            src="https://www.gravatar.com/avatar/{{ md5(strtolower($comment->email)) }}?d=mp&s=50" 
                            alt="{{ $comment->name }}" 
                            class="comment-avatar"
                        >
                        <div class="comment-meta">
                            <span class="comment-author">{{ $comment->name }}</span>
                            <span class="comment-date">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <div class="comment-content">
                        {!! nl2br(e($comment->content)) !!}
                    </div>
                </div>
            @empty
                <div class="no-comments">
                    <p>🤔 No comments yet. Be the first to share your thoughts!</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

<style>
.comments-section {
    max-width: 800px;
    margin: 50px auto;
    padding: 0 20px;
}

.comments-container {
    background: #fafafa;
    border-radius: 16px;
    padding: 30px;
}

.comments-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e2e2e2;
}

/* Comment Form */
.comment-form-wrapper {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.comment-user {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.comment-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.comment-username {
    font-weight: 600;
    color: #333;
}

.comment-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.comment-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e2e2;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 1rem;
    resize: vertical;
    min-height: 100px;
    transition: border-color 0.2s;
}

.comment-textarea:focus {
    outline: none;
    border-color: #326891;
}

.comment-form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.char-count {
    font-size: 0.85rem;
    color: #888;
}

.comment-submit-btn {
    padding: 10px 24px;
    background: linear-gradient(135deg, #FF006E 0%, #FB5607 50%, #FFBE0B 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.comment-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 0, 110, 0.3);
}

.comment-submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Login Prompt */
.comment-login-prompt {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.comment-login-prompt p {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 20px;
}

.comment-login-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.comment-login-btn,
.comment-signup-btn {
    padding: 12px 28px;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.comment-login-btn {
    background: #f5f5f5;
    color: #333;
}

.comment-login-btn:hover {
    background: #e2e2e2;
}

.comment-signup-btn {
    background: linear-gradient(135deg, #FF006E 0%, #FB5607 50%, #FFBE0B 100%);
    color: white;
}

.comment-signup-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 0, 110, 0.3);
}

/* Comments List */
.comments-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.comment-item {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.comment-meta {
    display: flex;
    flex-direction: column;
}

.comment-author {
    font-weight: 600;
    color: #333;
}

.comment-date {
    font-size: 0.8rem;
    color: #888;
}

.comment-content {
    color: #444;
    line-height: 1.6;
    font-size: 0.95rem;
}

.no-comments {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.no-comments p {
    font-size: 1.1rem;
}

/* Loading State */
.comment-loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Responsive */
@media (max-width: 600px) {
    .comments-container {
        padding: 20px;
    }
    
    .comment-login-actions {
        flex-direction: column;
    }
    
    .comment-form-actions {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
    }
    
    .comment-submit-btn {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const commentForm = document.getElementById('commentForm');
    const commentContent = document.getElementById('commentContent');
    const charCount = document.getElementById('charCount');
    const commentsList = document.getElementById('commentsList');

    // Character count
    if (commentContent) {
        commentContent.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }

    // Submit comment
    if (commentForm) {
        commentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const postId = this.dataset.postId;
            const content = commentContent.value.trim();
            
            if (!content) return;

            const submitBtn = this.querySelector('.comment-submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Posting...';

            try {
                const response = await fetch('{{ url("/comments") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        post_id: postId,
                        content: content
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Clear form
                    commentContent.value = '';
                    charCount.textContent = '0';

                    if (data.pending) {
                        alert(data.message || 'Comment submitted and waiting for moderation.');
                    } else if (data.comment) {
                        const newComment = createCommentElement(data.comment);
                        const noComments = commentsList.querySelector('.no-comments');

                        if (noComments) {
                            noComments.remove();
                        }

                        commentsList.insertBefore(newComment, commentsList.firstChild);
                        updateCommentCount(1);
                    }
                } else {
                    alert(data.message || 'Failed to post comment');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Post Comment';
            }
        });
    }

    function createCommentElement(comment) {
        const div = document.createElement('div');
        div.className = 'comment-item';
        div.innerHTML = `
            <div class="comment-header">
                <img src="${comment.avatar}" alt="${comment.author}" class="comment-avatar">
                <div class="comment-meta">
                    <span class="comment-author">${comment.author}</span>
                    <span class="comment-date">${comment.date}</span>
                </div>
            </div>
            <div class="comment-content">${comment.content.replace(/\n/g, '<br>')}</div>
        `;
        return div;
    }

    function updateCommentCount(change) {
        const title = document.querySelector('.comments-title');
        const match = title.textContent.match(/\((\d+)\)/);
        if (match) {
            const newCount = parseInt(match[1]) + change;
            title.textContent = `💬 Comments (${newCount})`;
        }
    }
});
</script>
