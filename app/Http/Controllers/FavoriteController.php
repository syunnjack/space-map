<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Venue;
use App\Models\VacancySlot;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle(Request $request, Venue $venue)
    {
        $lineUserLocalId = $request->session()->get('line_user_local_id');

        if (! $lineUserLocalId) {
            return redirect()->route('line.login', ['venue' => $venue->id]);
        }

        $favorite = Favorite::where('line_user_id', $lineUserLocalId)
            ->where('venue_id', $venue->id)
            ->first();

        if ($favorite) {
            $favorite->delete();

            return back()->with('success', '通知登録を解除しました。');
        }

        Favorite::create([
            'line_user_id' => $lineUserLocalId,
            'venue_id' => $venue->id,
            'last_checked_slot_id' => VacancySlot::where('venue_id', $venue->id)->max('id') ?? 0,
        ]);

        return back()->with('success', '新しい空き枠が投稿されるとLINEでお知らせします。');
    }
}
