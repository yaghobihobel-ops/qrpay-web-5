<!DOCTYPE html>
<html lang="{{ get_default_language_code() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $basic_settings->sitename(__($page_title??'')) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100;200;300;400;500;600;700;800;900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    @include('user.partials.header-assets')
    @vite(['resources/css/app.css', 'resources/sass/app.scss', 'resources/js/app.js'])

    @stack('css')

    <style>
        .offline-banner {
            position: fixed;
            right: 1.5rem;
            bottom: 1.5rem;
            width: min(360px, calc(100% - 2rem));
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            background: rgba(17, 24, 39, 0.92);
            color: #ffffff;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25);
            z-index: 1090;
            backdrop-filter: blur(6px);
            font-size: 0.875rem;
        }

        .offline-banner.is-offline {
            background: rgba(185, 28, 28, 0.92);
        }

        .offline-banner[hidden] {
            display: none !important;
        }

        .offline-banner__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .offline-banner__status {
            font-weight: 600;
        }

        .offline-banner__meta {
            font-size: 0.75rem;
            opacity: 0.85;
            margin-top: 0.2rem;
        }

        .offline-banner__retry {
            border: 0;
            border-radius: 999px;
            padding: 0.35rem 0.9rem;
            font-weight: 600;
            color: #111827;
            background: #fbbf24;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        .offline-banner__retry:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .offline-banner__feedback {
            min-height: 1rem;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            opacity: 0.9;
        }

        .offline-banner__list {
            list-style: none;
            margin: 0.75rem 0 0;
            padding: 0;
            max-height: 220px;
            overflow-y: auto;
        }

        .offline-banner__item + .offline-banner__item {
            border-top: 1px solid rgba(255, 255, 255, 0.18);
            margin-top: 0.75rem;
            padding-top: 0.75rem;
        }

        .offline-banner__item-title {
            font-size: 0.8rem;
            font-weight: 600;
            word-break: break-word;
        }

        .offline-banner__item-subtitle {
            font-size: 0.72rem;
            opacity: 0.85;
            margin-top: 0.35rem;
            word-break: break-word;
        }

        @media (max-width: 575px) {
            .offline-banner {
                right: 1rem;
                left: 1rem;
                width: auto;
            }
        }
    </style>
</head>
@php
    $user = auth()->user();
    $themePreference = $user->preferred_theme ?? 'light';
    $languagePreference = $user->preferred_language ?? app()->getLocale();
@endphp
<body class="{{ selectedLangDir() ?? "ltr"}}" data-theme="{{ $themePreference }}" data-language="{{ $languagePreference }}">

    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start body overlay
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <div id="body-overlay" class="body-overlay"></div>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End body overlay
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start Dashboard
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <div class="page-wrapper">
        @include('user.partials.side-nav')
        <div class="main-wrapper">
            <div class="main-body-wrapper">
                @include('user.partials.top-nav')
                @yield('content')
            </div>
        </div>
    </div>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End Dashboard
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <a href="{{ setRoute('user.receive.money.index') }}" class="qr-scan"><i class="fas fa-qrcode"></i></a>

    <div id="offline-status-banner" class="offline-banner" hidden aria-live="polite">
        <div class="offline-banner__header">
            <div>
                <div id="offline-status-text" class="offline-banner__status">You are offline.</div>
                <div class="offline-banner__meta"><span id="offline-queue-count">0</span> queued transaction(s)</div>
            </div>
            <button type="button" id="offline-queue-retry" class="offline-banner__retry">Retry now</button>
        </div>
        <div id="offline-queue-feedback" class="offline-banner__feedback" aria-live="polite"></div>
        <ul id="offline-queue-list" class="offline-banner__list" aria-live="polite"></ul>
    </div>

    @include('user.partials.footer-assets')
    @include('user.partials.push-notification')
    @stack('script')
</body>

</html>
