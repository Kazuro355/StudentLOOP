<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $type = array_key_exists($request->query('jenis'), UserNotification::TYPES) ? $request->query('jenis') : null;

        $notifications = $request->user()->appNotifications()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('notifications.index', ['notifications' => $notifications, 'type' => $type]);
    }

    /** Tandai dibaca lalu arahkan ke tujuan notifikasi. */
    public function open(Request $request, UserNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return redirect($notification->url ?: route('notifications.index'));
    }

    public function readAll(Request $request)
    {
        $request->user()->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai dibaca.');
    }
}
