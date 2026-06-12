@extends('layouts.app')

@section('title', __('Terms of Service'))
@section('meta_description', 'Terms of service for the unofficial FIFA World Cup 2026 fan project.')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div class="page-head__eyebrow">{{ __('Legal') }}</div>
            <h1 class="page-head__title">{{ __('Terms of Service') }}</h1>
            <p class="page-head__sub">{{ __('Last updated: :date', ['date' => $updated]) }}</p>
        </div>

        <div class="legal">
            <h2>{{ __('Acceptance of terms') }}</h2>
            <p>{{ __('By using this website, you agree to these terms. If you do not agree, please do not use the site.') }}</p>

            <h2>{{ __('Unofficial project') }}</h2>
            <p>{{ __('This is an independent fan project. It is not affiliated with, endorsed by, or sponsored by FIFA or any official body. All team, competition and player names belong to their respective owners.') }}</p>

            <h2>{{ __('Accuracy of information') }}</h2>
            <p>{{ __('Fixtures, scores and statistics are provided as-is from public sources and may be incomplete or delayed. Please do not rely on them for betting or any official purpose.') }}</p>

            <h2>{{ __('Acceptable use') }}</h2>
            <p>{{ __('Use the site lawfully. Do not attempt to disrupt, overload, or misuse it.') }}</p>

            <h2>{{ __('Intellectual property') }}</h2>
            <p>{{ __('Player photos are licensed via Wikimedia Commons; tournament data comes from the public-domain openfootball dataset.') }}</p>

            <h2>{{ __('Limitation of liability') }}</h2>
            <p>{{ __('The site is provided without warranty of any kind. We are not liable for any loss arising from its use.') }}</p>

            <h2>{{ __('Changes to these terms') }}</h2>
            <p>{{ __('We may update these terms from time to time. Continued use of the site means you accept the updated terms.') }}</p>
        </div>
    </div>
</section>
@endsection
