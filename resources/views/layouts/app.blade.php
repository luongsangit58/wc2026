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
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
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
                <a href="{{ route('home') }}" class="nav__link {{ request()->routeIs('home') ? 'nav__link--active' : '' }}">{{ __('Home') }}</a>
                <a href="{{ route('fixtures.index') }}" class="nav__link {{ request()->routeIs('fixtures.*') ? 'nav__link--active' : '' }}">{{ __('Fixtures') }}</a>
                <a href="{{ route('bracket.index') }}" class="nav__link {{ request()->routeIs('bracket.*') ? 'nav__link--active' : '' }}">{{ __('Bracket') }}</a>
                <a href="{{ route('standings.index') }}" class="nav__link {{ request()->routeIs('standings.*') ? 'nav__link--active' : '' }}">{{ __('Standings') }}</a>
                <a href="{{ route('teams.index') }}" class="nav__link {{ request()->routeIs('teams.*') ? 'nav__link--active' : '' }}">{{ __('Teams') }}</a>
                <a href="{{ route('venues.index') }}" class="nav__link {{ request()->routeIs('venues.*') ? 'nav__link--active' : '' }}">{{ __('Venues') }}</a>

                <div class="lang-switch" role="group" aria-label="{{ __('Language') }}">
                    @foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $label)
                        <a href="{{ route('lang.switch', $code) }}"
                           class="lang-switch__opt {{ app()->getLocale() === $code ? 'is-active' : '' }}"
                           title="{{ $label }}" hreflang="{{ $code }}">{{ strtoupper($code) }}</a>
                    @endforeach
                </div>
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
                <span class="muted">{{ __('June 11 – July 19, 2026 · 48 teams · 104 matches · 16 host cities') }}</span></p>
            </div>
            <nav class="footer__links">
                <a href="{{ route('home') }}">{{ __('Home') }}</a>
                <a href="{{ route('fixtures.index') }}">{{ __('Fixtures') }}</a>
                <a href="{{ route('bracket.index') }}">{{ __('Bracket') }}</a>
                <a href="{{ route('standings.index') }}">{{ __('Standings') }}</a>
                <a href="{{ route('teams.index') }}">{{ __('Teams') }}</a>
                <a href="{{ route('venues.index') }}">{{ __('Venues') }}</a>
                <a href="{{ route('privacy') }}">{{ __('Privacy') }}</a>
                <a href="{{ route('terms') }}">{{ __('Terms') }}</a>
            </nav>
            <p class="footer__note muted">
                {{ __('Unofficial fan project built with Laravel.') }}
                {!! __('Tournament data from the public-domain :openfootball dataset · player photos via :commons.', [
                    'openfootball' => '<a href="https://github.com/openfootball/worldcup.json" rel="noopener">openfootball</a>',
                    'commons' => '<a href="https://commons.wikimedia.org" rel="noopener">Wikipedia / Wikimedia Commons</a>',
                ]) !!}
            </p>
        </div>
    </footer>

    {{-- Click-to-open player card (filled by app.js) --}}
    <div class="pmodal" data-player-modal hidden>
        <div class="pmodal__backdrop" data-pmodal-close></div>
        <div class="pmodal__card" role="dialog" aria-modal="true" aria-label="{{ __('Player profile') }}">
            <button class="pmodal__close" type="button" data-pmodal-close aria-label="{{ __('Close') }}">&times;</button>
            <div data-pm-body></div>
        </div>
    </div>

    {{-- Translated labels for the JS-rendered player card --}}
    <script>
        window.WCi18n = {
            position: @json(__('Position')),
            age: @json(__('Age')),
            caps: @json(__('Caps')),
            intlGoals: @json(__("Int'l goals")),
            born: @json(__('Born')),
            viewTeam: @json(__('View team →')),
            wcGoals: @json(__(':n goals at World Cup 2026'))
        };
    </script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @stack('scripts')
</body>
</html>
