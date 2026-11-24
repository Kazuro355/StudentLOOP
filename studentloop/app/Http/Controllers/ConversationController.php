<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /** Layar "Pesan": daftar percakapan. */
    public function index(Request $request)
    {
        return view('conversations.index', [
            'conversations' => $this->listFor($request->user()),
            'current' => null,
            'messages' => collect(),
        ]);
    }

    /** Layar "Chat": satu percakapan (di desktop tampil berdampingan dengan daftar). */
    public function show(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        abort_unless($conversation->has($user), 403);

        $conversation->load(['item.photos', 'userOne', 'userTwo']);
        $other = $conversation->other($user);
        abort_if(in_array($other->id, $user->blockedIds(), true), 403, 'Percakapan ini tidak tersedia.');

        $conversation->messages()->whereNull('read_at')->where('sender_id', '!=', $user->id)->update(['read_at' => now()]);

        return view('conversations.index', [
            'conversations' => $this->listFor($user),
            'current' => $conversation,
            'other' => $other,
            'messages' => $conversation->messages()->get(),
        ]);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        abort_unless($conversation->has($user), 403);
        abort_if(in_array($conversation->other($user)->id, $user->blockedIds(), true), 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $conversation->messages()->create(['sender_id' => $user->id, 'body' => trim($data['body'])]);
        $conversation->update(['last_message_at' => now()]);

        return redirect()->route('conversations.show', $conversation);
    }

    /** "Chat pemilik" dari halaman barang. */
    public function startForItem(Request $request, Item $item)
    {
        $user = $request->user();
        abort_if($item->user_id === $user->id, 403, 'Ini barangmu sendiri.');
        abort_if(in_array($item->user_id, $user->blockedIds(), true), 404);

        return redirect()->route('conversations.show', $this->findOrCreate($user->id, $item->user_id, $item->id));
    }

    public function startWithUser(Request $request, User $user)
    {
        $me = $request->user();
        abort_if($user->id === $me->id, 403);
        abort_if(in_array($user->id, $me->blockedIds(), true), 404);

        return redirect()->route('conversations.show', $this->findOrCreate($me->id, $user->id, null));
    }

    /* ---------- helpers ---------- */

    private function listFor(User $user)
    {
        $blocked = $user->blockedIds();

        return Conversation::with(['userOne', 'userTwo', 'item', 'lastMessage'])
            ->where(fn ($q) => $q->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id))
            ->whereHas('messages')
            ->when($blocked, fn ($q) => $q->whereNotIn('user_one_id', $blocked)->whereNotIn('user_two_id', $blocked))
            ->orderByDesc('last_message_at')
            ->get();
    }

    private function findOrCreate(int $me, int $other, ?int $itemId): Conversation
    {
        $existing = Conversation::query()
            ->where('item_id', $itemId)
            ->where(fn ($q) => $q
                ->where(fn ($w) => $w->where('user_one_id', $me)->where('user_two_id', $other))
                ->orWhere(fn ($w) => $w->where('user_one_id', $other)->where('user_two_id', $me)))
            ->first();

        return $existing ?: Conversation::create(['item_id' => $itemId, 'user_one_id' => $me, 'user_two_id' => $other]);
    }
}
