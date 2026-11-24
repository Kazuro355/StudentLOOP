<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // @rp($angka) => Rp1.250.000
        Blade::directive('rp', fn ($expr) => "<?php echo 'Rp' . number_format((float) ({$expr}), 0, ',', '.'); ?>");

        // ID barang yang difavoritkan (dihitung sekali per request) untuk ikon hati di kartu barang.
        View::composer('partials.item-card', function ($view) {
            $request = request();
            if (! $request->attributes->has('fav_ids')) {
                $request->attributes->set('fav_ids', Auth::check() ? Auth::user()->favorites()->pluck('items.id')->all() : []);
            }
            $view->with('favIds', $request->attributes->get('fav_ids'));
        });

        // Badge angka di navigasi (notifikasi & pesan belum dibaca)
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            $view->with([
                'navUnreadNotifs' => $user ? $user->appNotifications()->whereNull('read_at')->count() : 0,
                'navUnreadMessages' => $user ? $user->unreadMessagesCount() : 0,
            ]);
        });
    }
}
