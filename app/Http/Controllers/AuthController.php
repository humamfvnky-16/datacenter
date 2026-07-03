<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public const MAX_ATTEMPTS = 5;
    public const LOCK_MINUTES = 15;

    public function showLogin(Request $request)
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('dashboard');
        }

        // Pastikan halaman login TIDAK di-cache oleh browser (penyebab 419 di mobile)
        // dan kunci CSRF selalu segar tiap akses.
        if (! $request->session()->has('_token')) {
            $request->session()->regenerateToken();
        }

        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:100',
        ]);

        $remember = (bool) $request->boolean('remember');
        $guard = 'admin';

        // ---- IP-based rate limit (anti brute force) ----
        $rateKey = 'login:'.$request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            $seconds = RateLimiter::availableIn($rateKey);
            throw ValidationException::withMessages([
                'username' => "Terlalu banyak percobaan dari IP ini. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        // ---- Cek akun apakah sudah terkunci ----
        $existing = User::where('email', $data['username'])->first();
        if ($existing && ! empty($existing->locked_until) && $existing->locked_until > now()) {
            $this->logAttempt($request, $data, false);
            $minutes = now()->diffInMinutes($existing->locked_until);
            throw ValidationException::withMessages([
                'username' => "Akun dikunci sementara. Coba lagi dalam {$minutes} menit.",
            ]);
        }

        $credentials = ['email' => $data['username'], 'password' => $data['password']];
        $ok = Auth::guard($guard)->attempt($credentials, $remember);

        $this->logAttempt($request, $data, $ok);
        RateLimiter::hit($rateKey, 60 * 10);

        if (! $ok) {
            if ($existing) {
                $count = (int) ($existing->failed_login_count ?? 0) + 1;
                $updates = ['failed_login_count' => $count];

                if ($count >= self::MAX_ATTEMPTS) {
                    $updates['locked_until'] = now()->addMinutes(self::LOCK_MINUTES);
                    $updates['failed_login_count'] = 0;
                }
                DB::table($existing->getTable())
                    ->where($existing->getKeyName(), $existing->getKey())
                    ->update($updates);
            }

            throw ValidationException::withMessages([
                'username' => 'Kombinasi username dan password salah.',
            ]);
        }

        // ---- Sukses ----
        $user = Auth::guard($guard)->user();

        // Cek status akun
        $status = strtolower((string) ($user->account_status ?? 'active'));
        if ($status !== 'active') {
            Auth::guard($guard)->logout();
            return redirect()->route('account.'.$status);
        }

        // Reset failed_login_count
        DB::table($user->getTable())
            ->where($user->getKeyName(), $user->getKey())
            ->update(['failed_login_count' => 0, 'locked_until' => null, 'last_seen_at' => now()]);

        RateLimiter::clear($rateKey);
        $request->session()->regenerate();

        // Jika OTP aktif, hapus tanda verifikasi sebelumnya supaya wajib verify
        if (! empty($user->otp_enabled)) {
            session()->forget('otp_verified_at');
            return redirect()->route('otp.show');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        session()->forget('otp_verified_at');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function logAttempt(Request $r, array $data, bool $success): void
    {
        $ua = (string) $r->userAgent();
        $parsed = $this->parseUserAgent($ua);

        $attemptNo = LoginAttempt::where('username', $data['username'])
            ->whereDate('created_at', today())->count() + 1;

        LoginAttempt::create([
            'username'   => $data['username'],
            'guard'      => 'admin',
            'success'    => $success,
            'ip_address' => $r->ip(),
            'user_agent' => substr($ua, 0, 500),
            'device_type'=> $parsed['device'],
            'browser'    => $parsed['browser'],
            'os'         => $parsed['os'],
            'attempt_no' => $attemptNo,
        ]);
    }

    /** Parser sederhana user agent → device/browser/OS */
    protected function parseUserAgent(string $ua): array
    {
        $device = 'desktop';
        if (preg_match('/iPad|Tablet/i', $ua)) $device = 'tablet';
        elseif (preg_match('/Mobi|Android|iPhone|iPod|BlackBerry|Opera Mini/i', $ua)) $device = 'mobile';

        $browser = 'Other';
        if (preg_match('/Edg\//i', $ua)) $browser = 'Edge';
        elseif (preg_match('/Chrome\//i', $ua)) $browser = 'Chrome';
        elseif (preg_match('/Firefox\//i', $ua)) $browser = 'Firefox';
        elseif (preg_match('/Safari\//i', $ua) && ! preg_match('/Chrome|Edg/i', $ua)) $browser = 'Safari';
        elseif (preg_match('/OPR\/|Opera/i', $ua)) $browser = 'Opera';

        $os = 'Other';
        if (preg_match('/Windows NT 10/i', $ua)) $os = 'Windows 10/11';
        elseif (preg_match('/Windows NT/i', $ua)) $os = 'Windows';
        elseif (preg_match('/Mac OS X/i', $ua)) $os = 'macOS';
        elseif (preg_match('/Android/i', $ua)) $os = 'Android';
        elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) $os = 'iOS';
        elseif (preg_match('/Linux/i', $ua)) $os = 'Linux';

        return compact('device', 'browser', 'os');
    }
}
