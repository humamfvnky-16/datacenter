<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

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

        // --- Data demo sekolah (SMP): sekolah, tahun ajaran, tingkat kelas, mapel,
        // guru, rombel, siswa, guru-mapel -- lihat SmpDemoSeeder utk detail.
        $this->call(SmpDemoSeeder::class);
    }
}
