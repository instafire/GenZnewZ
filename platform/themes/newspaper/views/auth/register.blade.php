@php
    use Theme\Newspaper\Models\Member;
    
    $errors = session('errors', new \Illuminate\Support\MessageBag());
    $success = session('success');
@endphp

<section class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1 class="auth-title">{{ __('Create Account') }}</h1>
                <p class="auth-subtitle">{{ __('Join GenZ NewZ and start commenting!') }}</p>
            </div>

            @if ($success)
                <div class="alert alert-success">
                    {{ $success }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ url('/register') }}" method="POST" class="auth-form">
                @csrf
                
                <div class="form-group">
                    <label for="name">{{ __('Full Name') }}</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required 
                           placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="username">{{ __('Username') }}</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" required 
                           placeholder="Choose a username">
                    <small class="form-help">{{ __('This will be visible when you comment') }}</small>
                </div>

                <div class="form-group">
                    <label for="email">{{ __('Email Address') }}</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required 
                           placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="password">{{ __('Password') }}</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Create a password" minlength="8">
                    <small class="form-help">{{ __('Must be at least 8 characters') }}</small>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required 
                           placeholder="Confirm your password">
                </div>

                <div class="form-group form-checkbox">
                    <label>
                        <input type="checkbox" name="terms" required>
                        {{ __('I agree to the') }} <a href="{{ url('/terms-of-service') }}" target="_blank">{{ __('Terms of Use') }}</a> {{ __('and') }} <a href="{{ url('/privacy-policy') }}" target="_blank">{{ __('Privacy Policy') }}</a>
                    </label>
                </div>

                <button type="submit" class="auth-btn auth-btn-primary">
                    {{ __('Create Account') }}
                </button>
            </form>

            <div class="auth-footer">
                <p>{{ __('Already have an account?') }} <a href="{{ url('/login') }}">{{ __('Log In') }}</a></p>
            </div>
        </div>
    </div>
</section>

<style>
.auth-page {
    min-height: calc(100vh - 200px);
    padding: 60px 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.auth-container {
    max-width: 480px;
    margin: 0 auto;
}

.auth-box {
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 8px;
    background: linear-gradient(135deg, #FF006E 0%, #FB5607 50%, #FFBE0B 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.auth-subtitle {
    color: #666;
    font-size: 1rem;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert ul {
    margin: 0;
    padding-left: 20px;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-weight: 600;
    font-size: 0.9rem;
    color: #333;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"] {
    padding: 12px 16px;
    border: 2px solid #e2e2e2;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-group input:focus {
    outline: none;
    border-color: #326891;
}

.form-help {
    font-size: 0.8rem;
    color: #666;
}

.form-checkbox {
    flex-direction: row;
    align-items: flex-start;
    gap: 10px;
}

.form-checkbox input {
    margin-top: 3px;
}

.form-checkbox label {
    font-weight: 400;
    font-size: 0.9rem;
}

.form-checkbox a {
    color: #326891;
    text-decoration: none;
}

.form-checkbox a:hover {
    text-decoration: underline;
}

.auth-btn {
    padding: 14px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.auth-btn-primary {
    background: linear-gradient(135deg, #FF006E 0%, #FB5607 50%, #FFBE0B 100%);
    color: white;
}

.auth-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 0, 110, 0.3);
}

.auth-footer {
    text-align: center;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e2e2e2;
}

.auth-footer a {
    color: #326891;
    text-decoration: none;
    font-weight: 600;
}

.auth-footer a:hover {
    text-decoration: underline;
}

@media (max-width: 480px) {
    .auth-box {
        padding: 24px;
    }
    
    .auth-title {
        font-size: 1.5rem;
    }
}
</style>
