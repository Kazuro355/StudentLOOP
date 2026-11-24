<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /** Layar "Lengkapi profil" setelah daftar. */
    public function complete(Request $request)
    {
        if ($request->user()->isProfileComplete()) {
            return redirect()->route('beranda');
        }
        return view('profile.form', ['user' => $request->user(), 'mode' => 'complete']);
    }

    public function storeComplete(Request $request)
    {
        $request->user()->update($this->validated($request) + ['profile_completed_at' => now()]);

        return redirect()->route('beranda')->with('success', 'Profil lengkap! Selamat datang di StudentLOOP.');
    }

    public function edit(Request $request)
    {
        return view('profile.form', ['user' => $request->user(), 'mode' => 'edit']);
    }

    public function update(Request $request)
    {
        $request->user()->update($this->validated($request));

        return redirect()->route('profile.show')->with('success', 'Profil diperbarui.');
    }

    public function show(Request $request)
    {
        $user = $request->user();

        return view('profile.show', [
            'user' => $user,
            'stats' => [
                'barang' => $user->items()->count(),
                'transaksi' => Order::where('buyer_id', $user->id)->orWhere('seller_id', $user->id)->count(),
                'rating' => $user->ratingAvg(),
            ],
        ]);
    }

    public function publicProfile(Request $request, User $user)
    {
        abort_if(in_array($user->id, $request->user()->blockedIds(), true), 404);

        return view('profile.public', [
            'user' => $user,
            'items' => $user->items()->browsable()->with(['photos', 'category'])->latest()->limit(8)->get(),
            'reviews' => $user->reviewsReceived()->with(['reviewer', 'item'])->latest()->limit(10)->get(),
            'isBlocked' => $request->user()->hasBlocked($user),
        ]);
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'min:8', 'confirmed'],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini salah.',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Kata sandi diubah.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'nim' => ['required', 'string', 'max:20', Rule::unique('users', 'nim')->ignore($request->user()->id)],
            'jurusan' => ['required', 'string', 'max:80'],
            'angkatan' => ['required', 'digits:4'],
            'kampus' => ['required', Rule::in(Item::CAMPUSES)],
            'phone' => ['nullable', 'string', 'max:20'],
            'area' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:300'],
        ]);
    }
}
