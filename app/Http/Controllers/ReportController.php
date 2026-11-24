<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    private const TARGETS = ['item' => Item::class, 'user' => User::class, 'order' => Order::class];

    /** Layar "Laporkan & keamanan". */
    public function create(Request $request)
    {
        $type = $request->query('type');
        $target = null;

        if (isset(self::TARGETS[$type])) {
            $target = self::TARGETS[$type]::find($request->query('id'));
            if ($target instanceof Order) {
                abort_unless($target->partyOf($request->user()), 403);
            }
        }

        return view('reports.create', [
            'type' => $target ? $type : null,
            'target' => $target,
            'blocked' => $request->user()->blockedUsers()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'target_type' => ['required', Rule::in(array_keys(self::TARGETS))],
            'target_id' => ['required', 'integer'],
            'reason' => ['required', Rule::in(Report::REASONS)],
            'details' => ['nullable', 'string', 'max:1000'],
        ], ['reason.required' => 'Pilih alasan laporan.']);

        $target = self::TARGETS[$data['target_type']]::findOrFail($data['target_id']);
        if ($target instanceof Order) {
            abort_unless($target->partyOf($request->user()), 403);
        }

        Report::create($data + ['reporter_id' => $request->user()->id]);

        return redirect()->route('beranda')->with('success', 'Laporan diterima. Tim StudentLOOP akan meninjaunya.');
    }

    public function block(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422);

        $request->user()->blockedUsers()->syncWithoutDetaching([$user->id]);

        return redirect()->route('beranda')->with('success', $user->name . ' diblokir. Kalian tidak akan saling melihat barang dan pesan.');
    }

    public function unblock(Request $request, User $user)
    {
        $request->user()->blockedUsers()->detach($user->id);

        return back()->with('success', 'Blokir dibuka.');
    }
}
