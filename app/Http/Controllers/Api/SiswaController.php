<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SiswaController extends Controller
{
    public const MAX_ATTEMPTS = 5;
    public const LOCK_MINUTES = 15;

    /**
     * GET /api/v1/siswa?rombel_id=&tahun_ajaran_id=&q=
     * Dipakai proses sinkronisasi mirror CBT (datacenter:sync) & pencarian umum.
     */
    public function index(Request $r)
    {
        $items = Siswa::with(['rombelSekarang.rombel'])
            ->when($r->rombel_id, function ($q) use ($r) {
                $q->whereHas('siswaRombel', fn ($x) => $x->where('rombongan_belajar_id', $r->rombel_id));
            })
            ->when($r->tahun_ajaran_id, function ($q) use ($r) {
                $q->whereHas('siswaRombel', fn ($x) => $x->where('tahun_ajaran_id', $r->tahun_ajaran_id));
            })
            ->when($r->q, function ($q) use ($r) {
                $q->where(function ($x) use ($r) {
                    $x->where('nama_siswa', 'like', "%{$r->q}%")
                      ->orWhere('nisn', 'like', "%{$r->q}%")
                      ->orWhere('nis', 'like', "%{$r->q}%");
                });
            })
            ->orderBy('nama_siswa')
            ->paginate((int) $r->input('per_page', 100));

        return response()->json($items);
    }

    public function show(Siswa $siswa)
    {
        $siswa->load('rombelSekarang.rombel');

        return response()->json(['data' => $siswa]);
    }

    /**
     * POST /api/v1/auth/verify-siswa {username, password}
     * Data Center adalah pemilik password asli — aplikasi klien (CBT) TIDAK
     * pernah menyimpan/membandingkan password siswa secara lokal, cukup
     * panggil endpoint ini setiap kali siswa login.
     */
    public function verify(Request $r)
    {
        $data = $r->validate([
            'username' => 'required|string|max:100', // nisn
            'password' => 'required|string|max:100',
        ]);

        $siswa = Siswa::where('nisn', $data['username'])->first();

        if (! $siswa) {
            return response()->json(['message' => 'Kombinasi username dan password salah.'], 401);
        }

        if (! empty($siswa->locked_until) && $siswa->locked_until->isFuture()) {
            $menit = now()->diffInMinutes($siswa->locked_until);
            return response()->json([
                'message' => "Akun dikunci sementara. Coba lagi dalam {$menit} menit.",
                'locked_until' => $siswa->locked_until->toIso8601String(),
            ], 423);
        }

        $status = strtolower((string) ($siswa->account_status ?? 'active'));
        if ($status !== 'active' || ! $siswa->is_aktif) {
            return response()->json([
                'message' => 'Akun siswa tidak aktif.',
                'account_status' => $status !== 'active' ? $status : 'inactive',
            ], 403);
        }

        if (! Hash::check($data['password'], (string) $siswa->password)) {
            $count = (int) ($siswa->failed_login_count ?? 0) + 1;
            $updates = ['failed_login_count' => $count];
            if ($count >= self::MAX_ATTEMPTS) {
                $updates['locked_until'] = now()->addMinutes(self::LOCK_MINUTES);
                $updates['failed_login_count'] = 0;
            }
            $siswa->forceFill($updates)->save();

            return response()->json(['message' => 'Kombinasi username dan password salah.'], 401);
        }

        $siswa->forceFill([
            'failed_login_count' => 0,
            'locked_until' => null,
            'last_seen_at' => now(),
        ])->save();

        $siswa->load('rombelSekarang.rombel');

        return response()->json(['data' => $siswa]);
    }

    /**
     * POST /api/v1/auth/change-password-siswa {username, current_password, password}
     * Klien (CBT) memproksikan ganti password siswa ke sini — password siswa
     * hanya pernah disimpan di Data Center.
     */
    public function changePassword(Request $r)
    {
        $data = $r->validate([
            'username' => 'required|string|max:100',
            'current_password' => 'required|string|max:100',
            'password' => 'required|string|min:6|max:100',
        ]);

        $siswa = Siswa::where('nisn', $data['username'])->first();
        if (! $siswa || ! Hash::check($data['current_password'], (string) $siswa->password)) {
            return response()->json(['message' => 'Password saat ini salah.'], 422);
        }

        $siswa->forceFill(['password' => $data['password']])->save();

        return response()->json(['message' => 'Password berhasil diperbarui.']);
    }
}
