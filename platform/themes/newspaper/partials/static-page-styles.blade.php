<style>
.gzn-static-page {
    max-width: 980px;
    margin: 0 auto;
    padding: 34px 20px 72px;
}

.gzn-static-shell {
    background: #fff;
    border: 1px solid #dcdcdc;
    border-radius: 6px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
    overflow: hidden;
}

.gzn-static-header {
    padding: 36px 44px 28px;
    border-bottom: 1px solid #e4e4e4;
    text-align: center;
}

.gzn-static-kicker {
    display: inline-block;
    margin-bottom: 10px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 1.8px;
    text-transform: uppercase;
    color: #5f5f5f;
}

.gzn-static-title {
    margin: 0 0 10px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.9rem, 4.4vw, 2.5rem);
    font-weight: 700;
    line-height: 1.15;
    letter-spacing: -0.5px;
    color: #131313;
}

.gzn-static-subtitle {
    margin: 0 auto;
    max-width: 760px;
    color: #4d4d4d;
    font-size: 1.03rem;
    line-height: 1.68;
}

.gzn-static-updated {
    margin-top: 14px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.8rem;
    letter-spacing: 0.7px;
    text-transform: uppercase;
    color: #6f6f6f;
}

.gzn-static-body {
    padding: 34px 44px 42px;
    font-size: 1.02rem;
    line-height: 1.76;
    color: #212121;
}

.gzn-static-body h2 {
    margin: 28px 0 10px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.34rem;
    font-weight: 700;
    line-height: 1.3;
    color: #131313;
}

.gzn-static-body h3 {
    margin: 22px 0 8px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: #1b1b1b;
}

.gzn-static-body p {
    margin: 0 0 16px;
}

.gzn-static-body ul,
.gzn-static-body ol {
    margin: 0 0 16px;
    padding-left: 22px;
}

.gzn-static-body li {
    margin-bottom: 9px;
}

.gzn-static-body a {
    color: #1f5d84;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.gzn-static-body a:hover {
    color: #0f3550;
}

.gzn-static-body strong {
    color: #121212;
    font-weight: 700;
}

.gzn-contact-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(260px, 0.9fr);
    gap: 26px;
}

.gzn-contact-cards {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin: 18px 0 28px;
}

.gzn-contact-card {
    padding: 14px 16px;
    border: 1px solid #dedede;
    border-radius: 4px;
    background: #fafafa;
}

.gzn-contact-card h3 {
    margin: 0 0 6px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.96rem;
    font-weight: 700;
    letter-spacing: 0.2px;
    text-transform: uppercase;
    color: #232323;
}

.gzn-contact-card p {
    margin: 0;
    font-size: 0.96rem;
    line-height: 1.6;
}

.gzn-contact-form {
    margin-top: 6px;
    padding: 18px 18px 16px;
    border: 1px solid #dcdcdc;
    border-radius: 4px;
    background: #fcfcfc;
}

.gzn-form-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.gzn-form-group {
    margin-bottom: 12px;
}

.gzn-form-group label {
    display: block;
    margin-bottom: 6px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.84rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #444;
}

.gzn-input,
.gzn-select,
.gzn-textarea {
    width: 100%;
    border: 1px solid #bdbdbd;
    border-radius: 3px;
    padding: 10px 12px;
    font-size: 0.98rem;
    line-height: 1.4;
    background: #fff;
    color: #1d1d1d;
}

.gzn-input:focus,
.gzn-select:focus,
.gzn-textarea:focus {
    outline: none;
    border-color: #161616;
    box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.08);
}

.gzn-textarea {
    min-height: 130px;
    resize: vertical;
}

.gzn-button {
    border: 1px solid #171717;
    border-radius: 3px;
    padding: 10px 18px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.9px;
    text-transform: uppercase;
    color: #fff;
    background: #171717;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease;
}

.gzn-button:hover {
    background: #fff;
    color: #171717;
}

.gzn-contact-aside {
    display: grid;
    gap: 14px;
    align-content: start;
}

.gzn-aside-box {
    padding: 16px;
    border: 1px solid #dddddd;
    border-radius: 4px;
    background: #fafafa;
}

.gzn-aside-box h3 {
    margin: 0 0 8px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.35px;
    text-transform: uppercase;
    color: #232323;
}

.gzn-aside-box p {
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.6;
}

@media (max-width: 920px) {
    .gzn-static-page {
        padding: 24px 14px 58px;
    }

    .gzn-static-header {
        padding: 28px 22px 22px;
    }

    .gzn-static-body {
        padding: 24px 22px 30px;
    }

    .gzn-contact-layout {
        grid-template-columns: 1fr;
        gap: 18px;
    }
}

@media (max-width: 620px) {
    .gzn-contact-cards {
        grid-template-columns: 1fr;
    }

    .gzn-form-row {
        grid-template-columns: 1fr;
    }
}

body.dark-mode .gzn-static-shell {
    background: #181818;
    border-color: #343434;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
}

body.dark-mode .gzn-static-header {
    border-bottom-color: #343434;
}

body.dark-mode .gzn-static-kicker {
    color: #aaaaaa;
}

body.dark-mode .gzn-static-title {
    color: #efefef;
}

body.dark-mode .gzn-static-subtitle,
body.dark-mode .gzn-static-body,
body.dark-mode .gzn-static-body p {
    color: #cdcdcd;
}

body.dark-mode .gzn-static-updated {
    color: #a4a4a4;
}

body.dark-mode .gzn-static-body h2,
body.dark-mode .gzn-static-body h3,
body.dark-mode .gzn-static-body strong {
    color: #efefef;
}

body.dark-mode .gzn-static-body a {
    color: #7fb7dd;
}

body.dark-mode .gzn-static-body a:hover {
    color: #a8d4f1;
}

body.dark-mode .gzn-contact-card,
body.dark-mode .gzn-contact-form,
body.dark-mode .gzn-aside-box {
    background: #1f1f1f;
    border-color: #3a3a3a;
}

body.dark-mode .gzn-contact-card h3,
body.dark-mode .gzn-form-group label,
body.dark-mode .gzn-aside-box h3 {
    color: #f0f0f0;
}

body.dark-mode .gzn-contact-card p,
body.dark-mode .gzn-aside-box p {
    color: #cfcfcf;
}

body.dark-mode .gzn-input,
body.dark-mode .gzn-select,
body.dark-mode .gzn-textarea {
    background: #141414;
    border-color: #4b4b4b;
    color: #efefef;
}

body.dark-mode .gzn-input:focus,
body.dark-mode .gzn-select:focus,
body.dark-mode .gzn-textarea:focus {
    border-color: #9e9e9e;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.12);
}

body.dark-mode .gzn-button {
    background: #efefef;
    border-color: #efefef;
    color: #141414;
}

body.dark-mode .gzn-button:hover {
    background: #141414;
    color: #efefef;
}
</style>
