<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'StockFlow')</title>
    <link rel="stylesheet" href="/css/app.css">
    <script>
        (function () {
            var theme = localStorage.getItem('sf-theme');
            if (theme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
        })();
    </script>
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar" id="sidebar">
        <div class="app-brand"><span class="logo">S</span> StockFlow</div>
        @php
            $can = fn ($key) => auth()->user()->hasPermission($key);
        @endphp
        <div class="nav-section">الرئيسية</div>
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">لوحة المتابعة</a>

        @if($can('sales.view') || $can('returns.view') || $can('payments.view'))
            <div class="nav-section">المبيعات</div>
            @can('sales.view')
                <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">فواتير البيع</a>
            @endcan
            @can('returns.view')
                <a class="nav-link {{ request()->routeIs('returns.*') ? 'active' : '' }}" href="{{ route('returns.index') }}">المرتجعات</a>
            @endcan
            @can('payments.view')
                <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">التحصيلات</a>
            @endcan
            @can('customers.view')
                <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">العملاء</a>
            @endcan
        @endif

        @if($can('purchases.view') || $can('inventory'))
            <div class="nav-section">المخزون</div>
            @can('purchases.view')
                <a class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}">المشتريات</a>
                <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">الموردون</a>
            @endcan
            @can('inventory.view')
                <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.balances') }}">أرصدة المخزون</a>
                <a class="nav-link {{ request()->routeIs('inventory.movements') ? 'active' : '' }}" href="{{ route('inventory.movements') }}">حركات المخزون</a>
                <a class="nav-link {{ request()->routeIs('transfers.*') ? 'active' : '' }}" href="{{ route('transfers.index') }}">التحويلات</a>
                <a class="nav-link {{ request()->routeIs('counts.*') ? 'active' : '' }}" href="{{ route('counts.index') }}">الجرد</a>
            @endcan
            @can('products.view')
                <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">الأصناف</a>
            @endcan
        @endif

        @if($can('reps.view'))
            <div class="nav-section">التوزيع</div>
            <a class="nav-link {{ request()->routeIs('reps.*') ? 'active' : '' }}" href="{{ route('reps.index') }}">المندوبون</a>
            <a class="nav-link {{ request()->routeIs('loadings.*') ? 'active' : '' }}" href="{{ route('loadings.index') }}">تحميل العهد</a>
            <a class="nav-link {{ request()->routeIs('settlements.*') ? 'active' : '' }}" href="{{ route('settlements.index') }}">التسويات</a>
            @can('expenses.view')
                <a class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">المصروفات</a>
            @endcan
        @endif

        @can('reports.view')
            <div class="nav-section">التقارير</div>
            <a class="nav-link {{ request()->routeIs('reports.sales') ? 'active' : '' }}" href="{{ route('reports.sales') }}">المبيعات والأرباح</a>
            <a class="nav-link {{ request()->routeIs('reports.products') ? 'active' : '' }}" href="{{ route('reports.products') }}">الأصناف</a>
            <a class="nav-link {{ request()->routeIs('reports.reps') ? 'active' : '' }}" href="{{ route('reports.reps') }}">المندوبون</a>
            <a class="nav-link {{ request()->routeIs('reports.aging') ? 'active' : '' }}" href="{{ route('reports.aging') }}">أعمار الديون</a>
            <a class="nav-link {{ request()->routeIs('reports.cheques') ? 'active' : '' }}" href="{{ route('reports.cheques') }}">الشيكات</a>
        @endcan

        @if($can('users.view') || $can('roles.view'))
            <div class="nav-section">الإدارة</div>
            @can('users.view')
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">المستخدمون</a>
            @endcan
            @can('roles.view')
                <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">الأدوار</a>
            @endcan
            @can('warehouses.view')
                <a class="nav-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}" href="{{ route('warehouses.index') }}">المخازن والعُهد</a>
            @endcan
            @can('settings.view')
                <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">الإعدادات</a>
            @endcan
        @endif
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <button class="btn btn-secondary btn-sm" onclick="document.getElementById('sidebar').classList.toggle('open')" style="display:none" id="menu-btn">☰</button>
            <span>{{ auth()->user()->company?->name }}</span>
            <div style="display:flex; align-items:center; gap:10px">
                <form method="POST" action="{{ route('locale.switch') }}">
                    @csrf
                    <input type="hidden" name="locale" value="{{ app()->getLocale() === 'ar' ? 'en' : 'ar' }}">
                    <button class="lang-toggle" type="submit">@lang('messages.language')</button>
                </form>
                <button class="theme-toggle" type="button" onclick="toggleTheme()" id="theme-btn">🌙</button>
                <span style="font-size:13px">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-secondary btn-sm" type="submit">@lang('messages.logout')</button>
                </form>
            </div>
        </header>
        <main class="app-content">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<script>
    if (window.innerWidth <= 860) document.getElementById('menu-btn').style.display = 'inline-flex';

    function toggleTheme() {
        var el = document.documentElement;
        var isDark = el.getAttribute('data-theme') === 'dark';
        if (isDark) {
            el.removeAttribute('data-theme');
            localStorage.setItem('sf-theme', 'light');
        } else {
            el.setAttribute('data-theme', 'dark');
            localStorage.setItem('sf-theme', 'dark');
        }
        document.getElementById('theme-btn').textContent = isDark ? '🌙' : '☀️';
    }

    if (document.documentElement.getAttribute('data-theme') === 'dark') {
        document.getElementById('theme-btn').textContent = '☀️';
    }
</script>
</body>
</html>
