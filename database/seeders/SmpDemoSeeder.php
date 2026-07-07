<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\GuruMapel;
use App\Models\MataPelajaran;
use App\Models\RombonganBelajar;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\SiswaRombel;
use App\Models\TahunAjaran;
use App\Models\TingkatKelas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data demo lengkap untuk jenjang SMP (7-9, tanpa jurusan), mengisi setiap
 * menu Data Center: Tahun Ajaran, Mata Pelajaran, Tingkat Kelas, Rombongan
 * Belajar, Data Guru, Guru Mapel, Data Siswa.
 *
 * Sengaja dibuat 2 tahun ajaran (lalu + aktif) dengan tahun ajaran aktif HANYA
 * berisi rombel kelas 7 (siswa baru) -- persis kondisi sekolah yang baru mulai
 * tahun ajaran baru dan BELUM menaikkan kelas siswa lama. Ini supaya modul
 * Administrasi Periodikal (Proses Semua Siswa / Per Rombel / Koreksi) punya
 * data nyata untuk dipraktikkan: naik kelas 7->8 dan 8->9, plus kelulusan 9.
 *
 * Seeder demo/dev -- JANGAN dijalankan di database produksi berisi data asli.
 * Untuk hasil bersih (tanpa sisa data SMA lama), jalankan di database kosong:
 *     php artisan migrate:fresh --seed
 */
class SmpDemoSeeder extends Seeder
{
    /** @var array<int, array{nip:string,nama:string,gender:string,jabatan:string,status:string}> */
    private array $guruData = [
        ['nip' => '196504121990031005', 'nama' => 'Drs. Bambang Sutrisno, M.Pd.', 'gender' => 'L', 'jabatan' => 'Wakil Kepala Sekolah', 'status' => 'PNS'],
        ['nip' => '197203151998022001', 'nama' => 'Siti Nurhaliza, S.Pd.',        'gender' => 'P', 'jabatan' => 'Guru',                  'status' => 'PNS'],
        ['nip' => '198009102006041003', 'nama' => 'Ahmad Fauzan, S.Pd.I.',        'gender' => 'L', 'jabatan' => 'Guru',                  'status' => 'PNS'],
        ['nip' => '198306252009022004', 'nama' => 'Dewi Kartika, S.Pd.',          'gender' => 'P', 'jabatan' => 'Guru',                  'status' => 'PNS'],
        ['nip' => '199001202015041002', 'nama' => 'Rudi Hermawan, S.Pd.',         'gender' => 'L', 'jabatan' => 'Guru',                  'status' => 'PPPK'],
        ['nip' => '197911302008012006', 'nama' => 'Ratna Sari Dewi, S.Pd.',       'gender' => 'P', 'jabatan' => 'Guru',                  'status' => 'PNS'],
        ['nip' => '199205172019031008', 'nama' => 'Yusuf Maulana, S.Pd.',         'gender' => 'L', 'jabatan' => 'Guru',                  'status' => 'Honorer'],
        ['nip' => '198412082010012003', 'nama' => 'Indah Permatasari, S.Pd.',     'gender' => 'P', 'jabatan' => 'Guru',                  'status' => 'PNS'],
        ['nip' => '198807142016041007', 'nama' => 'Eko Prasetyo, S.Pd.',          'gender' => 'L', 'jabatan' => 'Guru',                  'status' => 'PPPK'],
        ['nip' => '199310252020122005', 'nama' => 'Wulan Andriani, S.Pd.',        'gender' => 'P', 'jabatan' => 'Guru',                  'status' => 'Honorer'],
        ['nip' => '199107082018121004', 'nama' => 'Hendra Gunawan, S.Kom.',       'gender' => 'L', 'jabatan' => 'Guru',                  'status' => 'Honorer'],
        ['nip' => '198601192011012002', 'nama' => 'Fitri Handayani, S.Pd.',       'gender' => 'P', 'jabatan' => 'Guru',                  'status' => 'PNS'],
    ];

    /** Penugasan per mapel: kode => daftar [index guru di $guruData, tingkat yang diajar]. */
    private array $penugasan = [
        'PPKN'  => [[0, [7, 8, 9]]],
        'BIN'   => [[1, [7, 8]], [7, [9]]],
        'PAIBP' => [[2, [7, 8, 9]]],
        'IPA'   => [[3, [7, 8]], [7, [9]]],
        'MTK'   => [[4, [7]], [8, [8, 9]]],
        'IPS'   => [[5, [7, 8, 9]]],
        'PJOK'  => [[6, [7, 8, 9]]],
        'BING'  => [[9, [7, 8, 9]]],
        'INF'   => [[10, [7, 8, 9]]],
        'SBD'   => [[11, [7, 8, 9]]],
        'MULOK' => [[11, [7, 8, 9]]],
    ];

    /** Wali kelas per tingkat & urutan rombel (0,1,2 -> "-1","-2","-3") -> index guru di $guruData. */
    private array $waliKelas = [
        7 => [2, 1, 3],
        8 => [4, 5, 6],
        9 => [7, 8, 9],
    ];

    private const SISWA_PER_ROMBEL = 20;

    private array $namaDepanL = ['Ahmad', 'Muhammad', 'Rizky', 'Dimas', 'Bagus', 'Fajar', 'Galih', 'Hendra', 'Iwan', 'Joko', 'Krisna', 'Lukman', 'Made', 'Nanda', 'Oki', 'Putra', 'Rian', 'Surya', 'Taufik', 'Wahyu'];
    private array $namaDepanP = ['Ayu', 'Bunga', 'Cahya', 'Dewi', 'Eka', 'Fitri', 'Gita', 'Hilda', 'Intan', 'Julia', 'Kartika', 'Lestari', 'Mira', 'Nadia', 'Olivia', 'Putri', 'Ratna', 'Sari', 'Tia', 'Wulan'];
    private array $namaBelakangL = ['Saputra', 'Wijaya', 'Pratama', 'Ramadhan', 'Kurniawan', 'Setiawan', 'Nugroho', 'Firmansyah', 'Hidayat', 'Gunawan', 'Santoso', 'Wibowo', 'Permana', 'Maulana', 'Perkasa'];
    private array $namaBelakangP = ['Anggraini', 'Wulandari', 'Safitri', 'Rahmawati', 'Kusuma', 'Puspita', 'Maharani', 'Amelia', 'Salsabila', 'Kirana', 'Aulia', 'Damayanti', 'Oktaviani', 'Marlina', 'Handayani'];
    private array $agamaPool = ['Islam', 'Islam', 'Islam', 'Islam', 'Islam', 'Islam', 'Islam', 'Kristen', 'Katolik', 'Hindu'];

    /** Nomor urut global siswa (dipakai untuk NISN/NIS/nama unik lintas TA). */
    private int $siswaSeq = 0;

    public function run(): void
    {
        $sekolah = $this->seedSekolah();
        [$taLalu, $taAktif] = $this->seedTahunAjaran();
        $this->seedTingkatKelas();
        $mapel = $this->seedMapel();
        $guru = $this->seedGuru();

        // --- TA lalu (2025/2026, selesai): rombel penuh utk tingkat 7, 8, 9 ---
        $rombelLalu = $this->seedRombelDanSiswa($taLalu, $guru, [7, 8, 9]);
        $this->seedGuruMapelUntukTa($taLalu, $guru, $mapel, $rombelLalu);

        // --- TA aktif (2026/2027, baru mulai): baru rombel kelas 7 (siswa baru) ---
        // Kelas 8 & 9 TA aktif SENGAJA belum dibuat -- itu tugas admin lewat
        // Administrasi Periodikal (naikkan siswa TA lalu ke TA aktif).
        $rombelAktif = $this->seedRombelDanSiswa($taAktif, $guru, [7]);
        $this->seedGuruMapelUntukTa($taAktif, $guru, $mapel, $rombelAktif);
    }

    private function seedSekolah(): Sekolah
    {
        return Sekolah::updateOrCreate(['npsn' => '20100456'], [
            'nama_sekolah' => 'SMP Negeri 1 Cendekia Bangsa',
            'jenjang' => 'SMP',
            'alamat' => 'Jl. Pendidikan Raya No. 45',
            'kelurahan' => 'Sukamaju', 'kecamatan' => 'Cibinong',
            'kabupaten' => 'Bogor', 'provinsi' => 'Jawa Barat',
            'telepon' => '021-8765432', 'email' => 'info@smpn1cendekia.sch.id',
            'kepala_sekolah' => 'Dra. Hj. Siti Aminah, M.Pd.',
            'nip_kepala_sekolah' => '196812051994032002',
        ]);
    }

    /** @return array{0: TahunAjaran, 1: TahunAjaran} [ta lalu, ta aktif] */
    private function seedTahunAjaran(): array
    {
        $lalu = TahunAjaran::updateOrCreate(['kode_tahun_ajaran' => '2526'], [
            'nama_tahun_ajaran' => '2025/2026', 'is_aktif' => false,
            'tanggal_mulai' => '2025-07-14', 'tanggal_selesai' => '2026-06-30',
        ]);

        $aktif = TahunAjaran::updateOrCreate(['kode_tahun_ajaran' => '2627'], [
            'nama_tahun_ajaran' => '2026/2027', 'is_aktif' => true,
            'tanggal_mulai' => '2026-07-13', 'tanggal_selesai' => '2027-06-30',
        ]);

        return [$lalu, $aktif];
    }

    private function seedTingkatKelas(): void
    {
        foreach ([7, 8, 9] as $urutan => $nomor) {
            TingkatKelas::updateOrCreate(['kode' => (string) $nomor], [
                'nama' => "Kelas {$nomor}", 'nomor' => $nomor, 'jenjang' => 'SMP',
                'urutan' => $urutan + 1, 'is_aktif' => true,
            ]);
        }
    }

    /** @return array<string, MataPelajaran> kode_mapel => model */
    private function seedMapel(): array
    {
        $data = [
            ['PAIBP', 'Pendidikan Agama Islam dan Budi Pekerti', 'Umum'],
            ['PPKN',  'Pendidikan Pancasila', 'Umum'],
            ['BIN',   'Bahasa Indonesia', 'Umum'],
            ['MTK',   'Matematika', 'Umum'],
            ['IPA',   'Ilmu Pengetahuan Alam', 'Umum'],
            ['IPS',   'Ilmu Pengetahuan Sosial', 'Umum'],
            ['BING',  'Bahasa Inggris', 'Umum'],
            ['PJOK',  'Pendidikan Jasmani, Olahraga, dan Kesehatan', 'Umum'],
            ['INF',   'Informatika', 'Umum'],
            ['SBD',   'Seni Budaya', 'Umum'],
            ['MULOK', 'Bahasa Sunda', 'Muatan Lokal'],
        ];

        $mapel = [];
        foreach ($data as [$kode, $nama, $kelompok]) {
            $mapel[$kode] = MataPelajaran::updateOrCreate(['kode_mapel' => $kode], [
                'nama_mapel' => $nama, 'kelompok' => $kelompok,
                'jurusan_id' => null, 'tingkat' => null, 'is_aktif' => true,
            ]);
        }

        return $mapel;
    }

    /** @return array<int, Guru> index (sesuai $guruData) => model */
    private function seedGuru(): array
    {
        $guru = [];
        foreach ($this->guruData as $i => $g) {
            $guru[$i] = Guru::updateOrCreate(['nip' => $g['nip']], [
                'nama_ptk' => $g['nama'],
                'email' => \Illuminate\Support\Str::slug(explode(',', $g['nama'])[0]).'@smpn1cendekia.sch.id',
                'jenis_kelamin' => $g['gender'],
                'jabatan' => $g['jabatan'], 'status_kepegawaian' => $g['status'],
                'password' => Hash::make('password'),
                'is_aktif' => true,
            ]);
        }

        return $guru;
    }

    /**
     * Buat rombel utk tiap tingkat di $tingkatList pada $ta, isi wali kelas,
     * lalu buat & tempatkan siswa (via siswa_rombel).
     *
     * @param array<int, Guru> $guru
     * @return array<int, RombonganBelajar[]> tingkat => daftar rombel
     */
    private function seedRombelDanSiswa(TahunAjaran $ta, array $guru, array $tingkatList): array
    {
        $rombelByTingkat = [];

        foreach ($tingkatList as $tingkat) {
            $rombelByTingkat[$tingkat] = [];

            foreach ([1, 2, 3] as $urut) {
                $waliIdx = $this->waliKelas[$tingkat][$urut - 1];

                $rombel = RombonganBelajar::updateOrCreate(
                    ['nama_rombel' => "{$tingkat}-{$urut}", 'tahun_ajaran_id' => $ta->id],
                    ['tingkat' => $tingkat, 'jurusan_id' => null, 'wali_kelas_id' => $guru[$waliIdx]->id, 'kapasitas' => 32]
                );
                $rombelByTingkat[$tingkat][] = $rombel;

                $this->seedSiswaUntukRombel($rombel, $ta, self::SISWA_PER_ROMBEL);
            }
        }

        return $rombelByTingkat;
    }

    private function seedSiswaUntukRombel(RombonganBelajar $rombel, TahunAjaran $ta, int $jumlah): void
    {
        for ($n = 0; $n < $jumlah; $n++) {
            $seq = $this->siswaSeq++;
            $gender = $seq % 2 === 0 ? 'L' : 'P';
            $nama = $this->namaSiswa($seq, $gender);

            // Prefix "3201" (kode wilayah fiktif) supaya tidak bentrok dgn NISN
            // demo lain (mis. seeder cbt pakai prefix "0099").
            $nisn = '3201'.str_pad((string) ($seq + 1), 6, '0', STR_PAD_LEFT);

            $siswa = Siswa::updateOrCreate(['nisn' => $nisn], [
                'nis' => str_pad((string) ($seq + 1), 6, '0', STR_PAD_LEFT),
                'nama_siswa' => $nama,
                'jenis_kelamin' => $gender,
                'tanggal_lahir' => $this->tanggalLahir($rombel->tingkat, $seq),
                'agama' => $this->agamaPool[$seq % count($this->agamaPool)],
                'alamat' => 'Jl. Merdeka No. '.(($seq % 50) + 1).', Cibinong',
                'nama_ayah' => $this->namaSiswa($seq + 1000, 'L'),
                'nama_ibu' => $this->namaSiswa($seq + 1000, 'P'),
                'password' => Hash::make('password'),
                'is_aktif' => true,
                'status_siswa' => 'Aktif',
            ]);

            SiswaRombel::updateOrCreate(
                ['siswa_id' => $siswa->id, 'tahun_ajaran_id' => $ta->id],
                ['rombongan_belajar_id' => $rombel->id]
            );
        }
    }

    private function namaSiswa(int $i, string $gender): string
    {
        $depan = $gender === 'L' ? $this->namaDepanL : $this->namaDepanP;
        $belakang = $gender === 'L' ? $this->namaBelakangL : $this->namaBelakangP;

        $d = $depan[$i % count($depan)];
        $b = $belakang[intdiv($i, count($depan)) % count($belakang)];

        return "{$d} {$b}";
    }

    /** Umur wajar SMP: kelas 7 ~12-13th, kelas 8 ~13-14th, kelas 9 ~14-15th (relatif thn ajaran 2025/2026). */
    private function tanggalLahir(int $tingkat, int $seq): string
    {
        // Kelas 7 lahir ~2013 (usia ~13th di TA 2026/2027), kelas 8 ~2012, kelas 9 ~2011.
        $tahunLahir = 2013 - ($tingkat - 7);
        $bulan = ($seq % 12) + 1;
        $tanggal = ($seq % 28) + 1;

        return sprintf('%04d-%02d-%02d', $tahunLahir, $bulan, $tanggal);
    }

    /**
     * @param array<int, Guru> $guru
     * @param array<string, MataPelajaran> $mapel
     * @param array<int, RombonganBelajar[]> $rombelByTingkat
     */
    private function seedGuruMapelUntukTa(TahunAjaran $ta, array $guru, array $mapel, array $rombelByTingkat): void
    {
        foreach ($this->penugasan as $kodeMapel => $penugasanMapel) {
            foreach ($penugasanMapel as [$guruIdx, $tingkatList]) {
                foreach ($tingkatList as $tingkat) {
                    if (! isset($rombelByTingkat[$tingkat])) {
                        continue; // TA ini belum punya rombel di tingkat tsb (mis. TA aktif baru kelas 7)
                    }

                    foreach ($rombelByTingkat[$tingkat] as $rombel) {
                        GuruMapel::firstOrCreate([
                            'guru_id' => $guru[$guruIdx]->id,
                            'mata_pelajaran_id' => $mapel[$kodeMapel]->id,
                            'rombongan_belajar_id' => $rombel->id,
                            'tahun_ajaran_id' => $ta->id,
                        ]);
                    }
                }
            }
        }
    }
}
