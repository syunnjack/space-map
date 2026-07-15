<?php

namespace App\Http\Controllers;

use App\Models\BookingRequest;
use App\Models\Venue;
use App\Support\LineMessaging;
use Illuminate\Http\Request;

class BookingRequestController extends Controller
{
    public function store(Request $request, Venue $venue)
    {
        $lineUserLocalId = $request->session()->get('line_user_local_id');

        if (! $lineUserLocalId) {
            return redirect()->route('line.login', ['booking_venue' => $venue->id]);
        }

        $bookingRequest = BookingRequest::where('line_user_id', $lineUserLocalId)
            ->where('venue_id', $venue->id)
            ->first();

        if ($bookingRequest) {
            return back()->with('success', '「' . $venue->name . '」への予約問い合わせはすでに受け付けています。');
        }

        $bookingRequest = BookingRequest::create([
            'line_user_id' => $lineUserLocalId,
            'venue_id' => $venue->id,
        ]);

        $this->sendConfirmation($bookingRequest);

        return back()->with('success', '「' . $venue->name . '」への予約問い合わせを受け付けました。LINEで受付完了のお知らせをお送りします。');
    }

    public function sendConfirmation(BookingRequest $bookingRequest): void
    {
        $bookingRequest->loadMissing('lineUser', 'venue');

        if (! $bookingRequest->lineUser) {
            return;
        }

        $venue = $bookingRequest->venue;
        $text = "「{$venue->name}」への予約問い合わせを受け付けました。";
        if ($venue->phone) {
            $text .= " お急ぎの場合は直接お電話（{$venue->phone}）でのお問い合わせもご利用いただけます。";
        }

        LineMessaging::push($bookingRequest->lineUser->line_user_id, $text);
    }
}
