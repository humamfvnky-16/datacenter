<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);

        // Rate limiter default untuk routes/api.php (dipakai $middleware->throttleApi())
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Bagikan pengaturan aplikasi ke semua view
        View::composer('*', function ($view) {
            // Skip jika tabel belum migrate (mis. saat install)
            if (! Schema::hasTable('app_settings')) {
                $view->with('AppCfg', $this->defaults());
                return;
            }
            $view->with('AppCfg', [
                'app_name'       => AppSetting::get('app_name', config('app.name')),
                'app_tagline'    => AppSetting::get('app_tagline', 'Sistem Informasi Data Induk Sekolah'),
                'theme_color'    => AppSetting::get('theme_color', '#0d9488'),
                'logo'           => AppSetting::get('logo'),
                'favicon'        => AppSetting::get('favicon'),
                'login_bg'       => AppSetting::get('login_bg'),
                'login_title'    => AppSetting::get('login_title', 'Selamat datang di Data Center Sekolah.'),
                'login_subtitle' => AppSetting::get('login_subtitle', 'Kelola data sekolah, guru, siswa, dan kelas dalam satu sumber data terpusat.'),
                'footer_text'    => AppSetting::get('footer_text'),
            ]);
        });
    }

    protected function defaults(): array
    {
        return [
            'app_name' => config('app.name'),
            'app_tagline' => 'Sistem Informasi Data Induk Sekolah',
            'theme_color' => '#0d9488',
            'logo' => null, 'favicon' => null, 'login_bg' => null,
            'login_title' => 'Selamat datang di Data Center Sekolah.',
            'login_subtitle' => 'Kelola data sekolah, guru, siswa, dan kelas dalam satu sumber data terpusat.',
            'footer_text' => null,
        ];
    }
}
