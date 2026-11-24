<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('wallet.index', [
            'user' => $user,
            'transactions' => DB::table('wallet_transactions')->where('user_id', $user->id)->latest()->limit(20)->get(),
            'withdrawals' => DB::table('withdrawals')->where('user_id', $user->id)->latest()->limit(20)->get(),
        ]);
    }

    public function updateBank(Request $request)
    {
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:50'],
            'bank_account_name' => ['required', 'string', 'max:100'],
            'bank_account_number' => ['required', 'string', 'max:50', 'regex:/^[0-9 -]+$/'],
        ], ['bank_account_number.regex' => 'Nomor rekening hanya boleh berisi angka, spasi, atau tanda strip.']);

        $request->user()->update($data);

        return back()->with('success', 'Data rekening penarikan diperbarui.');
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:10000'],
        ]);

        DB::transaction(function () use ($request, $data) {
            $user = \App\Models\User::query()->lockForUpdate()->findOrFail($request->user()->id);
            abort_unless($user->bank_name && $user->bank_account_name && $user->bank_account_number, 422, 'Lengkapi data rekening terlebih dahulu.');
            abort_if($data['amount'] > $user->balance, 422, 'Saldo tidak mencukupi.');

            $user->decrement('balance', $data['amount']);
            $reference = 'WD-' . strtoupper(Str::random(10));
            DB::table('withdrawals')->insert([
                'user_id' => $user->id,
                'amount' => $data['amount'],
                'bank_name' => $user->bank_name,
                'account_name' => $user->bank_account_name,
                'account_number' => $user->bank_account_number,
                'status' => 'pending',
                'reference' => $reference,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('wallet_transactions')->insert([
                'user_id' => $user->id,
                'order_id' => null,
                'type' => 'withdrawal',
                'status' => 'pending',
                'amount' => $data['amount'],
                'description' => 'Permintaan penarikan ke ' . $user->bank_name . ' ' . $user->bank_account_number,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Permintaan penarikan dikirim. Saldo dipotong dan menunggu proses demo transfer.');
    }
}
