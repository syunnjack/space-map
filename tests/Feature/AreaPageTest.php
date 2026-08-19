<?php

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeVenue(string $area, string $name): Venue
    {
        return Venue::create([
            'name' => $name,
            'area' => $area,
            'city' => '千代田区',
            'facility_type' => '公民館・コミュニティセンター',
            'lat' => 35.68,
            'lng' => 139.76,
            'source' => 'openstreetmap',
            'source_ref' => 'node/'.random_int(1, 99999999),
        ]);
    }

    public function test_都道府県ページが掲載件数を表示する(): void
    {
        $this->makeVenue('東京都', 'テスト会館');

        $response = $this->get('/area/tokyo');

        $response->assertOk();
        $response->assertSee('テスト会館');
        $response->assertSee('東京都のレンタルスペース', false);
    }

    public function test_掲載の無い都道府県ページは404になる(): void
    {
        $this->makeVenue('東京都', 'テスト会館');

        $this->get('/area/okinawa')->assertNotFound();
        $this->get('/area/nowhere')->assertNotFound();
    }

    public function test_旧エリア検索は都道府県ページへ転送される(): void
    {
        $this->makeVenue('東京都', 'テスト会館');

        $this->get('/?area='.urlencode('東京都'))
            ->assertRedirect(route('venues.area', 'tokyo'));
    }

    public function test_2ページ目は自分自身を正規URLとして申告する(): void
    {
        foreach (range(1, 61) as $i) {
            $this->makeVenue('東京都', "テスト会館{$i}");
        }

        $this->get('/area/tokyo?page=2')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('venues.area', 'tokyo').'?page=2">', false);
    }

    public function test_サイトマップに都道府県ページが載る(): void
    {
        $this->makeVenue('東京都', 'テスト会館');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('venues.area', 'tokyo'), false);
    }

    public function test_施設ページに出典が載る(): void
    {
        $venue = $this->makeVenue('東京都', 'テスト会館');

        $this->get("/venues/{$venue->id}")
            ->assertOk()
            ->assertSee('OpenStreetMap contributors', false)
            ->assertSee('公民館・コミュニティセンター');
    }
}
