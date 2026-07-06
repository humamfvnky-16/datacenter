<?php

namespace App\Services\Master;

/** Hasil ringkas proses import Excel: dipakai bersama oleh semua *ExcelService. */
class ImportResult
{
    public int $success = 0;
    public int $failed = 0;
    public array $errors = [];
}
