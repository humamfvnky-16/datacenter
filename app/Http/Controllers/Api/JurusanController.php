<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;

class JurusanController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Jurusan::where('is_aktif', true)->orderBy('nama_jurusan')->get(),
        ]);
    }
}
