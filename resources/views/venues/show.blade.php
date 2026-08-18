@extends('layouts.plain')

@section('title', $venue->name . ' の空き枠・口コミ | ' . config('app.name'))
@section('description', $venue->name . '（' . ($venue->area ?? 'レンタルスペース') . '）の場所・直前の空き枠・利用者の口コミを確認できます。')

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'BreadcrumbList',
  'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => config('app.name'), 'item' => url('/')],
      ['@type' => 'ListItem', 'position' => 2, 'name' => $venue->name, 'item' => url("/venues/{$venue->id}")],
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode(array_filter([
  '@@context' => 'https://schema.org',
  '@type' => 'LocalBusiness',
  'name' => $venue->name,
  'description' => $venue->description,
  'geo' => [
      '@type' => 'GeoCoordinates',
      'latitude' => $venue->lat,
      'longitude' => $venue->lng,
  ],
  'address' => $venue->address ?? $venue->area,
  'telephone' => $venue->phone,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="container my-4">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h1 class="h3 fw-bold mb-3">{{ $venue->name }}</h1>
      <p class="text-muted mb-2">{{ $venue->description }}</p>
      @if($venue->area)
        <p class="text-secondary small mb-1">エリア: {{ $venue->area }}</p>
      @endif
      @if($venue->address)
        <p class="text-secondary small mb-1">住所: {{ $venue->address }}</p>
      @endif
      @if($venue->phone)
        <p class="text-secondary small mb-1">電話: {{ $venue->phone }}</p>
      @endif
      @if($venue->capacity)
        <p class="text-secondary small mb-1">収容人数: {{ $venue->capacity }}名</p>
      @endif
      @if($venue->hourly_rate)
        <p class="text-secondary small mb-4">目安料金: {{ number_format($venue->hourly_rate) }}円/時間</p>
      @endif

      <div class="mb-3">
        <a href="{{ route('venues.index') }}" class="btn btn-secondary">トップページに戻る</a>
      </div>

      @if (session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('venues.favorite.toggle', $venue) }}" class="mb-3">
        @csrf
        @if ($isWatching)
          <button type="submit" class="btn btn-outline-secondary">🔕 通知をやめる</button>
        @else
          {{-- LINEの認証情報が未設定のうちは、押すとLINE側でエラーになるので出さない --}}
          @if (config('services.line.login_channel_id'))
          <button type="submit" class="btn btn-line">🔔 新しい空き枠が投稿されたらLINEで通知</button>
          @else
            <button type="button" class="btn btn-secondary" disabled>🔔 新しい空き枠が投稿されたらLINEで通知（準備中）</button>
          @endif
        @endif
      </form>

      <form method="POST" action="{{ route('venues.booking-request.store', $venue) }}" class="mb-4">
        @csrf
        @if ($hasRequestedBooking)
          <button type="submit" class="btn btn-outline-secondary" disabled>📮 予約問い合わせ済みです</button>
        @else
          {{-- LINEの認証情報が未設定のうちは、押すとLINE側でエラーになるので出さない --}}
          @if (config('services.line.login_channel_id'))
          <button type="submit" class="btn btn-line">📮 LINEで予約問い合わせする</button>
          @else
            <button type="button" class="btn btn-secondary" disabled>📮 LINEで予約問い合わせする（準備中）</button>
          @endif
        @endif
      </form>

      <div class="d-flex align-items-center mt-4 mb-4">
        <button id="likeButton" data-venue-id="{{ $venue->id }}" class="btn btn-primary me-2">いいね！</button>
        <span id="likesCount" class="h4 fw-bold mb-0">{{ $venue->likes_count }}</span> <span class="text-muted ms-1">件のいいね！</span>
      </div>

      <h2 class="h5 mb-2">直前の空き枠</h2>

      <h3 class="h6 mt-3 mb-2">空き枠を投稿する</h3>
      <p class="text-muted small">主催者・運営者の方は、直前にキャンセルが出た枠などをここから投稿できます。</p>
      <form action="{{ route('venues.vacancy-slots.store', $venue) }}" method="POST" class="bg-light p-3 rounded shadow-sm mb-4">
        @csrf
        <div style="position:absolute; left:-9999px;" aria-hidden="true">
          <label>ウェブサイト<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>
        <div class="row">
          <div class="col-12 col-md-4 mb-2">
            <label class="form-label small">日付 <span class="text-danger">*</span></label>
            <input type="date" name="available_date" class="form-control form-control-sm" required>
          </div>
          <div class="col-6 col-md-4 mb-2">
            <label class="form-label small">開始時刻 <span class="text-danger">*</span></label>
            <input type="time" name="start_time" class="form-control form-control-sm" required>
          </div>
          <div class="col-6 col-md-4 mb-2">
            <label class="form-label small">終了時刻 <span class="text-danger">*</span></label>
            <input type="time" name="end_time" class="form-control form-control-sm" required>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label small">ニックネーム（任意）</label>
          <input type="text" name="nickname" class="form-control form-control-sm" maxlength="30">
        </div>
        <div class="mb-2">
          <label class="form-label small">コメント（任意）</label>
          <textarea name="comment" class="form-control form-control-sm" rows="2" maxlength="1000" placeholder="例：直前キャンセルが出ました。ご希望の方はお問い合わせください。"></textarea>
        </div>
        <button type="submit" class="btn btn-dark">投稿する</button>
      </form>

      <div id="vacancySlotList" class="mb-5">
        @forelse($venue->vacancySlots as $slot)
          <div class="border rounded p-3 mb-2 bg-white">
            <div class="d-flex justify-content-between">
              <strong>{{ $slot->available_date->format('Y/m/d (D)') }} {{ substr($slot->start_time, 0, 5) }}〜{{ substr($slot->end_time, 0, 5) }}</strong>
              <span class="text-muted small">投稿: {{ $slot->created_at->format('m/d H:i') }}</span>
            </div>
            <div class="small text-muted">{{ $slot->nickname }}</div>
            @if($slot->comment)
              <p class="mb-0 mt-1">{{ $slot->comment }}</p>
            @endif
          </div>
        @empty
          <p class="text-muted">現在、投稿されている空き枠はありません。</p>
        @endforelse
      </div>

      <h3 class="h6 mt-4 mb-2">写真付き口コミを投稿する</h3>
      <form action="{{ route('venues.reviews.store', $venue) }}" method="POST" enctype="multipart/form-data" class="bg-light p-3 rounded shadow-sm">
        @csrf
        <div style="position:absolute; left:-9999px;" aria-hidden="true">
          <label>ウェブサイト<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>
        <div class="mb-2">
          <label class="form-label small">ニックネーム（任意）</label>
          <input type="text" name="nickname" class="form-control form-control-sm" maxlength="30">
        </div>
        <div class="mb-2">
          <label class="form-label small">評価</label>
          <select name="rating" class="form-select form-select-sm" required>
            <option value="">選択してください</option>
            <option value="5">★★★★★</option>
            <option value="4">★★★★☆</option>
            <option value="3">★★★☆☆</option>
            <option value="2">★★☆☆☆</option>
            <option value="1">★☆☆☆☆</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label small">口コミ</label>
          <textarea name="comment" class="form-control form-control-sm" rows="3" minlength="5" maxlength="1000" required></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label small">施設の様子の写真（任意）</label>
          <input type="file" name="photo" accept="image/*" class="form-control form-control-sm">
        </div>
        <button type="submit" class="btn btn-dark">投稿する</button>
      </form>

      <h3 class="h6 mt-5 mb-3">口コミ</h3>
      <div id="reviewList">
        @forelse($venue->reviews as $review)
          <div class="card mb-3 bg-light">
            @if($review->photo_path)
              <img src="{{ \Illuminate\Support\Facades\Storage::url($review->photo_path) }}" class="card-img-top" style="max-height:320px;object-fit:cover;" alt="{{ $venue->name }}の口コミ写真">
            @endif
            <div class="card-body">
              <div>{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }} <strong>{{ $review->nickname }}</strong></div>
              <p class="mb-1">{{ $review->comment }}</p>
              <small class="text-muted">投稿日: {{ $review->created_at->format('Y/m/d H:i') }}</small>
            </div>
          </div>
        @empty
          <p class="text-muted">まだ口コミはありません。</p>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const likeButton = document.getElementById('likeButton');
    const likesCountSpan = document.getElementById('likesCount');
    if (likeButton) {
      likeButton.addEventListener('click', async function() {
        const venueId = likeButton.dataset.venueId;
        try {
          const response = await fetch(`/venues/${venueId}/like`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
          });
          if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'いいね！に失敗しました。');
          }
          const data = await response.json();
          likesCountSpan.textContent = data.likes_count;
        } catch (error) {
          alert('エラー: ' + error.message);
        }
      });
    }
  });
</script>
@endsection
