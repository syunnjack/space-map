<?php

namespace App\Http\Controllers;

use App\Models\BookingRequest;
use App\Models\Favorite;
use App\Models\LineUser;
use App\Models\VacancySlot;
use App\Models\Venue;
use App\Support\LineMessaging;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LineLoginController extends Controller
{
    public function redirect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('line_login_state', $state);

        if ($request->filled('venue')) {
            $request->session()->put('line_login_intended_venue', (int) $request->input('venue'));
        }

        if ($request->filled('booking_venue')) {
            $request->session()->put('line_login_intended_booking_venue', (int) $request->input('booking_venue'));
        }

        return redirect()->away(LineMessaging::authorizeUrl($state));
    }

    public function callback(Request $request)
    {
        $state = $request->query('state');
        $expectedState = $request->session()->pull('line_login_state');

        if (! $state || $state !== $expectedState) {
            return redirect()->route('venues.index')->withErrors(['line' => 'LINEログインの検証に失敗しました。もう一度お試しください。']);
        }

        if (! $request->filled('code')) {
            return redirect()->route('venues.index')->withErrors(['line' => 'LINEログインがキャンセルされました。']);
        }

        $token = LineMessaging::exchangeToken($request->input('code'));
        $claims = LineMessaging::verifyIdToken($token['id_token']);

        $lineUser = LineUser::updateOrCreate(
            ['line_user_id' => $claims['sub']],
            ['display_name' => $claims['name'] ?? null]
        );

        $request->session()->put('line_user_local_id', $lineUser->id);

        $intendedVenueId = $request->session()->pull('line_login_intended_venue');
        if ($intendedVenueId) {
            $venue = Venue::find($intendedVenueId);
            if ($venue) {
                Favorite::firstOrCreate(
                    ['line_user_id' => $lineUser->id, 'venue_id' => $venue->id],
                    ['last_checked_slot_id' => VacancySlot::where('venue_id', $venue->id)->max('id') ?? 0]
                );

                return redirect()->route('venues.show', $venue)->with('success', '通知登録が完了しました。新しい空き枠が投稿されるとLINEでお知らせします。');
            }
        }

        $intendedBookingVenueId = $request->session()->pull('line_login_intended_booking_venue');
        if ($intendedBookingVenueId) {
            $venue = Venue::find($intendedBookingVenueId);
            if ($venue) {
                $bookingRequest = BookingRequest::firstOrCreate([
                    'line_user_id' => $lineUser->id,
                    'venue_id' => $venue->id,
                ]);

                if ($bookingRequest->wasRecentlyCreated) {
                    app(BookingRequestController::class)->sendConfirmation($bookingRequest);
                }

                return redirect()->route('venues.show', $venue)->with('success', '「' . $venue->name . '」への予約問い合わせを受け付けました。LINEで受付完了のお知らせをお送りします。');
            }
        }

        return redirect()->route('venues.index')->with('success', 'LINEログインが完了しました。');
    }
}
