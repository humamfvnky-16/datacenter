<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\MataPelajaran;
use App\Models\RombonganBelajar;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'siswa'   => Siswa::count(),
            'guru'    => Guru::count(),
            'mapel'   => MataPelajaran::count(),
            'jurusan' => Jurusan::count(),
            'rombel'  => RombonganBelajar::count(),
        ];

        return view('dashboard.admin', [
            'stats' => $stats,
            'sekolah' => Sekolah::first(),
            'tahunAjaranAktif' => TahunAjaran::where('is_aktif', true)->first(),
        ]);
    }
}
