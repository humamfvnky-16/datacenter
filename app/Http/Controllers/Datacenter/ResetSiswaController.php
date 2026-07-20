<?php

namespace App\Http\Controllers\Datacenter;

use App\Http\Controllers\Controller;
use App\Models\RombonganBelajar;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\TingkatKelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul ultra-sensitif: hapus permanen data induk siswa (beserta siswa_rombel
 * & riwayat_periodikal terkait via cascadeOnDelete), berbasis 3 cakupan:
 * per tingkat kelas, per rombel, atau satu siswa. Dilindungi middleware
 * `admin` (bukan sekadar role:admin) & wajib mengetik "HAPUS" sebagai konfirmasi.
 */
class ResetSiswaController extends Controller
{
    public function index(Request $r)
    {
        $tingkatList = TingkatKelas::aktif()->orderBy('urutan')->orderBy('nomor')->get();
        $rombelList = RombonganBelajar::whereHas('tahunAjaran', fn ($q) => $q->where('is_aktif', true))
            ->orderBy('tingkat')->orderBy('nama_rombel')->get();
        $tahunAktif = TahunAjaran::where('is_aktif', true)->first();

        $siswaTingkat = null;
        if ($r->filled('tingkat')) {
            $siswaTingkat = $this->siswaByTingkat($r->integer('tingkat'))->orderBy('nama_siswa')->get();
        }

        $siswaRombel = null;
        if ($r->filled('rombel')) {
            $siswaRombel = $this->siswaByRombel($r->integer('rombel'))->orderBy('nama_siswa')->get();
        }

        $siswaHasil = null;
        if ($r->filled('q')) {
            $siswaHasil = Siswa::with('rombelSekarang.rombel')
                ->where(function ($x) use ($r) {
                    $x->where('nama_siswa', 'like', "%{$r->q}%")
                      ->orWhere('nisn', 'like', "%{$r->q}%")
                      ->orWhere('nis', 'like', "%{$r->q}%");
                })
                ->orderBy('nama_siswa')->limit(25)->get();
        }

        $totalSiswa = Siswa::count();

        return view('datacenter.reset-siswa.index', compact(
            'tingkatList', 'rombelList', 'tahunAktif', 'siswaTingkat', 'siswaRombel', 'siswaHasil', 'totalSiswa'
        ));
    }

    public function perTingkat(Request $r)
    {
        $data = $r->validate([
            'tingkat' => 'required|integer',
            'konfirmasi' => 'required|in:HAPUS',
        ]);

        $count = DB::transaction(function () use ($data) {
            $ids = $this->siswaByTingkat($data['tingkat'])->pluck('siswa.id');
            Siswa::whereIn('id', $ids)->delete();
            return $ids->count();
        });

        return redirect()->route('reset-siswa.index')
            ->with('success', "{$count} data siswa pada tingkat kelas ini berhasil dihapus permanen.");
    }

    public function perRombel(Request $r)
    {
        $data = $r->validate([
            'rombel' => 'required|integer|exists:rombongan_belajar,id',
            'konfirmasi' => 'required|in:HAPUS',
        ]);

        $count = DB::transaction(function () use ($data) {
            $ids = $this->siswaByRombel($data['rombel'])->pluck('siswa.id');
            Siswa::whereIn('id', $ids)->delete();
            return $ids->count();
        });

        return redirect()->route('reset-siswa.index')
            ->with('success', "{$count} data siswa pada rombel ini berhasil dihapus permanen.");
    }

    public function perSiswa(Request $r)
    {
        $data = $r->validate([
            'siswa_id' => 'required|integer|exists:siswa,id',
            'konfirmasi' => 'required|in:HAPUS',
        ]);

        $siswa = Siswa::findOrFail($data['siswa_id']);
        $nama = $siswa->nama_siswa;
        $siswa->delete();

        return redirect()->route('reset-siswa.index')
            ->with('success', "Data siswa {$nama} berhasil dihapus permanen.");
    }

    /** Hapus SELURUH data induk siswa. Frasa konfirmasi sengaja dibuat beda & lebih panjang dari cakupan lain. */
    public function semua(Request $r)
    {
        $data = $r->validate([
            'konfirmasi' => 'required|in:HAPUS SEMUA',
        ]);

        $count = DB::transaction(function () {
            $count = Siswa::count();
            Siswa::query()->delete();
            return $count;
        });

        return redirect()->route('reset-siswa.index')
            ->with('success', "Seluruh data siswa ({$count} siswa) berhasil dihapus permanen.");
    }

    /** Siswa yg penempatan rombelnya (tahun ajaran aktif) berada di tingkat tertentu. */
    protected function siswaByTingkat(int $tingkat)
    {
        return Siswa::query()
            ->whereHas('siswaRombel', function ($q) use ($tingkat) {
                $q->whereHas('tahunAjaran', fn ($qa) => $qa->where('is_aktif', true))
                  ->whereHas('rombel', fn ($qr) => $qr->where('tingkat', $tingkat));
            });
    }

    /** Siswa yg saat ini tercatat di rombel tertentu (lintas tahun ajaran rombel itu). */
    protected function siswaByRombel(int $rombelId)
    {
        return Siswa::query()
            ->whereHas('siswaRombel', fn ($q) => $q->where('rombongan_belajar_id', $rombelId));
    }
}
