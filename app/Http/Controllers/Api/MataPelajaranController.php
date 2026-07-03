<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MataPelajaran;
use Illuminate\Http\Request;

class MataPelajaranController extends Controller
{
    public function index(Request $r)
    {
        $items = MataPelajaran::query()
            ->where('is_aktif', true)
            ->when($r->jurusan_id, fn ($q) => $q->where('jurusan_id', $r->jurusan_id))
            ->when($r->tingkat, fn ($q) => $q->where('tingkat', $r->tingkat))
            ->orderBy('nama_mapel')
            ->get();

        return response()->json(['data' => $items]);
    }
}
