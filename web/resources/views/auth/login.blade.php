<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@lang('messages.login') — StockFlow</title>
    <link rel="stylesheet" href="/css/app.css">
    <script>
        (function () {
            var theme = localStorage.getItem('sf-theme');
            if (theme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
        })();
    </script>
</head>
<body>
<div class="login-page">
    <div class="login-toggles">
        <form method="POST" action="{{ route('locale.switch') }}">
            @csrf
            <input type="hidden" name="locale" value="{{ app()->getLocale() === 'ar' ? 'en' : 'ar' }}">
            <button class="lang-toggle" type="submit" title="Language">@lang('messages.language')</button>
        </form>
        <button class="theme-toggle" type="button" id="theme-btn" onclick="toggleTheme()">🌙</button>
    </div>
    <div class="login-card">
        <div class="login-logo">S</div>
        <div class="login-brand">StockFlow</div>
        <div class="login-sub">@lang('messages.login_sub')</div>

        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="field">
                <label for="phone">@lang('messages.phone')</label>
                <input class="input {{ $errors->has('phone') ? 'input-error' : '' }}" id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="01xxxxxxxxx" required autofocus dir="ltr" style="text-align:start">
                @error('phone')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password">@lang('messages.password')</label>
                <div class="password-wrap">
                    <input class="input {{ $errors->has('password') ? 'input-error' : '' }}" id="password" name="password" type="password" required autocomplete="current-password">
                    <button class="eye-btn" type="button" onclick="togglePassword()" aria-label="show password">👁</button>
                </div>
                @error('password')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="remember-row">
                <label>
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    @lang('messages.remember')
                </label>
            </div>

            <button class="btn btn-primary" type="submit" style="width:100%">@lang('messages.login')</button>
        </form>
    </div>
</div>
<script>
    function togglePassword() {
        var input = document.getElementById('password');
        input.type = input.type === 'password' ? 'text' : 'password';
    }

    function toggleTheme() {
        var el = document.documentElement;
        var isDark = el.getAttribute('data-theme') === 'dark';
        if (isDark) {
            el.removeAttribute('data-theme');
            localStorage.setItem('sf-theme', 'light');
            document.getElementById('theme-btn').textContent = '🌙';
        } else {
            el.setAttribute('data-theme', 'dark');
            localStorage.setItem('sf-theme', 'dark');
            document.getElementById('theme-btn').textContent = '☀️';
        }
    }

    if (document.documentElement.getAttribute('data-theme') === 'dark') {
        document.getElementById('theme-btn').textContent = '☀️';
    }
</script>
</body>
</html>
