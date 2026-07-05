<?php

namespace App\Services\Master;

use App\Models\Guru;
use App\Models\GuruMapel;
use App\Models\MataPelajaran;
use App\Models\RombonganBelajar;
use App\Models\TahunAjaran;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Import / Export assignment Guru ↔ Mapel ↔ Rombel (Excel).
 *
 * Kolom (baris 1), sesuai rekap "Guru Mengajar" (mis. hasil export Dapodik):
 *   NIP | Nama Lengkap | Tingkat | Rombel | Kode Mata Pelajaran | Mata Pelajaran
 *
 * - NIP                  : NIP guru (harus sudah terdaftar). Boleh kosong pada baris
 *                          lanjutan (sel "digabung") → diwarisi dari baris terakhir yang terisi.
 * - Nama Lengkap/Tingkat : opsional, hanya informasi tambahan (ikut carry-forward, tidak divalidasi).
 * - Rombel               : nama rombel, boleh berisi lebih dari satu dipisah koma
 *                          (mis. "7-1,7-2,7-3,7-4") → tiap rombel jadi 1 assignment.
 * - Kode Mata Pelajaran  : kode mapel. Jika kosong → dicocokkan lewat kolom Mata Pelajaran (nama).
 * - Mata Pelajaran       : nama mapel, dipakai sebagai fallback pencocokan saat kode kosong.
 *
 * Tahun ajaran selalu memakai Tahun Ajaran aktif.
 * Logika: firstOrCreate berdasarkan kombinasi guru+mapel+rombel+TA aktif.
 */
class GuruMapelExcelService
{
    public const HEADERS = [
        'NIP', 'Nama Lengkap', 'Tingkat', 'Rombel', 'Kode Mata Pelajaran', 'Mata Pelajaran',
    ];

    /** Peta label header (dinormalisasi lower-case, spasi dirapikan) → key internal. */
    protected const HEADER_MAP = [
        'nip'                  => 'nip',
        'nama lengkap'         => 'nama_lengkap',
        'tingkat'              => 'tingkat',
        'rombel'               => 'rombel',
        'kode mata pelajaran'  => 'kode_mapel',
        'mata pelajaran'       => 'nama_mapel',
    ];

    public function import(UploadedFile $file): ImportResult
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $data  = $sheet->toArray(null, true, true, false);
        $result = new ImportResult();

        if (count($data) < 2) return $result;

        $rawHeaders = array_shift($data);
        $headers = array_map(function ($v) {
            $norm = preg_replace('/\s+/', ' ', trim(strtolower((string) $v)));
            return self::HEADER_MAP[$norm] ?? $norm;
        }, $rawHeaders);

        $taAktif = TahunAjaran::aktif();
        if (! $taAktif) {
            $result->errors[] = 'Tidak ada Tahun Ajaran aktif.';
            return $result;
        }

        // Cache lookup biar cepat
        $guruCache  = Guru::pluck('id', 'nip');
        $mapelByKode = MataPelajaran::pluck('id', 'kode_mapel');
        $mapelByNama = MataPelajaran::get()->keyBy(fn ($m) => strtolower(trim($m->nama_mapel)))->map(fn ($m) => $m->id);
        $rombelCache = RombonganBelajar::where('tahun_ajaran_id', $taAktif->id)
            ->get()->keyBy(fn ($r) => strtolower(trim($r->nama_rombel)));

        // State carry-forward untuk sel yang "digabung" (kosong = lanjutan baris di atasnya)
        $lastNip = null;

        foreach ($data as $i => $row) {
            $line = $i + 2;
            try {
                $assoc = [];
                foreach ($headers as $idx => $h) {
                    $assoc[$h] = $row[$idx] ?? null;
                }

                $nip = trim((string) ($assoc['nip'] ?? ''));
                if ($nip === '') {
                    $nip = $lastNip ?? '';
                } else {
                    $lastNip = $nip;
                }

                $rombelRaw  = trim((string) ($assoc['rombel'] ?? ''));
                $kodeMapel  = trim((string) ($assoc['kode_mapel'] ?? ''));
                $namaMapel  = trim((string) ($assoc['nama_mapel'] ?? ''));

                if ($nip === '') {
                    throw new \RuntimeException('NIP tidak ditemukan (baris pertama tidak boleh kosong)');
                }
                if ($rombelRaw === '') {
                    throw new \RuntimeException('Rombel wajib diisi');
                }
                if ($kodeMapel === '' && $namaMapel === '') {
                    throw new \RuntimeException('Kode Mata Pelajaran & Mata Pelajaran tidak boleh kosong dua-duanya');
                }

                $guruId = $guruCache[$nip] ?? null;
                if (! $guruId) throw new \RuntimeException("Guru NIP '{$nip}' tidak ditemukan");

                $mapelId = $kodeMapel !== ''
                    ? ($mapelByKode[$kodeMapel] ?? null)
                    : ($mapelByNama[strtolower($namaMapel)] ?? null);

                if (! $mapelId) {
                    $label = $kodeMapel !== '' ? "kode '{$kodeMapel}'" : "nama '{$namaMapel}'";
                    throw new \RuntimeException("Mapel dengan {$label} tidak ditemukan");
                }

                $rombelNames = array_filter(array_map('trim', explode(',', $rombelRaw)));

                $createdInLine = 0;
                $lineErrors = [];
                foreach ($rombelNames as $namaRombel) {
                    $rombel = $rombelCache[strtolower($namaRombel)] ?? null;
                    if (! $rombel) {
                        $lineErrors[] = "Rombel '{$namaRombel}' tidak ada di TA aktif";
                        continue;
                    }

                    GuruMapel::firstOrCreate([
                        'guru_id'              => $guruId,
                        'mata_pelajaran_id'    => $mapelId,
                        'rombongan_belajar_id' => $rombel->id,
                        'tahun_ajaran_id'      => $taAktif->id,
                    ]);
                    $createdInLine++;
                }

                $result->success += $createdInLine;
                if ($lineErrors) {
                    $result->failed++;
                    $result->errors[] = "Baris {$line}: " . implode('; ', $lineErrors);
                }
            } catch (\Throwable $e) {
                $result->failed++;
                $result->errors[] = "Baris {$line}: " . $e->getMessage();
            }
        }

        return $result;
    }

    public function export(?\Illuminate\Database\Eloquent\Collection $items = null): StreamedResponse
    {
        $items ??= GuruMapel::with('guru', 'mapel', 'rombel', 'tahunAjaran')
                    ->orderBy('guru_id')->orderBy('mata_pelajaran_id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Guru Mapel');

        $sheet->fromArray([self::HEADERS], null, 'A1');
        $this->styleHeader($sheet, count(self::HEADERS));
        $this->forceTextColumns($sheet, count(self::HEADERS));

        // Kelompokkan per guru + mapel + tingkat rombel, rombel-nya digabung koma
        // (meniru format rekap: sel NIP/Nama Lengkap hanya tampil di baris pertama guru terkait).
        $grouped = $items
            ->filter(fn ($gm) => $gm->guru && $gm->mapel && $gm->rombel)
            ->groupBy(fn ($gm) => $gm->guru_id . '|' . $gm->mata_pelajaran_id . '|' . $gm->rombel->tingkat);

        $rows = [];
        $lastGuruId = null;
        foreach ($grouped as $group) {
            $first = $group->first();
            $guru  = $first->guru;
            $mapel = $first->mapel;
            $sameGuru = $lastGuruId === $guru->id;

            $rows[] = [
                $sameGuru ? '' : $guru->nip,
                $sameGuru ? '' : $guru->nama_ptk,
                $first->rombel->tingkat,
                $group->pluck('rombel.nama_rombel')->unique()->implode(','),
                $mapel->kode_mapel,
                $mapel->nama_mapel,
            ];
            $lastGuruId = $guru->id;
        }

        $this->writeRowsAsText($sheet, $rows, 2);

        foreach (range('A', chr(64 + count(self::HEADERS))) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->stream($spreadsheet, 'data-guru-mapel-' . date('Ymd-His') . '.xlsx');
    }

    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Guru Mapel');

        $sheet->fromArray([self::HEADERS], null, 'A1');
        $this->styleHeader($sheet, count(self::HEADERS));
        $this->forceTextColumns($sheet, count(self::HEADERS));

        $this->writeRowsAsText($sheet, [
            ['197112161997022002', 'TIARLY SILABAN, S.Pd., M.', '7', '7-1,7-2,7-3,7-4', '',    'IPA'],
            ['',                   '',                           '9', '9-1,9-2,9-3',     '',    'IPA'],
            ['198609031980021002', 'RIKARDO HUTAPEA, S.Pd.',     '7', '7-1,7-2',         'MTK', 'MATEMATIKA'],
        ], 2);

        foreach (range('A', chr(64 + count(self::HEADERS))) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->stream($spreadsheet, 'template-import-guru-mapel.xlsx');
    }

    /* ---------- helpers ---------- */

    protected function styleHeader($sheet, int $colCount): void
    {
        $range = 'A1:' . chr(64 + $colCount) . '1';
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F47F5');
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * Paksa SEMUA kolom data jadi format TEXT supaya Excel tidak meng-auto-convert
     * "7-1" jadi tanggal, NIP panjang jadi notasi ilmiah, dsb.
     */
    protected function forceTextColumns($sheet, int $colCount, int $maxRow = 9999): void
    {
        $lastCol = chr(64 + $colCount);
        $sheet->getStyle("A2:{$lastCol}{$maxRow}")
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_TEXT);
        // Set juga ke kolom dimension supaya berlaku ke baris baru yang di-tambah user
        for ($i = 1; $i <= $colCount; $i++) {
            $col = chr(64 + $i);
            $sheet->getStyle($col)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        }
    }

    /** Tulis baris-baris data dengan tipe STRING eksplisit (anti auto-cast Excel). */
    protected function writeRowsAsText($sheet, array $rows, int $startRow = 2): void
    {
        foreach ($rows as $rIdx => $row) {
            $r = $startRow + $rIdx;
            $cIdx = 0;
            foreach ($row as $value) {
                $col = chr(65 + $cIdx);
                $sheet->setCellValueExplicit("{$col}{$r}", (string) ($value ?? ''), DataType::TYPE_STRING);
                $cIdx++;
            }
        }
    }

    protected function stream(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        return response()->streamDownload(fn () => $writer->save('php://output'), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

if (! class_exists(ImportResult::class, false)) {
    class ImportResult
    {
        public int $success = 0;
        public int $failed = 0;
        public array $errors = [];
    }
}
