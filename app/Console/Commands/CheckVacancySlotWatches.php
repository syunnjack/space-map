<?php

namespace App\Console\Commands;

use App\Models\Favorite;
use App\Models\VacancySlot;
use App\Support\LineMessaging;
use Illuminate\Console\Command;

class CheckVacancySlotWatches extends Command
{
    protected $signature = 'vacancy:check-watches';

    protected $description = 'ウォッチ登録されたスペースに新しい空き枠が投稿されていないか確認し、LINEで通知する';

    public function handle(): int
    {
        $favorites = Favorite::with('lineUser')->get();

        foreach ($favorites as $favorite) {
            if (! $favorite->lineUser) {
                continue;
            }

            $since = $favorite->last_checked_slot_id ?? 0;
            $newSlots = VacancySlot::where('venue_id', $favorite->venue_id)
                ->where('id', '>', $since)
                ->get();

            if ($newSlots->isEmpty()) {
                continue;
            }

            // 空き枠の投稿=空きの告知そのものであり「空きなし」報告は存在しないため、
            // 新着はすべて通知対象。
            $latest = $newSlots->sortByDesc('id')->first();
            $favorite->loadMissing('venue');
            LineMessaging::push(
                $favorite->lineUser->line_user_id,
                "「{$favorite->venue->name}」に新しい空き枠が投稿されました: "
                . $latest->available_date->format('n/j')
                . ' ' . substr($latest->start_time, 0, 5) . '〜' . substr($latest->end_time, 0, 5)
            );

            // last_checked_slot_idは検知カーソル。idは常に厳密単調増加のため、
            // created_at(秒精度)を使った場合に起こりうる同一秒内の複数投稿の取りこぼしが起きない。
            $favorite->update(['last_checked_slot_id' => $newSlots->max('id')]);
        }

        return self::SUCCESS;
    }
}
