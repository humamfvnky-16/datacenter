<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Permission;
use App\Models\Role;
use App\Models\MataPelajaran;
use App\Models\RombonganBelajar;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\SiswaRombel;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- AppSettings default ---
        $defaults = [
            ['app_name',       'Data Center Sekolah', 'text', 'aplikasi'],
            ['app_tagline',    'Sistem Informasi Data Induk Sekolah', 'text', 'aplikasi'],
            ['theme_color',    '#0d9488',            'color','tampilan'],
            ['login_title',    'Selamat datang di <span class="text-amber-300">Data Center</span> Sekolah.', 'text', 'login'],
            ['login_subtitle', 'Kelola data sekolah, guru, siswa, dan kelas dalam satu sumber data terpusat.', 'text', 'login'],
            ['footer_text',    null, 'text', 'aplikasi'],
        ];
        foreach ($defaults as [$key, $val, $type, $group]) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => $val, 'type' => $type, 'group' => $group]);
        }

        // --- Roles, Permissions & Akun admin ---
        // Dipisah ke RbacSeeder supaya bisa dijalankan sendiri di produksi tanpa
        // data demo: php artisan db:seed --class=RbacSeeder --force
        $this->call(RbacSeeder::class);

        // --- Sekolah ---
        Sekolah::updateOrCreate(['npsn' => '20200001'], [
            'nama_sekolah' => 'SMA Negeri 1 Modern',
            'jenjang' => 'SMA',
            'alamat' => 'Jl. Pendidikan No. 1',
            'kelurahan' => 'Sukamaju', 'kecamatan' => 'Cibinong',
            'kabupaten' => 'Bogor', 'provinsi' => 'Jawa Barat',
            'telepon' => '021-1234567', 'email' => 'info@sma1modern.sch.id',
            'kepala_sekolah' => 'Dr. Budi Santoso, M.Pd.',
            'nip_kepala_sekolah' => '197001012000031001',
        ]);

        // --- Tahun ajaran ---
        $ta = TahunAjaran::updateOrCreate(
            ['kode_tahun_ajaran' => '2526'],
            ['nama_tahun_ajaran' => '2025/2026', 'semester' => 'Ganjil', 'is_aktif' => true,
             'tanggal_mulai' => now()->setDate(2025, 7, 15), 'tanggal_selesai' => now()->setDate(2026, 6, 30)]
        );

        // --- Jurusan ---
        $ipa = Jurusan::firstOrCreate(['kode_jurusan' => 'IPA'], ['nama_jurusan' => 'Ilmu Pengetahuan Alam', 'singkatan' => 'IPA']);
        $ips = Jurusan::firstOrCreate(['kode_jurusan' => 'IPS'], ['nama_jurusan' => 'Ilmu Pengetahuan Sosial', 'singkatan' => 'IPS']);

        // --- Mapel ---
        $mapelData = [
            ['MTK', 'Matematika', 'Umum'],
            ['BIO', 'Biologi', 'Kejuruan', $ipa->id],
            ['FIS', 'Fisika', 'Kejuruan', $ipa->id],
            ['EKO', 'Ekonomi', 'Kejuruan', $ips->id],
            ['SEJ', 'Sejarah Indonesia', 'Umum'],
            ['BIN', 'Bahasa Indonesia', 'Umum'],
            ['BING', 'Bahasa Inggris', 'Umum'],
        ];
        foreach ($mapelData as $row) {
            MataPelajaran::firstOrCreate(['kode_mapel' => $row[0]], [
                'nama_mapel' => $row[1], 'kelompok' => $row[2],
                'jurusan_id' => $row[3] ?? null, 'tingkat' => 10,
            ]);
        }

        // --- Guru ---
        $guruNames = ['Andi Wijaya', 'Sri Wahyuni', 'Hendra Pratama', 'Lina Marlina', 'Rudi Hartono'];
        $gurus = [];
        foreach ($guruNames as $i => $name) {
            $nip = '198001'.str_pad((string) ($i+1), 2, '0', STR_PAD_LEFT).'2000031000'.$i;
            $g = Guru::updateOrCreate(['nip' => $nip], [
                'nama_ptk' => $name,
                'email' => Str::slug($name, '.').'@sekolah.test',
                'jenis_kelamin' => $i % 2 ? 'P' : 'L',
                'jabatan' => 'Guru', 'status_kepegawaian' => 'PNS',
                'password' => Hash::make('password'),
                'is_aktif' => true,
            ]);
            $gurus[] = $g;
        }

        // --- Rombel ---
        $rombelNames = ['X IPA 1', 'X IPA 2', 'X IPS 1', 'XI IPA 1', 'XII IPS 1'];
        $rombels = [];
        foreach ($rombelNames as $i => $nm) {
            $tingkat = (int) ['X' => 10, 'XI' => 11, 'XII' => 12][explode(' ', $nm)[0]];
            $jurusan = str_contains($nm, 'IPA') ? $ipa : $ips;
            $r = RombonganBelajar::firstOrCreate(
                ['nama_rombel' => $nm, 'tahun_ajaran_id' => $ta->id],
                ['tingkat' => $tingkat, 'jurusan_id' => $jurusan->id, 'wali_kelas_id' => $gurus[$i % count($gurus)]->id, 'kapasitas' => 36]
            );
            $rombels[] = $r;
        }

        // --- Siswa ---
        $siswaNames = ['Ahmad Fauzi', 'Bunga Citra', 'Cahya Dewi', 'Dimas Eka', 'Eva Faridah',
                       'Galih Hidayat', 'Hilda Indah', 'Iwan Jaya', 'Joko Kurnia', 'Kartika Lestari',
                       'Mira Nurul', 'Nanda Oktavia', 'Putu Riadi', 'Rina Sari', 'Surya Tama'];
        foreach ($siswaNames as $i => $name) {
            $nisn = '0099' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT);
            $s = Siswa::updateOrCreate(['nisn' => $nisn], [
                'nis' => 'NIS'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'nama_siswa' => $name,
                'jenis_kelamin' => $i % 2 ? 'P' : 'L',
                'agama' => 'Islam',
                'password' => Hash::make('password'),
                'is_aktif' => true,
            ]);
            SiswaRombel::firstOrCreate([
                'siswa_id' => $s->id, 'tahun_ajaran_id' => $ta->id,
            ], [
                'rombongan_belajar_id' => $rombels[$i % count($rombels)]->id,
            ]);
        }
    }
}
