<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Support\ContentModeration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VenueController extends Controller
{
    public function index(Request $request)
    {
        $query = Venue::query();

        // 旧URL（/?area=東京都）は都道府県ページへ送る。
        if ($request->filled('area')) {
            $slug = Venue::slugForArea((string) $request->input('area'));

            if ($slug !== null) {
                return redirect()->route('venues.area', ['areaSlug' => $slug], 301);
            }
        }

        // 全件を1ページに描くとHTMLが大きくなりすぎるため、ページ送りにする。
        $venues = $query->latest()->paginate(self::PER_PAGE);

        return view('venues.index', [
            'venues' => $venues,
            'areaCounts' => $this->areaCounts(),
            'area' => null,
            'areaSlug' => null,
            'total' => Venue::count(),
        ]);
    }

    /** 1ページに載せる施設の数。 */
    private const PER_PAGE = 60;

    public function area(string $areaSlug)
    {
        $area = Venue::areaForSlug($areaSlug);

        if ($area === null) {
            abort(404);
        }

        $venues = Venue::where('area', $area)->orderBy('city')->orderBy('name')->paginate(self::PER_PAGE);

        if ($venues->total() === 0) {
            abort(404);
        }

        return view('venues.index', [
            'venues' => $venues,
            'areaCounts' => $this->areaCounts(),
            'area' => $area,
            'areaSlug' => $areaSlug,
            'total' => $venues->total(),
        ]);
    }

    /** 都道府県ごとの掲載件数（多い順）。 */
    private function areaCounts()
    {
        return Venue::query()
            ->selectRaw('area, COUNT(*) as total')
            ->whereNotNull('area')
            ->groupBy('area')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'area' => $row->area,
                'slug' => Venue::slugForArea($row->area),
                'total' => (int) $row->total,
            ])
            ->filter(fn (array $row) => $row['slug'] !== null)
            ->values();
    }

    public function create()
    {
        return view('venues.create');
    }

    public function store(Request $request)
    {
        if (! empty($request->input('website'))) {
            return redirect()->route('venues.thanks');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'area' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'capacity' => 'nullable|integer|min:1|max:9999',
            'hourly_rate' => 'nullable|integer|min:0|max:1000000',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if (ContentModeration::containsNgWord($validated['name'] . ' ' . ($validated['description'] ?? ''))) {
            return back()->withErrors(['name' => '投稿内容に使用できない文字列が含まれています。'])->withInput();
        }

        $ipHash = ContentModeration::clientIpHash($request);
        if (ContentModeration::isTooSoon("venue-create:{$ipHash}", 30)) {
            return back()->withErrors(['name' => '投稿間隔が短すぎます。しばらく待ってから再度お試しください。'])->withInput();
        }

        Venue::create($validated);

        return redirect()->route('venues.thanks');
    }

    public function show(Venue $venue)
    {
        $venue->load(['reviews' => fn ($q) => $q->latest()]);
        $venue->load(['vacancySlots' => fn ($q) => $q->where('available_date', '>=', now()->toDateString())->orderBy('available_date')->orderBy('start_time')]);

        $isWatching = session('line_user_local_id')
            ? $venue->favorites()->where('line_user_id', session('line_user_local_id'))->exists()
            : false;

        $hasRequestedBooking = session('line_user_local_id')
            ? $venue->bookingRequests()->where('line_user_id', session('line_user_local_id'))->exists()
            : false;

        return view('venues.show', compact('venue', 'isWatching', 'hasRequestedBooking'));
    }

    public function like(Request $request, Venue $venue)
    {
        $ipHash = ContentModeration::clientIpHash($request);
        if (ContentModeration::isTooSoon("like:{$venue->id}:{$ipHash}", 60)) {
            return response()->json(['error' => 'いいね！は少し時間を空けてから再度お試しください。'], 429);
        }

        $venue->increment('likes_count');
        $venue->refresh();

        return response()->json(['likes_count' => $venue->likes_count]);
    }

    public function sitemap()
    {
        // 掲載件数が多く、毎回組み立てると重いので短時間だけ覚えておく。
        $xml = Cache::remember('sitemap-xml', now()->addHour(), function () {
            $venues = Venue::select('id', 'updated_at')->get();
            $areaSlugs = Venue::query()
                ->whereNotNull('area')
                ->distinct()
                ->pluck('area')
                ->map(fn (string $area) => Venue::slugForArea($area))
                ->filter()
                ->values();

            return view('sitemap', compact('venues', 'areaSlugs'))->render();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
