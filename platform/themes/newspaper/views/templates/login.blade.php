@php
    $errors = session('errors', new \Illuminate\Support\MessageBag());
@endphp

<section class="auth-page newspaper-auth">
    <div class="auth-container">
        <div class="auth-newspaper">
            <!-- Newspaper Header -->
            <div class="newspaper-masthead">
                <div class="masthead-date">{{ now()->format('l, F j, Y') }}</div>
                <div class="masthead-logo">
                    <a href="{{ url('/') }}">
                        <img src="{{ url('storage/logo.png') }}" alt="GenZ NewZ" class="masthead-img">
                    </a>
                </div>
                <div class="masthead-tagline">Breaking News for the Next Generation</div>
            </div>

            <!-- Newspaper Divider -->
            <div class="newspaper-divider">
                <span class="divider-line"></span>
                <span class="divider-text">MEMBER LOGIN</span>
                <span class="divider-line"></span>
            </div>

            <!-- Auth Content -->
            <div class="auth-content">
                @if ($errors->any())
                    <div class="alert alert-error">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ url('/member-login') }}" method="POST" class="auth-form">
                    @csrf
                    
                    <div class="form-group">
                        <label for="email">{{ __('Email Address') }}</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required 
                               placeholder="your@email.com" class="newspaper-input">
                    </div>

                    <div class="form-group">
                        <label for="password">{{ __('Password') }}</label>
                        <input type="password" id="password" name="password" required 
                               placeholder="••••••••" class="newspaper-input">
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" value="1">
                            <span class="checkmark"></span>
                            {{ __('Keep me signed in') }}
                        </label>
                        <a href="{{ url('/forgot-password') }}" class="forgot-link">{{ __('Forgot password?') }}</a>
                    </div>

                    <button type="submit" class="newspaper-btn newspaper-btn-primary">
                        <span class="btn-text">{{ __('Sign In') }}</span>
                        <span class="btn-icon">→</span>
                    </button>
                </form>

                <div class="auth-footer">
                    <div class="footer-divider">
                        <span></span>
                        <p>{{ __('New to GenZ NewZ?') }}</p>
                        <span></span>
                    </div>
                    <a href="{{ url('/register') }}" class="newspaper-btn newspaper-btn-secondary">
                        {{ __('Create Free Account') }}
                    </a>
                </div>
            </div>

            <!-- Newspaper Footer -->
            <div class="newspaper-footer">
                <p>&copy; {{ now()->year }} GenZ NewZ. All rights reserved.</p>
                <p class="footer-links">
                    <a href="{{ url('/terms-of-service') }}">Terms</a> • 
                    <a href="{{ url('/privacy-policy') }}">Privacy</a> • 
                    <a href="{{ url('/contact') }}">Contact</a>
                </p>
            </div>
        </div>
    </div>
</section>

<style>
/* Newspaper Theme - Authentication Pages */
.auth-page.newspaper-auth {
    min-height: 100vh;
    padding: 40px 20px;
    background: #f4f4f0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.auth-container {
    max-width: 520px;
    margin: 0 auto;
}

.auth-newspaper {
    background: #fff;
    border: 1px solid #1a1a1a;
    box-shadow: 
        0 0 0 1px #1a1a1a,
        4px 4px 0 0 #1a1a1a,
        8px 8px 0 0 rgba(26, 26, 26, 0.1);
    padding: 40px;
}

/* Masthead */
.newspaper-masthead {
    text-align: center;
    margin-bottom: 30px;
}

.masthead-date {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #666;
    margin-bottom: 15px;
    font-weight: 500;
}

.masthead-logo {
    margin-bottom: 10px;
}

.masthead-img {
    max-width: 200px;
    height: auto;
}

.masthead-tagline {
    font-size: 0.85rem;
    color: #666;
    font-style: italic;
    font-family: Georgia, serif;
}

/* Divider */
.newspaper-divider {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
}

.divider-line {
    flex: 1;
    height: 1px;
    background: #1a1a1a;
}

.divider-text {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: #1a1a1a;
    white-space: nowrap;
}

/* Form */
.auth-content {
    margin-bottom: 30px;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #1a1a1a;
}

.newspaper-input {
    padding: 14px 16px;
    border: 2px solid #1a1a1a;
    background: #fff;
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.2s;
    outline: none;
}

.newspaper-input:focus {
    background: #f9f9f9;
    box-shadow: 3px 3px 0 0 #1a1a1a;
}

.newspaper-input::placeholder {
    color: #999;
}

/* Options */
.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 0.9rem;
    color: #444;
}

.checkbox-label input {
    display: none;
}

.checkmark {
    width: 18px;
    height: 18px;
    border: 2px solid #1a1a1a;
    position: relative;
    transition: all 0.2s;
}

.checkbox-label input:checked + .checkmark {
    background: #1a1a1a;
}

.checkbox-label input:checked + .checkmark::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #fff;
    font-size: 12px;
    font-weight: bold;
}

.forgot-link {
    color: #326891;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
}

.forgot-link:hover {
    text-decoration: underline;
}

/* Buttons */
.newspaper-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 24px;
    font-size: 0.9rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    border: 2px solid #1a1a1a;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    font-family: inherit;
}

.newspaper-btn-primary {
    background: #1a1a1a;
    color: #fff;
    width: 100%;
}

.newspaper-btn-primary:hover {
    background: #333;
    transform: translateY(-2px);
    box-shadow: 4px 4px 0 0 rgba(26, 26, 26, 0.2);
}

.newspaper-btn-secondary {
    background: #fff;
    color: #1a1a1a;
    width: 100%;
}

.newspaper-btn-secondary:hover {
    background: #f4f4f0;
    transform: translateY(-2px);
    box-shadow: 4px 4px 0 0 rgba(26, 26, 26, 0.1);
}

.btn-icon {
    transition: transform 0.2s;
}

.newspaper-btn:hover .btn-icon {
    transform: translateX(4px);
}

/* Alert */
.alert {
    padding: 14px 16px;
    border: 2px solid;
    margin-bottom: 20px;
    font-size: 0.9rem;
}

.alert-error {
    background: #fff5f5;
    border-color: #c53030;
    color: #c53030;
}

.alert ul {
    margin: 0;
    padding-left: 20px;
}

/* Footer */
.auth-footer {
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #ddd;
}

.footer-divider {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.footer-divider span {
    flex: 1;
    height: 1px;
    background: #ddd;
}

.footer-divider p {
    font-size: 0.85rem;
    color: #666;
    white-space: nowrap;
}

/* Newspaper Footer */
.newspaper-footer {
    text-align: center;
    padding-top: 30px;
    border-top: 2px solid #1a1a1a;
    margin-top: 30px;
}

.newspaper-footer p {
    font-size: 0.8rem;
    color: #666;
    margin: 5px 0;
}

.footer-links {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.footer-links a {
    color: #326891;
    text-decoration: none;
}

.footer-links a:hover {
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 480px) {
    .auth-page.newspaper-auth {
        padding: 20px 15px;
        background: #fff;
    }
    
    .auth-newspaper {
        padding: 25px;
        box-shadow: none;
        border: none;
    }
    
    .masthead-img {
        max-width: 150px;
    }
    
    .form-options {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
