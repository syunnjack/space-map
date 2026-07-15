@extends('layouts.plain')

@section('title', 'このサイトについて | ' . config('app.name'))
@section('description', config('app.name') . 'の運営方針、データの取り扱い、口コミ・LINE通知・予約問い合わせ受付の仕組みについて説明しています。')

@section('content')
<div class="container my-4" style="max-width: 720px;">
  <h1 class="h4 fw-bold mb-4">このサイトについて</h1>

  <section class="mb-4">
    <h2 class="h6">サイトの目的</h2>
    <p class="text-muted small">
      「{{ config('app.name') }}」は、レンタルスペース・貸し会議室の場所を地図から探せる投稿型マップです。新しい施設は誰でもログイン不要・匿名で投稿でき、
      主催者・運営者の方が直前に生まれた空き枠を投稿することで情報が更新されていきます。
      予約サイトでは気づきにくい「直前のキャンセル・空き枠」が分かることが特徴です。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">空き枠について</h2>
    <p class="text-muted small">
      掲載している空き枠（日付・時間帯）は、利用者からの投稿によるものです。運営による事実確認は行っておらず、
      実際に予約できるかどうかは各施設に直接ご確認ください。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">LINE通知について</h2>
    <p class="text-muted small">
      各施設のページから「🔔 新しい空き枠が投稿されたらLINEで通知」を選ぶと、LINEログインのうえその施設を通知対象として登録できます。
      新しい空き枠が投稿されるとLINE公式アカウントからお知らせします。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">予約問い合わせについて</h2>
    <p class="text-muted small">
      各施設のページから「📮 LINEで予約問い合わせする」を選ぶと、LINEログインのうえ受け付けます。
      受付完了はLINE公式アカウントからお知らせしますが、当サイトは予約の調整そのものは行っておりません。
      お急ぎの場合は、掲載している電話番号へ直接お問い合わせいただくか、各施設の公式サイトもあわせてご確認ください。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">口コミ・投稿について</h2>
    <p class="text-muted small">
      口コミ（写真を含む）・空き枠・新規施設の投稿は、どなたでもログイン不要で行えます。投稿内容は運営による事前確認を行わず即時反映されますが、
      不適切な投稿を発見した場合は内容を精査のうえ削除などの対応を行います。
    </p>
  </section>

  <a href="{{ route('venues.index') }}" class="d-block text-center text-muted mt-4">トップページに戻る</a>
</div>
@endsection
