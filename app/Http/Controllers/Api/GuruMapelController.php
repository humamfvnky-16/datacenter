<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuruMapel;
use Illuminate\Http\Request;

class GuruMapelController extends Controller
{
    /**
     * GET /api/v1/guru-mapel?guru_id=&tahun_ajaran_id=
     * Dipakai CBT untuk sinkronisasi/refresh penugasan guru↔mapel↔rombel
     * (dipakai ScopedToGuruMapel untuk membatasi bank soal & tes per guru).
     */
    public function index(Request $r)
    {
        $items = GuruMapel::with(['guru:id,nip,nama_ptk', 'mapel:id,kode_mapel,nama_mapel', 'rombel:id,nama_rombel,tingkat'])
            ->when($r->guru_id, fn ($q) => $q->where('guru_id', $r->guru_id))
            ->when($r->tahun_ajaran_id, fn ($q) => $q->where('tahun_ajaran_id', $r->tahun_ajaran_id))
            ->get();

        return response()->json(['data' => $items]);
    }
}
