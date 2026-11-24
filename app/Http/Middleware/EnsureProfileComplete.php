<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengguna baru harus melengkapi profil (NIM, jurusan, kampus) sebelum memakai marketplace.
 * Tidak ada verifikasi email: cukup daftar dengan email aktif apa pun.
 */
class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isProfileComplete()) {
            return redirect()->route('profile.complete')
                ->with('info', 'Lengkapi profil Binusian kamu dulu ya sebelum mulai.');
        }

        return $next($request);
    }
}
