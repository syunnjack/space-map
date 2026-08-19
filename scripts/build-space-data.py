"""OpenStreetMap から、貸室のある公共施設（公民館・コミュニティセンター等）を取り出す。

出典: OpenStreetMap contributors（ODbL 1.0） https://www.openstreetmap.org/copyright

レンタルスペースの多くは民間の予約サイトが持つ情報で、そのまま転載はできない。
一方、公民館・コミュニティセンター・会議施設は公共施設として地図に載っており、
実際に部屋を借りられる場所でもある。ここを土台にして、利用者の投稿（空き枠・
口コミ）を上に載せる。

都道府県ごとに問い合わせるので、座標から都道府県を求め直す必要がない。

使い方: python scripts/build-space-data.py
  → database/data/spaces-osm.json を書き出す
"""
import json
import re
import time
import urllib.parse
import urllib.request
from datetime import date
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
CACHE = ROOT / 'scripts' / '.cache'
OUTPUT = ROOT / 'database' / 'data' / 'spaces-osm.json'

OVERPASS = 'https://overpass-api.de/api/interpreter'
UA = 'rental-space-map-data/1.0 (+https://rental-space-map.jp)'
DELAY = 3.0  # Overpass への間隔（秒）

PREFECTURES = [
    '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県',
    '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県',
    '山梨県', '長野県', '岐阜県', '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府',
    '兵庫県', '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県',
    '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県',
    '鹿児島県', '沖縄県',
]

QUERY = """
[out:json][timeout:300];
area["name"="{prefecture}"]["admin_level"="4"]->.pref;
(
  nwr["amenity"="community_centre"]["name"](area.pref);
  nwr["amenity"="conference_centre"]["name"](area.pref);
);
out tags center;
"""

# 施設ではないもの（バス停や交差点の名前に施設名が入っている場合がある）
NOT_A_PLACE = ('highway', 'railway', 'public_transport', 'aeroway', 'barrier', 'junction')
# 貸室として案内するのがふさわしくない名前
DENY_NAME = re.compile('跡$|跡地|案内図|バス停|駐車場$')


def fetch(prefecture: str) -> list[dict]:
    CACHE.mkdir(exist_ok=True)
    path = CACHE / f'overpass-{PREFECTURES.index(prefecture):02d}.json'

    if not path.exists():
        body = urllib.parse.urlencode({'data': QUERY.format(prefecture=prefecture)}).encode()
        request = urllib.request.Request(OVERPASS, data=body, headers={'User-Agent': UA})
        with urllib.request.urlopen(request, timeout=320) as response:
            payload = json.loads(response.read().decode('utf-8', 'replace'))
        path.write_text(json.dumps(payload['elements'], ensure_ascii=False), encoding='utf-8')
        time.sleep(DELAY)

    return json.loads(path.read_text(encoding='utf-8'))


def main() -> None:
    records = []
    seen = set()

    for prefecture in PREFECTURES:
        try:
            elements = fetch(prefecture)
        except Exception as error:
            print(f'{prefecture} の取得に失敗しました: {error}', flush=True)
            continue

        added = 0
        for element in elements:
            tags = element.get('tags', {})
            name = (tags.get('name') or '').strip()

            if not name or DENY_NAME.search(name):
                continue
            if any(key in tags for key in NOT_A_PLACE):
                continue

            center = element.get('center') or element
            lat, lng = center.get('lat'), center.get('lon')
            if lat is None or lng is None:
                continue

            key = (element['type'], element['id'])
            if key in seen:
                continue
            seen.add(key)

            address = tags.get('addr:full') or ''.join(filter(None, [
                tags.get('addr:province'), tags.get('addr:city'), tags.get('addr:suburb'),
                tags.get('addr:neighbourhood'), tags.get('addr:block_number'), tags.get('addr:housenumber'),
            ]))

            records.append({
                'name': name,
                'area': prefecture,
                'city': tags.get('addr:city'),
                'address': address or None,
                'facilityType': '会議施設' if tags.get('amenity') == 'conference_centre' else '公民館・コミュニティセンター',
                'phone': tags.get('phone') or tags.get('contact:phone'),
                'website': tags.get('website') or tags.get('contact:website'),
                'openingHours': tags.get('opening_hours'),
                'lat': round(float(lat), 7),
                'lng': round(float(lng), 7),
                'sourceRef': f"{element['type']}/{element['id']}",
            })
            added += 1

        print(f'{prefecture} {added}件', flush=True)

    records.sort(key=lambda record: (record['area'], record['name']))

    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT.write_text(json.dumps({
        'confirmedOn': date.today().isoformat(),
        'sourceLabel': 'OpenStreetMap contributors（ODbL 1.0）',
        'sourceUrl': 'https://www.openstreetmap.org/copyright',
        'spaces': records,
    }, ensure_ascii=False), encoding='utf-8')

    print(f'{len(records)}件を書き出しました')


main()
