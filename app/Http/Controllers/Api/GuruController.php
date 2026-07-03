<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class GuruController extends Controller
{
    public const MAX_ATTEMPTS = 5;
    public const LOCK_MINUTES = 15;

    public function index(Request $r)
    {
        $items = Guru::query()
            ->when($r->q, function ($q) use ($r) {
                $q->where(function ($x) use ($r) {
                    $x->where('nama_ptk', 'like', "%{$r->q}%")
                      ->orWhere('nip', 'like', "%{$r->q}%");
                });
            })
            ->when($r->has('is_aktif'), fn ($q) => $q->where('is_aktif', $r->boolean('is_aktif')))
            ->orderBy('nama_ptk')
            ->paginate((int) $r->input('per_page', 100));

        return response()->json($items);
    }

    public function show(Guru $guru)
    {
        $guru->load('guruMapel.mapel', 'guruMapel.rombel');

        return response()->json(['data' => $guru]);
    }

    /**
     * POST /api/v1/auth/verify-guru {username, password}
     * Sama seperti verify-siswa: Data Center pemilik password guru, klien
     * (CBT) memanggil endpoint ini setiap kali guru login, tidak menyimpan
     * password guru secara lokal.
     */
    public function verify(Request $r)
    {
        $data = $r->validate([
            'username' => 'required|string|max:100', // nip
            'password' => 'required|string|max:100',
        ]);

        $guru = Guru::where('nip', $data['username'])->first();

        if (! $guru) {
            return response()->json(['message' => 'Kombinasi username dan password salah.'], 401);
        }

        if (! empty($guru->locked_until) && $guru->locked_until->isFuture()) {
            $menit = now()->diffInMinutes($guru->locked_until);
            return response()->json([
                'message' => "Akun dikunci sementara. Coba lagi dalam {$menit} menit.",
                'locked_until' => $guru->locked_until->toIso8601String(),
            ], 423);
        }

        $status = strtolower((string) ($guru->account_status ?? 'active'));
        if ($status !== 'active' || ! $guru->is_aktif) {
            return response()->json([
                'message' => 'Akun guru tidak aktif.',
                'account_status' => $status !== 'active' ? $status : 'inactive',
            ], 403);
        }

        if (! Hash::check($data['password'], (string) $guru->password)) {
            $count = (int) ($guru->failed_login_count ?? 0) + 1;
            $updates = ['failed_login_count' => $count];
            if ($count >= self::MAX_ATTEMPTS) {
                $updates['locked_until'] = now()->addMinutes(self::LOCK_MINUTES);
                $updates['failed_login_count'] = 0;
            }
            $guru->forceFill($updates)->save();

            return response()->json(['message' => 'Kombinasi username dan password salah.'], 401);
        }

        $guru->forceFill([
            'failed_login_count' => 0,
            'locked_until' => null,
            'last_seen_at' => now(),
        ])->save();

        $guru->load('guruMapel.mapel', 'guruMapel.rombel');

        return response()->json(['data' => $guru]);
    }

    /**
     * POST /api/v1/auth/change-password-guru {username, current_password, password}
     * Klien (CBT) memproksikan ganti password guru ke sini — password guru
     * hanya pernah disimpan di Data Center.
     */
    public function changePassword(Request $r)
    {
        $data = $r->validate([
            'username' => 'required|string|max:100',
            'current_password' => 'required|string|max:100',
            'password' => 'required|string|min:6|max:100',
        ]);

        $guru = Guru::where('nip', $data['username'])->first();
        if (! $guru || ! Hash::check($data['current_password'], (string) $guru->password)) {
            return response()->json(['message' => 'Password saat ini salah.'], 422);
        }

        $guru->forceFill(['password' => $data['password']])->save();

        return response()->json(['message' => 'Password berhasil diperbarui.']);
    }
}
