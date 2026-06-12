<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'World Cup 2026') · WC2026</title>
    <meta name="description" content="@yield('meta_description', 'Your daily hub for the FIFA World Cup 2026 — fixtures, live scores, group standings and all 48 teams across the USA, Canada and Mexico.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('head')
</head>
<body>
    <header class="site-header">
        <div class="container nav">
            <a href="{{ route('home') }}" class="nav__brand">
                <span class="nav__brand-mark">26</span>
                <span class="nav__brand-text">World&nbsp;Cup<strong>2026</strong></span>
            </a>
            <button class="nav__toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="primary-nav" data-nav-toggle>
                <span></span><span></span><span></span>
            </button>
            <nav id="primary-nav" class="nav__links" data-nav-links>
                <a href="{{ route('home') }}" class="nav__link {{ request()->routeIs('home') ? 'nav__link--active' : '' }}">Home</a>
                <a href="{{ route('fixtures.index') }}" class="nav__link {{ request()->routeIs('fixtures.*') ? 'nav__link--active' : '' }}">Fixtures</a>
                <a href="{{ route('bracket.index') }}" class="nav__link {{ request()->routeIs('bracket.*') ? 'nav__link--active' : '' }}">Bracket</a>
                <a href="{{ route('standings.index') }}" class="nav__link {{ request()->routeIs('standings.*') ? 'nav__link--active' : '' }}">Standings</a>
                <a href="{{ route('teams.index') }}" class="nav__link {{ request()->routeIs('teams.*') ? 'nav__link--active' : '' }}">Teams</a>
                <a href="{{ route('venues.index') }}" class="nav__link {{ request()->routeIs('venues.*') ? 'nav__link--active' : '' }}">Venues</a>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container footer__inner">
            <div class="footer__brand">
                <span class="nav__brand-mark">26</span>
                <p>FIFA World Cup 2026 · United States · Canada · Mexico<br>
                <span class="muted">June 11 – July 19, 2026 · 48 teams · 104 matches · 16 host cities</span></p>
            </div>
            <nav class="footer__links">
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('fixtures.index') }}">Fixtures</a>
                <a href="{{ route('bracket.index') }}">Bracket</a>
                <a href="{{ route('standings.index') }}">Standings</a>
                <a href="{{ route('teams.index') }}">Teams</a>
                <a href="{{ route('venues.index') }}">Venues</a>
            </nav>
            <p class="footer__note muted">
                Unofficial fan project built with Laravel. Tournament data from the public-domain
                <a href="https://github.com/openfootball/worldcup.json" rel="noopener">openfootball</a> dataset.
            </p>
        </div>
    </footer>

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
