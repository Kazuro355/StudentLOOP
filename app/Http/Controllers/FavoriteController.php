<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $items = $request->user()->favorites()
            ->with(['photos', 'category', 'owner'])
            ->withCount('favoritedBy')
            ->latest('favorites.created_at')
            ->paginate(12);

        return view('items.favorites', ['items' => $items, 'mode' => 'semua']);
    }

    public function toggle(Request $request, Item $item)
    {
        $result = $request->user()->favorites()->toggle($item->id);
        $added = ! empty($result['attached']);

        return back()->with('success', $added ? 'Disimpan ke favorit.' : 'Dihapus dari favorit.');
    }
}
