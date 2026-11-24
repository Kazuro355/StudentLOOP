<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemQuestion;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function store(Request $request, Item $item)
    {
        abort_if(in_array($item->user_id, $request->user()->blockedIds(), true), 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:500']]);
        $item->questions()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        if ($item->user_id !== $request->user()->id) {
            UserNotification::createFor(
                $item->user_id, 'transaksi', 'Pertanyaan baru untuk ' . $item->title,
                $request->user()->firstName() . ': ' . $data['body'],
                route('items.show', $item) . '#tanya'
            );
        }

        return redirect(route('items.show', $item) . '#tanya')->with('success', 'Pertanyaan terkirim.');
    }

    public function answer(Request $request, ItemQuestion $question)
    {
        abort_unless($question->item->user_id === $request->user()->id, 403);

        $data = $request->validate(['answer' => ['required', 'string', 'max:500']]);
        $question->update(['answer' => $data['answer'], 'answered_at' => now()]);

        UserNotification::createFor(
            $question->user_id, 'transaksi', 'Pertanyaanmu dijawab',
            $question->item->title . ': ' . $data['answer'],
            route('items.show', $question->item) . '#tanya'
        );

        return redirect(route('items.show', $question->item) . '#tanya')->with('success', 'Jawaban terkirim.');
    }
}
