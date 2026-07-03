<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;

class TahunAjaranController extends Controller
{
    public function index(Request $r)
    {
        $items = TahunAjaran::query()
            ->when($r->boolean('aktif'), fn ($q) => $q->where('is_aktif', true))
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $items]);
    }
}
