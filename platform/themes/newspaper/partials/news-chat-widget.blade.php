<div id="gzn-news-chat" class="gzn-news-chat" data-endpoint="{{ route('api.news-chat.message') }}">
    <button type="button" class="gzn-news-chat__trigger" data-chat-toggle aria-controls="gzn-news-chat-panel" aria-expanded="false" aria-label="Open GenZ Ai chat">
        <span class="gzn-news-chat__trigger-dot" aria-hidden="true">Ai News</span>
    </button>

    <section id="gzn-news-chat-panel" class="gzn-news-chat__panel" data-chat-panel hidden aria-live="polite">
        <header class="gzn-news-chat__header">
            <div>
                <h2>GenZ Ai</h2>
                <p>Ai News Agent</p>
            </div>
            <button type="button" class="gzn-news-chat__close" data-chat-close aria-label="Close chat">&times;</button>
        </header>

        <div class="gzn-news-chat__messages" data-chat-messages></div>

        <form class="gzn-news-chat__form" data-chat-form>
            <label class="sr-only" for="gzn-news-chat-input">Ask about GenZ NewZ stories</label>
            <textarea
                id="gzn-news-chat-input"
                class="gzn-news-chat__input"
                data-chat-input
                placeholder="Ask about latest headlines, major stories, or GenZ NewZ coverage..."
                rows="2"
                maxlength="1500"
                required
            ></textarea>
            <button type="submit" class="gzn-news-chat__send" data-chat-send>Send</button>
        </form>
    </section>
</div>


