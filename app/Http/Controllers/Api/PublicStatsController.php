<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Siswa;

/**
 * Statistik agregat (jumlah siswa/guru) — TANPA autentikasi, dipakai landing
 * page publik masing-masing sekolah untuk menampilkan "dipercaya X siswa &
 * guru". Sengaja hanya mengembalikan angka, bukan data individu, supaya aman
 * diakses tanpa token.
 */
class PublicStatsController extends Controller
{
    public function show()
    {
        return response()->json([
            'data' => [
                'siswa' => Siswa::where('is_aktif', true)->count(),
                'guru'  => Guru::where('is_aktif', true)->count(),
            ],
        ]);
    }
}
