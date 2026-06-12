@extends('layouts.app')

@section('title', __('Privacy Policy'))
@section('meta_description', 'Privacy policy for the unofficial FIFA World Cup 2026 fan project.')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div class="page-head__eyebrow">{{ __('Legal') }}</div>
            <h1 class="page-head__title">{{ __('Privacy Policy') }}</h1>
            <p class="page-head__sub">{{ __('Last updated: :date', ['date' => $updated]) }}</p>
        </div>

        <div class="legal">
            <p>{{ __('This is an unofficial, non-commercial fan project for the FIFA World Cup 2026. We keep data collection to an absolute minimum.') }}</p>

            <h2>{{ __('Information we collect') }}</h2>
            <p>{{ __('We do not require accounts and never ask for personal information. The only thing we remember is your chosen language, stored in a session cookie so the site shows the right language on your next visit.') }}</p>

            <h2>{{ __('Cookies') }}</h2>
            <p>{{ __('We use a single essential session cookie for basic site function and your language preference. We do not use advertising or third-party tracking cookies.') }}</p>

            <h2>{{ __('Third-party content') }}</h2>
            <p>{{ __('Player photos are served from Wikipedia / Wikimedia Commons and match data comes from the public-domain openfootball dataset. Your browser may contact these services to load that content.') }}</p>

            <h2>{{ __('Children and general audience') }}</h2>
            <p>{{ __('We store no personal data and the site is suitable for a general audience.') }}</p>

            <h2>{{ __('Contact') }}</h2>
            <p>{{ __('Questions about this policy? Reach us through the project repository.') }}</p>
        </div>
    </div>
</section>
@endsection
