<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class OsmSpaceSeeder extends Seeder
{
    /**
     * OpenStreetMap から取り出した公共施設（公民館・コミュニティセンター・会議施設）を取り込む。
     *
     * データは scripts/build-space-data.py が database/data/spaces-osm.json に書き出す。
     * 出典は OpenStreetMap contributors（ODbL 1.0）で、表示側に明記する必要がある。
     * 元データに無い項目（料金、定員など）は補わずに空のままにする。
     * 利用者が投稿した施設（source が null）には触れない。
     */
    private const CHUNK = 40;

    public function run(): void
    {
        $path = database_path('data/spaces-osm.json');

        if (! File::exists($path)) {
            throw new RuntimeException('database/data/spaces-osm.json が見つかりません。');
        }

        $payload = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        $spaces = $payload['spaces'] ?? [];

        if ($spaces === []) {
            throw new RuntimeException('施設データが空です。');
        }

        $now = now();
        $written = 0;

        foreach (array_chunk($spaces, self::CHUNK) as $chunk) {
            $rows = [];

            foreach ($chunk as $space) {
                $rows[] = [
                    'name' => $space['name'],
                    'area' => $space['area'],
                    'city' => $space['city'],
                    'facility_type' => $space['facilityType'],
                    'address' => $space['address'],
                    'phone' => $space['phone'],
                    'website' => $space['website'],
                    'opening_hours' => $space['openingHours'],
                    'lat' => $space['lat'],
                    'lng' => $space['lng'],
                    'source' => 'openstreetmap',
                    'source_ref' => $space['sourceRef'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('venues')->upsert(
                $rows,
                ['source', 'source_ref'],
                [
                    'name', 'area', 'city', 'facility_type', 'address', 'phone',
                    'website', 'opening_hours', 'lat', 'lng', 'updated_at',
                ]
            );

            $written += count($rows);
        }

        $this->command?->info(number_format($written).'件を取り込みました（掲載中 '
            .number_format(Venue::count()).'件）。');
    }
}
