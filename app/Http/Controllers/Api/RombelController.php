<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RombonganBelajar;
use Illuminate\Http\Request;

class RombelController extends Controller
{
    /**
     * GET /api/v1/rombel?tahun_ajaran_id=
     * Dipakai Perpus untuk mengisi dropdown "Kelas" setelah Tahun Ajaran dipilih,
     * dan dipakai CBT untuk sinkronisasi mirror rombel.
     */
    public function index(Request $r)
    {
        $items = RombonganBelajar::with(['jurusan:id,nama_jurusan,singkatan', 'tahunAjaran:id,kode_tahun_ajaran,nama_tahun_ajaran'])
            ->when($r->tahun_ajaran_id, fn ($q) => $q->where('tahun_ajaran_id', $r->tahun_ajaran_id))
            ->when($r->tingkat, fn ($q) => $q->where('tingkat', $r->tingkat))
            ->orderBy('tingkat')->orderBy('nama_rombel')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function show(RombonganBelajar $rombel)
    {
        $rombel->load(['jurusan', 'tahunAjaran', 'waliKelas:id,nama_ptk,nip']);

        return response()->json(['data' => $rombel]);
    }

    /**
     * GET /api/v1/rombel/{rombel}/siswa
     * Daftar siswa AKTIF di rombel ini pada tahun ajaran rombel tsb — dipakai
     * langsung oleh fitur "Tambah Anggota" Perpus (checkbox pilih siswa / pilih semua).
     */
    public function siswa(RombonganBelajar $rombel)
    {
        $siswa = $rombel->siswa()
            ->where('siswa.is_aktif', true)
            ->orderBy('nama_siswa')
            ->get(['siswa.id', 'siswa.nisn', 'siswa.nis', 'siswa.nama_siswa', 'siswa.jenis_kelamin',
                   'siswa.tanggal_lahir', 'siswa.alamat', 'siswa.nomor_hp', 'siswa.email']);

        return response()->json([
            'data' => $siswa,
            'rombel' => [
                'id' => $rombel->id,
                'nama_rombel' => $rombel->nama_rombel,
                'tingkat' => $rombel->tingkat,
                'jurusan' => optional($rombel->jurusan)->nama_jurusan,
                'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
            ],
        ]);
    }
}
