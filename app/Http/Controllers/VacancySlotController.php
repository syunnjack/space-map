<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Support\ContentModeration;
use Illuminate\Http\Request;

class VacancySlotController extends Controller
{
    public function store(Request $request, Venue $venue)
    {
        if (! empty($request->input('website'))) {
            return back()->with('success', '投稿を受け付けました。');
        }

        $validated = $request->validate([
            'available_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'comment' => 'nullable|string|max:1000',
            'nickname' => 'nullable|string|max:30',
        ]);

        if (! empty($validated['comment']) && ContentModeration::containsNgWord($validated['comment'])) {
            return back()->withErrors(['comment' => '投稿内容に使用できない文字列が含まれています。'])->withInput();
        }

        $ipHash = ContentModeration::clientIpHash($request);
        if (ContentModeration::isTooSoon("vacancy-slot:{$venue->id}:{$ipHash}", 30)) {
            return back()->withErrors(['available_date' => '投稿間隔が短すぎます。しばらく待ってから再度お試しください。'])->withInput();
        }

        $venue->vacancySlots()->create([
            'available_date' => $validated['available_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'comment' => $validated['comment'] ?? null,
            'nickname' => ($validated['nickname'] ?? '') !== '' ? $validated['nickname'] : '匿名',
            'ip_hash' => $ipHash,
        ]);

        return back()->with('success', '空き枠を投稿しました。ありがとうございます。');
    }
}
