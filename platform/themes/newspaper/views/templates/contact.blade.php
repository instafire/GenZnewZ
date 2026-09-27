@php
    use Botble\SeoHelper\Facades\SeoHelper;

    SeoHelper::setTitle('Contact Us | GenZ NewZ');
    SeoHelper::setDescription('Contact the GenZ NewZ newsroom for story tips, corrections, partnerships, technical support, or general questions.');
@endphp

<section class="gzn-static-page">
    <article class="gzn-static-shell">
        <header class="gzn-static-header">
            <span class="gzn-static-kicker">Connect</span>
            <h1 class="gzn-static-title">Contact Us</h1>
            <p class="gzn-static-subtitle">Reach the GenZ NewZ newsroom for tips, feedback, partnerships, and support. We route every message to the right editor or operator.</p>
        </header>

        <div class="gzn-static-body gzn-contact-layout">
            <div>
                <p>Have a story tip, correction, partnership inquiry, or technical issue? Send a message and include as much detail as possible so we can respond faster.</p>

                <div class="gzn-contact-cards">
                    <section class="gzn-contact-card">
                        <h3>General Inquiries</h3>
                        <p><a href="mailto:hello@genznewz.com">hello@genznewz.com</a></p>
                    </section>

                    <section class="gzn-contact-card">
                        <h3>Story Tips</h3>
                        <p><a href="mailto:tips@genznewz.com">tips@genznewz.com</a></p>
                    </section>

                    <section class="gzn-contact-card">
                        <h3>Partnerships</h3>
                        <p><a href="mailto:partnerships@genznewz.com">partnerships@genznewz.com</a></p>
                    </section>

                    <section class="gzn-contact-card">
                        <h3>Technical Support</h3>
                        <p><a href="mailto:support@genznewz.com">support@genznewz.com</a></p>
                    </section>
                </div>

                <h2>Send a Message</h2>
                <form class="gzn-contact-form" action="mailto:hello@genznewz.com" method="post" enctype="text/plain">
                    <div class="gzn-form-row">
                        <div class="gzn-form-group">
                            <label for="name">Your Name</label>
                            <input class="gzn-input" id="name" name="name" type="text" placeholder="Jane Doe" required>
                        </div>
                        <div class="gzn-form-group">
                            <label for="email">Email Address</label>
                            <input class="gzn-input" id="email" name="email" type="email" placeholder="jane@example.com" required>
                        </div>
                    </div>

                    <div class="gzn-form-group">
                        <label for="subject">Subject</label>
                        <select class="gzn-select" id="subject" name="subject">
                            <option value="general">General Inquiry</option>
                            <option value="tip">Story Tip</option>
                            <option value="correction">Correction</option>
                            <option value="partnership">Partnership</option>
                            <option value="support">Technical Support</option>
                        </select>
                    </div>

                    <div class="gzn-form-group">
                        <label for="message">Message</label>
                        <textarea class="gzn-textarea" id="message" name="message" placeholder="Tell us what you need..." required></textarea>
                    </div>

                    <button class="gzn-button" type="submit">Send Message</button>
                </form>
            </div>

            <aside class="gzn-contact-aside">
                <section class="gzn-aside-box">
                    <h3>Newsroom Hours</h3>
                    <p>Monday to Friday: 9:00 AM to 6:00 PM ET</p>
                </section>

                <section class="gzn-aside-box">
                    <h3>Response Time</h3>
                    <p>Most messages receive a response within 24 to 48 hours on business days.</p>
                </section>

                <section class="gzn-aside-box">
                    <h3>Press and Legal</h3>
                    <p>For legal or rights requests, contact <a href="mailto:legal@genznewz.com">legal@genznewz.com</a> and include relevant URLs.</p>
                </section>
            </aside>
        </div>
    </article>
</section>

@include('theme::partials.static-page-styles')
