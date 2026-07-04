<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Sekolah;
use App\Models\Siswa;
use Illuminate\Support\Facades\Storage;

/**
 * Endpoint publik (TANPA autentikasi) yang dipakai aplikasi lain dalam satu
 * ekosistem sekolah:
 *  - stats    : jumlah siswa/guru untuk landing page.
 *  - branding : identitas sekolah (nama + logo) sebagai SUMBER TUNGGAL, dipakai
 *               CBT & landing-page supaya logo/nama seragam di semua aplikasi.
 * Sengaja hanya mengembalikan data non-sensitif supaya aman tanpa token.
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

    /**
     * Branding sekolah dari Profil Sekolah (model Sekolah). Logo dipakai
     * sekaligus sebagai favicon di aplikasi konsumen. URL logo dikembalikan
     * absolut (mengikuti APP_URL, mis. .../datacenter/storage/...).
     */
    public function branding()
    {
        $sekolah = Sekolah::first();
        $logoUrl = ($sekolah && $sekolah->logo)
            ? Storage::disk('public')->url($sekolah->logo)
            : null;

        return response()->json([
            'data' => [
                'school_name' => $sekolah->nama_sekolah ?? config('app.name'),
                'logo'        => $logoUrl,
                'favicon'     => $logoUrl, // favicon = logo yang sama
            ],
        ]);
    }
}
