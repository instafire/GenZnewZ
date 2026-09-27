<style>
.ai-portal-page {
    --ai-bg-1: #080c10;
    --ai-bg-2: #0f151b;
    --ai-bg-3: #111a22;
    --ai-panel: rgba(12, 18, 24, 0.88);
    --ai-panel-soft: rgba(255, 255, 255, 0.04);
    --ai-line: rgba(255, 255, 255, 0.1);
    --ai-line-strong: rgba(255, 191, 71, 0.22);
    --ai-text: #f6f1e8;
    --ai-muted: #b9b1a2;
    --ai-accent: #ffbf47;
    --ai-accent-2: #95eb75;
    --ai-info: #67d7e7;
    --ai-danger: #ff8f6b;
    --ai-shadow: 0 28px 80px rgba(0, 0, 0, 0.34);
    position: relative;
    overflow: hidden;
    padding: 20px 16px 88px;
    color: var(--ai-text);
    background:
        radial-gradient(circle at top left, rgba(255, 191, 71, 0.16), transparent 34%),
        radial-gradient(circle at top right, rgba(103, 215, 231, 0.11), transparent 30%),
        linear-gradient(180deg, var(--ai-bg-1) 0%, var(--ai-bg-2) 48%, var(--ai-bg-3) 100%);
    font-family: 'IBM Plex Sans', 'Space Grotesk', sans-serif;
}

.ai-portal-page::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: 0.18;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.9), transparent 96%);
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
    border-radius: 20px;
    border: 1px solid var(--ai-line);
    background: rgba(8, 12, 16, 0.76);
    backdrop-filter: blur(18px);
    box-shadow: 0 14px 40px rgba(0, 0, 0, 0.22);
}

.ai-nav-brand {
    display: grid;
    gap: 4px;
}

.ai-nav-brand strong {
    font-family: 'Space Grotesk', 'IBM Plex Sans', sans-serif;
    font-size: 1.05rem;
    letter-spacing: 0.01em;
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
    padding: 9px 12px;
    border-radius: 999px;
    border: 1px solid var(--ai-line);
    background: rgba(255, 255, 255, 0.03);
    color: var(--ai-text);
    text-decoration: none;
    font-family: 'IBM Plex Mono', 'JetBrains Mono', monospace;
    font-size: 0.83rem;
    transition: 0.18s ease;
}

.ai-nav-link:hover,
.ai-nav-link.is-active {
    transform: translateY(-1px);
    border-color: rgba(255, 191, 71, 0.38);
}

.ai-nav-link.is-active {
    color: #101010;
    background: linear-gradient(135deg, var(--ai-accent) 0%, #ffe08d 100%);
    box-shadow: 0 10px 24px rgba(255, 191, 71, 0.24);
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
    border-radius: 28px;
    border: 1px solid var(--ai-line-strong);
    background:
        linear-gradient(135deg, rgba(255, 191, 71, 0.09) 0%, rgba(103, 215, 231, 0.07) 42%, rgba(8, 12, 16, 0.92) 100%);
    box-shadow: var(--ai-shadow);
}

.ai-kicker {
    margin: 0 0 10px;
    color: var(--ai-accent);
    font-family: 'IBM Plex Mono', 'JetBrains Mono', monospace;
    font-size: 0.79rem;
    text-transform: uppercase;
    letter-spacing: 0.18em;
}

.ai-hero h1 {
    margin: 0 0 12px;
    font-family: 'Space Grotesk', 'IBM Plex Sans', sans-serif;
    font-size: clamp(2rem, 4.1vw, 3.4rem);
    line-height: 0.98;
    letter-spacing: -0.03em;
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
    background: rgba(255, 255, 255, 0.04);
    color: var(--ai-text);
    font-family: 'IBM Plex Mono', 'JetBrains Mono', monospace;
    font-size: 0.8rem;
}

.ai-chip.ok,
.ai-pill.ok {
    border-color: rgba(149, 235, 117, 0.28);
    background: rgba(149, 235, 117, 0.09);
}

.ai-chip.warn,
.ai-pill.warn {
    border-color: rgba(255, 191, 71, 0.34);
    background: rgba(255, 191, 71, 0.1);
}

.ai-chip.info,
.ai-pill.info {
    border-color: rgba(103, 215, 231, 0.3);
    background: rgba(103, 215, 231, 0.09);
}

.ai-chip.danger,
.ai-pill.danger {
    border-color: rgba(255, 143, 107, 0.34);
    background: rgba(255, 143, 107, 0.11);
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
    padding: 12px 16px;
    border-radius: 14px;
    border: 1px solid transparent;
    font-weight: 700;
    text-decoration: none;
    transition: 0.18s ease;
}

.ai-btn:hover {
    transform: translateY(-1px);
}

.ai-btn-primary {
    color: #121212;
    background: linear-gradient(135deg, var(--ai-accent) 0%, #ffe09a 100%);
    box-shadow: 0 12px 28px rgba(255, 191, 71, 0.22);
}

.ai-btn-secondary {
    color: var(--ai-text);
    border-color: var(--ai-line);
    background: rgba(255, 255, 255, 0.04);
}

.ai-btn-danger {
    color: #ffd7cb;
    border-color: rgba(255, 143, 107, 0.26);
    background: rgba(255, 143, 107, 0.11);
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
    border-radius: 18px;
    border: 1px solid var(--ai-line);
    background: rgba(255, 255, 255, 0.04);
}

.ai-stat-label {
    display: block;
    margin-bottom: 6px;
    color: var(--ai-muted);
    font-family: 'IBM Plex Mono', 'JetBrains Mono', monospace;
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.ai-stat-value {
    display: block;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.4;
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
    border-radius: 22px;
    border: 1px solid var(--ai-line);
    background: var(--ai-panel);
    backdrop-filter: blur(12px);
    box-shadow: 0 18px 48px rgba(0, 0, 0, 0.16);
}

.ai-panel.soft {
    background: rgba(255, 255, 255, 0.035);
}

.ai-panel.dark {
    background: linear-gradient(180deg, rgba(8, 12, 16, 0.96) 0%, rgba(11, 16, 22, 0.96) 100%);
}

.ai-section-head {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}

.ai-panel h2,
.ai-panel h3 {
    margin: 0;
    color: var(--ai-text);
    font-family: 'Space Grotesk', 'IBM Plex Sans', sans-serif;
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
    border-radius: 18px;
    border: 1px solid var(--ai-line);
    background: rgba(255, 255, 255, 0.03);
}

.ai-flow-index {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--ai-accent) 0%, #ffe4a8 100%);
    color: #111;
    font-family: 'IBM Plex Mono', 'JetBrains Mono', monospace;
    font-size: 0.82rem;
    font-weight: 700;
}

.ai-flow-content strong {
    display: block;
    margin-bottom: 4px;
    font-size: 0.97rem;
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
    border-radius: 16px;
    border: 1px solid var(--ai-line);
    background: rgba(255, 255, 255, 0.03);
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
    border-radius: 18px;
    border: 1px solid var(--ai-line);
    background: rgba(255, 255, 255, 0.03);
}

.ai-method {
    min-width: 58px;
    padding: 6px 8px;
    border-radius: 10px;
    text-align: center;
    font-family: 'IBM Plex Mono', 'JetBrains Mono', monospace;
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
    color: #ffe2d8;
    background: rgba(255, 143, 107, 0.16);
    border: 1px solid rgba(255, 143, 107, 0.2);
}

.ai-table-row code,
.ai-code {
    display: inline-block;
    color: #fdf4d6;
    font-family: 'IBM Plex Mono', 'JetBrains Mono', monospace;
    font-size: 0.83rem;
    word-break: break-word;
}

.ai-table-row strong {
    display: block;
    margin-bottom: 5px;
}

.ai-doc {
    border-radius: 18px;
    border: 1px solid var(--ai-line);
    background: rgba(6, 10, 14, 0.78);
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
}

.ai-doc summary::-webkit-details-marker {
    display: none;
}

.ai-doc-meta {
    color: var(--ai-muted);
    font-family: 'IBM Plex Mono', 'JetBrains Mono', monospace;
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
    border-radius: 16px;
    border: 1px solid rgba(103, 215, 231, 0.16);
    background: #070b0d;
    color: #d3eef2;
    white-space: pre-wrap;
    word-break: break-word;
    font-family: 'IBM Plex Mono', 'JetBrains Mono', monospace;
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
    border-radius: 16px;
    border: 1px solid transparent;
    font-weight: 600;
}

.ai-alert.error {
    color: #ffd7cb;
    border-color: rgba(255, 143, 107, 0.24);
    background: rgba(255, 143, 107, 0.12);
}

.ai-alert.success {
    color: #e7ffd6;
    border-color: rgba(149, 235, 117, 0.24);
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
}

.ai-field-tag {
    padding: 3px 7px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.06);
    color: var(--ai-muted);
    font-family: 'IBM Plex Mono', 'JetBrains Mono', monospace;
    font-size: 0.73rem;
    font-weight: 600;
}

.ai-field input,
.ai-field textarea {
    width: 100%;
    padding: 14px 15px;
    border-radius: 16px;
    border: 1px solid var(--ai-line);
    background: rgba(5, 9, 13, 0.8);
    color: var(--ai-text);
    font-size: 0.96rem;
    font-family: 'IBM Plex Sans', 'Space Grotesk', sans-serif;
}

.ai-field textarea {
    min-height: 148px;
    resize: vertical;
}

.ai-field input::placeholder,
.ai-field textarea::placeholder {
    color: rgba(185, 177, 162, 0.66);
}

.ai-field input:focus,
.ai-field textarea:focus {
    outline: none;
    border-color: rgba(255, 191, 71, 0.5);
    box-shadow: 0 0 0 3px rgba(255, 191, 71, 0.12);
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
    border-radius: 16px;
    border: 1px solid var(--ai-line);
    background: rgba(255, 255, 255, 0.03);
}

.ai-checkbox input {
    margin-top: 4px;
    accent-color: var(--ai-accent);
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
    font-family: 'IBM Plex Mono', 'JetBrains Mono', monospace;
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
    border-radius: 14px;
    border: 1px solid var(--ai-line);
    background: #070b0d;
    color: #f6f1e8;
    font-family: 'IBM Plex Mono', 'JetBrains Mono', monospace;
    font-size: 0.84rem;
    overflow-wrap: anywhere;
}

.ai-token-box {
    color: #fff1b8;
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
    border-radius: 16px;
    border: 1px solid var(--ai-line);
    background: rgba(255, 255, 255, 0.03);
}

.ai-post-item a,
.ai-panel a:not(.ai-btn):not(.ai-nav-link) {
    color: #9debf5;
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
    border-radius: 16px;
    border: 1px solid var(--ai-line);
    background: rgba(255, 255, 255, 0.03);
    color: var(--ai-text);
    line-height: 1.7;
}

.ai-center {
    text-align: center;
}

.ai-mono {
    font-family: 'IBM Plex Mono', 'JetBrains Mono', monospace;
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
        border-radius: 18px;
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
