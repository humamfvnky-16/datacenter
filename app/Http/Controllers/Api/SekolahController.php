<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;

class SekolahController extends Controller
{
    public function show()
    {
        return response()->json(['data' => Sekolah::first()]);
    }
}
