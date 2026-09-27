@php
    $errors = session('errors', new \Illuminate\Support\MessageBag());
@endphp

<section class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1 class="auth-title">{{ __('Welcome Back') }}</h1>
                <p class="auth-subtitle">{{ __('Log in to comment and engage with the community') }}</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ url('/login') }}" method="POST" class="auth-form">
                @csrf
                
                <div class="form-group">
                    <label for="email">{{ __('Email Address') }}</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required 
                           placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="password">{{ __('Password') }}</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>

                <div class="form-group form-checkbox">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        {{ __('Remember me') }}
                    </label>
                    <a href="{{ url('/forgot-password') }}" class="forgot-link">{{ __('Forgot password?') }}</a>
                </div>

                <button type="submit" class="auth-btn auth-btn-primary">
                    {{ __('Log In') }}
                </button>
            </form>

            <div class="auth-footer">
                <p>{{ __('Don\'t have an account?') }} <a href="{{ url('/register') }}">{{ __('Sign Up') }}</a></p>
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

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
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

.form-checkbox {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
}

.form-checkbox label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 400;
    cursor: pointer;
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
    
    .form-checkbox {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>
