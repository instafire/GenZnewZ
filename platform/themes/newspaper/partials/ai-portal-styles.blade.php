<style>
/* AI Reporter portal — GenZ NewZ newspaper theme (restyled 2026-10-01).
   Same layout and class names as before; visual tokens mapped to the theme's
   --gzn-* variables so light AND dark mode follow the main site automatically.
   Layout structure intentionally untouched: visual restyle only. */
.ai-portal-page {
    --ai-panel: var(--gzn-surface);
    --ai-panel-soft: var(--gzn-surface-muted);
    --ai-line: var(--gzn-border);
    --ai-text: var(--gzn-ink);
    --ai-muted: var(--gzn-muted);
    --ai-link: #326891;
    --ai-kicker: #b45309;
    --ai-shadow: var(--gzn-shadow);
    position: relative;
    padding: 20px 16px 88px;
    color: var(--ai-text);
    font-family: var(--gzn-font-body);
}

body.dark-mode .ai-portal-page {
    --ai-link: #8fc3e8;
    --ai-kicker: var(--gzn-accent);
}

.ai-portal-shell {
    position: relative;
    z-index: 1;
    max-width: 1240px;
    margin: 0 auto;
}

.ai-portal-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 16px 18px;
    margin-bottom: 18px;
    border-radius: var(--gzn-radius);
    border: 1px solid var(--ai-line);
    background: var(--ai-panel);
    box-shadow: var(--ai-shadow);
}

.ai-nav-brand {
    display: grid;
    gap: 4px;
}

.ai-nav-brand strong {
    font-family: var(--gzn-font-display);
    font-size: 1.05rem;
    letter-spacing: 0.01em;
    color: var(--ai-text);
}

.ai-nav-brand span {
    color: var(--ai-muted);
    font-size: 0.84rem;
}

.ai-nav-links {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
}

.ai-nav-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 9px 14px;
    border-radius: 999px;
    border: 1px solid var(--ai-line);
    background: transparent;
    color: var(--ai-text);
    text-decoration: none;
    font-family: var(--gzn-font-mono);
    font-size: 0.83rem;
    transition: 0.18s ease;
}

.ai-nav-link:hover {
    border-color: var(--gzn-accent);
    text-decoration: none;
}

.ai-nav-link.is-active {
    color: #101010;
    background: var(--gzn-accent);
    border-color: var(--gzn-accent);
    font-weight: 700;
}

.ai-hero,
.ai-panel {
    animation: aiPortalRise 0.55s ease both;
}

.ai-hero {
    display: grid;
    grid-template-columns: 1.45fr 0.95fr;
    gap: 18px;
    padding: 30px;
    border-radius: var(--gzn-radius);
    border: 1px solid var(--ai-line);
    border-top: 4px solid var(--gzn-accent);
    background: var(--ai-panel);
    box-shadow: var(--ai-shadow);
}

.ai-kicker {
    margin: 0 0 10px;
    color: var(--ai-kicker);
    font-family: var(--gzn-font-mono);
    font-size: 0.79rem;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-weight: 700;
}

.ai-hero h1 {
    margin: 0 0 12px;
    font-family: var(--gzn-font-display);
    font-size: clamp(2rem, 4.1vw, 3.4rem);
    line-height: 1.02;
    letter-spacing: -0.02em;
    color: var(--ai-text);
}

.ai-lede,
.ai-note,
.ai-muted,
.ai-panel p,
.ai-panel li {
    color: var(--ai-muted);
}

.ai-lede {
    max-width: 760px;
    margin: 0;
    font-size: 1.02rem;
    line-height: 1.65;
}

.ai-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
}

.ai-chip,
.ai-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 11px;
    border-radius: 999px;
    border: 1px solid var(--ai-line);
    background: var(--ai-panel-soft);
    color: var(--ai-text);
    font-family: var(--gzn-font-mono);
    font-size: 0.8rem;
}

.ai-chip.ok,
.ai-pill.ok {
    border-color: rgba(22, 101, 52, 0.4);
    background: rgba(22, 101, 52, 0.08);
    color: #166534;
}

.ai-chip.warn,
.ai-pill.warn {
    border-color: rgba(180, 83, 9, 0.4);
    background: rgba(180, 83, 9, 0.08);
    color: #92400e;
}

.ai-chip.info,
.ai-pill.info {
    border-color: rgba(50, 104, 145, 0.4);
    background: rgba(50, 104, 145, 0.08);
    color: #326891;
}

.ai-chip.danger,
.ai-pill.danger {
    border-color: rgba(185, 28, 28, 0.4);
    background: rgba(185, 28, 28, 0.08);
    color: #b91c1c;
}

body.dark-mode .ai-chip.ok,
body.dark-mode .ai-pill.ok {
    border-color: rgba(149, 235, 117, 0.35);
    background: rgba(149, 235, 117, 0.1);
    color: #a6ef88;
}

body.dark-mode .ai-chip.warn,
body.dark-mode .ai-pill.warn {
    border-color: rgba(255, 191, 71, 0.4);
    background: rgba(255, 191, 71, 0.1);
    color: #ffd98a;
}

body.dark-mode .ai-chip.info,
body.dark-mode .ai-pill.info {
    border-color: rgba(143, 195, 232, 0.35);
    background: rgba(143, 195, 232, 0.1);
    color: #8fc3e8;
}

body.dark-mode .ai-chip.danger,
body.dark-mode .ai-pill.danger {
    border-color: rgba(255, 143, 107, 0.4);
    background: rgba(255, 143, 107, 0.12);
    color: #ffbba7;
}

.ai-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 20px;
}

.ai-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 18px;
    border-radius: 999px;
    border: 1px solid transparent;
    font-weight: 700;
    font-family: var(--gzn-font-body);
    text-decoration: none;
    transition: 0.18s ease;
}

.ai-btn:hover {
    transform: translateY(-1px);
    text-decoration: none;
}

.ai-btn-primary {
    color: #101010;
    background: var(--gzn-accent);
    border-color: var(--gzn-accent);
}

.ai-btn-secondary {
    color: var(--ai-text);
    border-color: var(--ai-line);
    background: var(--ai-panel);
}

.ai-btn-secondary:hover {
    border-color: var(--gzn-accent);
}

.ai-btn-danger {
    color: #b91c1c;
    border-color: rgba(185, 28, 28, 0.35);
    background: rgba(185, 28, 28, 0.06);
}

body.dark-mode .ai-btn-danger {
    color: #ffbba7;
    border-color: rgba(255, 143, 107, 0.35);
    background: rgba(255, 143, 107, 0.1);
}

.ai-btn-block {
    width: 100%;
}

.ai-stat-stack {
    display: grid;
    gap: 10px;
    align-content: start;
}

.ai-stat-card {
    padding: 14px 15px;
    border-radius: 12px;
    border: 1px solid var(--ai-line);
    background: var(--ai-panel-soft);
}

.ai-stat-label {
    display: block;
    margin-bottom: 6px;
    color: var(--ai-muted);
    font-family: var(--gzn-font-mono);
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.ai-stat-value {
    display: block;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.4;
    color: var(--ai-text);
}

.ai-stat-note {
    display: block;
    margin-top: 4px;
    color: var(--ai-muted);
    font-size: 0.83rem;
}

.ai-grid {
    display: grid;
    gap: 16px;
    margin-top: 16px;
}

.ai-grid-main {
    grid-template-columns: 1.35fr 1fr;
}

.ai-grid-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.ai-grid-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.ai-panel {
    padding: 22px;
    border-radius: var(--gzn-radius);
    border: 1px solid var(--ai-line);
    background: var(--ai-panel);
    box-shadow: var(--ai-shadow);
    color: var(--ai-text);
}

.ai-panel.soft {
    background: var(--ai-panel-soft);
    box-shadow: none;
}

.ai-panel.dark {
    background: var(--ai-panel);
    box-shadow: var(--ai-shadow);
}

.ai-section-head {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--ai-line);
}

.ai-panel h2,
.ai-panel h3 {
    margin: 0;
    color: var(--ai-text);
    font-family: var(--gzn-font-display);
}

.ai-panel h2 {
    font-size: 1.35rem;
}

.ai-panel h3 {
    font-size: 1rem;
}

.ai-note,
.ai-muted {
    font-size: 0.92rem;
    line-height: 1.6;
}

.ai-flow {
    display: grid;
    gap: 12px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.ai-flow li {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 12px;
    align-items: start;
    padding: 13px 14px;
    border-radius: 12px;
    border: 1px solid var(--ai-line);
    background: var(--ai-panel-soft);
}

.ai-flow-index {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: var(--gzn-accent);
    color: #101010;
    font-family: var(--gzn-font-mono);
    font-size: 0.82rem;
    font-weight: 700;
}

.ai-flow-content strong {
    display: block;
    margin-bottom: 4px;
    font-size: 0.97rem;
    color: var(--ai-text);
}

.ai-flow-content p {
    margin: 0;
}

.ai-pill-list,
.ai-list {
    display: grid;
    gap: 10px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.ai-list li {
    padding: 11px 12px;
    border-radius: 12px;
    border: 1px solid var(--ai-line);
    background: var(--ai-panel-soft);
    color: var(--ai-muted);
}

.ai-list li strong {
    display: block;
    margin-bottom: 4px;
    color: var(--ai-text);
}

.ai-list li span {
    display: block;
}

.ai-inline-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.ai-table {
    display: grid;
    gap: 10px;
}

.ai-table-row {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 12px;
    align-items: start;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid var(--ai-line);
    background: var(--ai-panel-soft);
}

.ai-method {
    min-width: 58px;
    padding: 6px 8px;
    border-radius: 8px;
    text-align: center;
    font-family: var(--gzn-font-mono);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.ai-method.post {
    color: #16120c;
    background: #ffd06a;
}

.ai-method.get {
    color: #071214;
    background: #7ce8f4;
}

.ai-method.patch {
    color: #0f1112;
    background: #a6ef88;
}

.ai-method.system {
    color: #9a3412;
    background: #ffedd5;
    border: 1px solid rgba(180, 83, 9, 0.3);
}

body.dark-mode .ai-method.system {
    color: #ffe2d8;
    background: rgba(255, 143, 107, 0.16);
    border: 1px solid rgba(255, 143, 107, 0.3);
}

.ai-table-row code,
.ai-code {
    display: inline-block;
    color: var(--ai-text);
    font-family: var(--gzn-font-mono);
    font-size: 0.83rem;
    word-break: break-word;
    background: var(--ai-panel-soft);
    padding: 2px 6px;
    border-radius: 6px;
    border: 1px solid var(--ai-line);
}

.ai-table-row strong {
    display: block;
    margin-bottom: 5px;
    color: var(--ai-text);
}

.ai-doc {
    border-radius: 12px;
    border: 1px solid var(--ai-line);
    background: var(--ai-panel);
    overflow: hidden;
}

.ai-doc + .ai-doc {
    margin-top: 12px;
}

.ai-doc summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    cursor: pointer;
    list-style: none;
    font-weight: 700;
    color: var(--ai-text);
    font-family: var(--gzn-font-display);
}

.ai-doc summary::-webkit-details-marker {
    display: none;
}

.ai-doc-meta {
    color: var(--ai-muted);
    font-family: var(--gzn-font-mono);
    font-size: 0.79rem;
    white-space: nowrap;
    flex-shrink: 0;
}

.ai-doc-body {
    padding: 0 16px 16px;
}

.ai-doc-pre {
    margin: 12px 0 0;
    max-height: 500px;
    overflow: auto;
    padding: 14px;
    border-radius: 12px;
    border: 1px solid var(--ai-line);
    background: var(--ai-panel-soft);
    color: var(--ai-text);
    white-space: pre-wrap;
    word-break: break-word;
    font-family: var(--gzn-font-mono);
    font-size: 0.83rem;
    line-height: 1.6;
}

.ai-scroll-block {
    max-height: 780px;
    overflow: auto;
    padding-right: 6px;
}

.ai-alert {
    margin: 0 0 14px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid transparent;
    font-weight: 600;
}

.ai-alert.error {
    color: #b91c1c;
    border-color: rgba(185, 28, 28, 0.3);
    background: rgba(185, 28, 28, 0.07);
}

.ai-alert.success {
    color: #166534;
    border-color: rgba(22, 101, 52, 0.3);
    background: rgba(22, 101, 52, 0.07);
}

body.dark-mode .ai-alert.error {
    color: #ffbba7;
    border-color: rgba(255, 143, 107, 0.3);
    background: rgba(255, 143, 107, 0.12);
}

body.dark-mode .ai-alert.success {
    color: #a6ef88;
    border-color: rgba(149, 235, 117, 0.3);
    background: rgba(149, 235, 117, 0.12);
}

.ai-form {
    display: grid;
    gap: 14px;
}

.ai-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.ai-field {
    display: grid;
    gap: 8px;
}

.ai-field label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 0.94rem;
    color: var(--ai-text);
}

.ai-field-tag {
    padding: 3px 7px;
    border-radius: 999px;
    background: var(--ai-panel-soft);
    border: 1px solid var(--ai-line);
    color: var(--ai-muted);
    font-family: var(--gzn-font-mono);
    font-size: 0.73rem;
    font-weight: 600;
}

.ai-field input,
.ai-field textarea {
    width: 100%;
    padding: 14px 15px;
    border-radius: 12px;
    border: 1px solid var(--ai-line);
    background: var(--ai-panel);
    color: var(--ai-text);
    font-size: 0.96rem;
    font-family: var(--gzn-font-body);
}

.ai-field textarea {
    min-height: 148px;
    resize: vertical;
}

.ai-field input::placeholder,
.ai-field textarea::placeholder {
    color: var(--ai-muted);
}

.ai-field input:focus,
.ai-field textarea:focus {
    outline: none;
    border-color: var(--gzn-accent);
    box-shadow: 0 0 0 3px rgba(255, 191, 71, 0.25);
}

.ai-help,
.ai-error {
    font-size: 0.83rem;
    line-height: 1.55;
}

.ai-help {
    color: var(--ai-muted);
}

.ai-error {
    color: #b91c1c;
}

body.dark-mode .ai-error {
    color: #ffbba7;
}

.ai-checkboxes {
    display: grid;
    gap: 10px;
}

.ai-checkbox {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 12px;
    align-items: start;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid var(--ai-line);
    background: var(--ai-panel-soft);
    color: var(--ai-muted);
}

.ai-checkbox input {
    margin-top: 4px;
    accent-color: var(--gzn-accent);
}

.ai-kv {
    display: grid;
    gap: 10px;
}

.ai-kv-row {
    padding: 12px 0 0;
    border-top: 1px solid var(--ai-line);
}

.ai-kv-row:first-child {
    padding-top: 0;
    border-top: 0;
}

.ai-kv-label {
    display: block;
    color: var(--ai-muted);
    font-family: var(--gzn-font-mono);
    font-size: 0.77rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.ai-kv-value {
    display: block;
    margin-top: 6px;
    font-size: 0.96rem;
    line-height: 1.6;
    color: var(--ai-text);
}

.ai-kv-value code,
.ai-token-box {
    display: block;
    padding: 11px 12px;
    border-radius: 12px;
    border: 1px solid var(--ai-line);
    background: var(--ai-panel-soft);
    color: var(--ai-text);
    font-family: var(--gzn-font-mono);
    font-size: 0.84rem;
    overflow-wrap: anywhere;
}

.ai-copy-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 8px;
    align-items: stretch;
}

.ai-post-list {
    display: grid;
    gap: 10px;
    min-height: 220px;
}

.ai-post-item {
    padding: 13px 14px;
    border-radius: 12px;
    border: 1px solid var(--ai-line);
    background: var(--ai-panel-soft);
}

.ai-post-item a,
.ai-panel a:not(.ai-btn):not(.ai-nav-link) {
    color: var(--ai-link);
    text-decoration: none;
}

.ai-post-item a:hover,
.ai-panel a:not(.ai-btn):not(.ai-nav-link):hover {
    text-decoration: underline;
}

.ai-post-meta {
    margin-top: 6px;
    color: var(--ai-muted);
    font-size: 0.84rem;
}

.ai-empty {
    color: var(--ai-muted);
    font-size: 0.93rem;
}

.ai-profile-box {
    padding: 14px;
    border-radius: 12px;
    border: 1px solid var(--ai-line);
    background: var(--ai-panel-soft);
    color: var(--ai-text);
    line-height: 1.7;
}

.ai-center {
    text-align: center;
}

.ai-mono {
    font-family: var(--gzn-font-mono);
}

@keyframes aiPortalRise {
    0% {
        opacity: 0;
        transform: translateY(12px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 1060px) {
    .ai-hero,
    .ai-grid-main,
    .ai-grid-3 {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 840px) {
    .ai-portal-nav,
    .ai-form-grid,
    .ai-grid-2 {
        grid-template-columns: 1fr;
    }

    .ai-portal-nav {
        display: grid;
    }

    .ai-nav-links {
        justify-content: flex-start;
    }
}

@media (max-width: 680px) {
    .ai-portal-page {
        padding: 14px 12px 72px;
    }

    .ai-portal-nav,
    .ai-hero,
    .ai-panel {
        padding: 18px;
        border-radius: 14px;
    }

    .ai-actions,
    .ai-copy-row {
        grid-template-columns: 1fr;
    }

    .ai-copy-row {
        display: grid;
    }

    .ai-btn,
    .ai-nav-link {
        width: 100%;
    }

    .ai-doc summary {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}
</style>
