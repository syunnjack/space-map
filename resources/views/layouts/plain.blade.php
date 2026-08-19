<!DOCTYPE html>
<html lang="ja">
<head>
  <meta name="google-site-verification" content="35Y1aFhE4dN-iCiL75rg5HmeEbuR26SQUixGjKgz3xE" />
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#4a3b8f">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', config('app.name') . ' | 現在地から探す・直前の空き枠がわかるレンタルスペースマップ')</title>
  <meta name="description" content="@yield('description', '全国のレンタルスペース・貸し会議室を地図から探せる投稿型マップです。現在地から近い施設をすぐ見つけられ、主催者が投稿する直前の空き枠をリアルタイムで確認できます。')">
  @php
      // url()->current() はクエリを落とすため、2ページ目以降が1ページ目を
      // 正規URLとして申告してしまう。内容が変わる page だけを残す。
      $canonicalQuery = array_filter(request()->only(['page']), fn ($value) => $value !== null && $value !== '' && $value !== '1');
      $canonicalUrl = url()->current() . ($canonicalQuery ? '?' . http_build_query($canonicalQuery) : '');
  @endphp
  <link rel="canonical" href="{{ $canonicalUrl }}">

  <meta property="og:site_name" content="{{ config('app.name') }}">
  <meta property="og:type" content="website">
  <meta property="og:title" content="@yield('title', config('app.name') . ' | 現在地から探す・直前の空き枠がわかるレンタルスペースマップ')">
  <meta property="og:description" content="@yield('description', '全国のレンタルスペース・貸し会議室を地図から探せる投稿型マップです。現在地から近い施設をすぐ見つけられ、主催者が投稿する直前の空き枠をリアルタイムで確認できます。')">
  <meta property="og:url" content="{{ $canonicalUrl }}">
  <meta property="og:locale" content="ja_JP">

  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="@yield('title', config('app.name') . ' | 現在地から探す・直前の空き枠がわかるレンタルスペースマップ')">
  <meta name="twitter:description" content="@yield('description', '全国のレンタルスペース・貸し会議室を地図から探せる投稿型マップです。現在地から近い施設をすぐ見つけられ、主催者が投稿する直前の空き枠をリアルタイムで確認できます。')">

  <link rel="icon" href="/favicon.ico" sizes="any">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body { background-color: #f6f5fb; font-family: system-ui, -apple-system, sans-serif; }
    .btn { min-height: 44px; }
    .btn-line { background: #06c755; color: #fff; border: none; }
    .btn-line:hover { background: #05a848; color: #fff; }
    .btn-space { background: #4a3b8f; color: #fff; border: none; }
    .btn-space:hover { background: #392d6e; color: #fff; }
  </style>
  @yield('styles')

  @stack('structured-data')
  @if(config('services.ga4.id'))
  <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.ga4.id') }}"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ config('services.ga4.id') }}');
  </script>
  @endif
</head>
<body>
  <nav class="navbar navbar-dark p-2" style="background-color:#332968;">
    <div class="container-fluid">
      <a href="{{ route('venues.index') }}" class="navbar-brand text-white text-decoration-none">🏢 {{ config('app.name') }}</a>
      <a href="{{ route('about') }}" class="text-white small text-decoration-none">サイトについて</a>
    </div>
  </nav>

  @yield('content')

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  @yield('scripts')
</body>
</html>
