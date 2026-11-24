<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function create(Request $request, Order $order)
    {
        $user = $request->user();
        abort_unless($order->partyOf($user), 403);
        abort_unless($order->status === 'selesai', 404);

        if ($order->reviews()->where('reviewer_id', $user->id)->exists()) {
            return redirect()->route('orders.show', $order)->with('info', 'Kamu sudah memberi ulasan untuk transaksi ini.');
        }

        return view('reviews.create', [
            'order' => $order->load('item.photos'),
            'reviewee' => $order->otherParty($user),
        ]);
    }

    public function store(Request $request, Order $order)
    {
        $user = $request->user();
        abort_unless($order->partyOf($user), 403);
        abort_unless($order->status === 'selesai', 422);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ], ['rating.required' => 'Pilih rating bintang 1–5.']);

        $reviewee = $order->otherParty($user);

        // unique(order_id, reviewer_id) menjaga satu ulasan per pihak.
        $review = Review::firstOrCreate(
            ['order_id' => $order->id, 'reviewer_id' => $user->id],
            ['reviewee_id' => $reviewee->id, 'item_id' => $order->item_id, 'rating' => $data['rating'], 'comment' => $data['comment'] ?? null]
        );

        if ($review->wasRecentlyCreated) {
            UserNotification::createFor($reviewee, 'ulasan', 'Ulasan baru', $user->firstName() . ' memberi ' . $data['rating'] . '★ untuk ' . $order->item->title . '.', route('users.show', $reviewee));
        }

        return redirect()->route('orders.show', $order)->with('success', 'Terima kasih, ulasanmu tersimpan.');
    }
}
