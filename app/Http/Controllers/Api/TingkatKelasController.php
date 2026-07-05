<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TingkatKelas;

/**
 * GET /api/v1/tingkat-kelas
 * Master tingkat kelas (Data Center = sumber tunggal). Dipakai CBT (dan aplikasi
 * klien lain) untuk sinkronisasi mirror tingkat kelas via `datacenter:sync`.
 */
class TingkatKelasController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => TingkatKelas::orderBy('urutan')->orderBy('nomor')->get(),
        ]);
    }
}
