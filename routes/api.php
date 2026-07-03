<?php

/*
|--------------------------------------------------------------------------
| API Routes — Data Center (provider data induk)
|--------------------------------------------------------------------------
| Prefix otomatis: /api (lihat bootstrap/app.php)
| Semua endpoint di sini dipakai oleh aplikasi KLIEN (CBT, Perpustakaan
| Digital) via Sanctum bearer token. Token dibuat lewat:
|     php artisan api:token {nama-klien}
|
| Ability token:
|   - datacenter.read  → boleh akses endpoint list/detail (read-only)
|   - datacenter.auth  → boleh akses endpoint verifikasi login (verify-siswa/guru)
*/

use App\Http\Controllers\Api\GuruController;
use App\Http\Controllers\Api\GuruMapelController;
use App\Http\Controllers\Api\JurusanController;
use App\Http\Controllers\Api\MataPelajaranController;
use App\Http\Controllers\Api\PublicStatsController;
use App\Http\Controllers\Api\RombelController;
use App\Http\Controllers\Api\SekolahController;
use App\Http\Controllers\Api\SiswaController;
use App\Http\Controllers\Api\TahunAjaranController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['ok' => true, 'time' => now()->toIso8601String()]);

Route::prefix('v1')->group(function () {

    // ---- Publik, tanpa token (dipakai landing page sekolah) ----
    Route::get('public/stats', [PublicStatsController::class, 'show']);

    // ---- Read-only (list & detail) ----
    Route::middleware(['auth:sanctum', 'abilities:datacenter.read'])->group(function () {
        Route::get('tahun-ajaran', [TahunAjaranController::class, 'index']);

        Route::get('rombel', [RombelController::class, 'index']);
        Route::get('rombel/{rombel}', [RombelController::class, 'show']);
        Route::get('rombel/{rombel}/siswa', [RombelController::class, 'siswa']);

        Route::get('siswa', [SiswaController::class, 'index']);
        Route::get('siswa/{siswa}', [SiswaController::class, 'show']);

        Route::get('guru', [GuruController::class, 'index']);
        Route::get('guru/{guru}', [GuruController::class, 'show']);
        Route::get('guru-mapel', [GuruMapelController::class, 'index']);

        Route::get('jurusan', [JurusanController::class, 'index']);
        Route::get('mata-pelajaran', [MataPelajaranController::class, 'index']);
        Route::get('sekolah', [SekolahController::class, 'show']);
    });

    // ---- Verifikasi login & ganti password (dipakai auth guard aplikasi klien) ----
    Route::middleware(['auth:sanctum', 'abilities:datacenter.auth'])->group(function () {
        Route::post('auth/verify-siswa', [SiswaController::class, 'verify']);
        Route::post('auth/verify-guru', [GuruController::class, 'verify']);
        Route::post('auth/change-password-siswa', [SiswaController::class, 'changePassword']);
        Route::post('auth/change-password-guru', [GuruController::class, 'changePassword']);
    });
});
