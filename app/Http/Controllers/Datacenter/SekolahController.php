<?php

namespace App\Http\Controllers\Datacenter;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SekolahController extends Controller
{
    public function edit()
    {
        $sekolah = Sekolah::first() ?? new Sekolah();
        return view('datacenter.sekolah.edit', compact('sekolah'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'npsn' => 'required|string|max:20',
            'nama_sekolah' => 'required|string|max:255',
            'jenjang' => 'required|string|max:20',
            'alamat' => 'nullable|string|max:255',
            'kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kabupaten' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'telepon' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:100',
            'kepala_sekolah' => 'nullable|string|max:255',
            'nip_kepala_sekolah' => 'nullable|string|max:30',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        $sekolah = Sekolah::first();

        // Logo sekolah = sumber tunggal branding untuk CBT & landing-page
        // (diambil via GET /api/v1/public/branding). Disimpan di disk "public".
        if ($request->hasFile('logo')) {
            if ($sekolah && $sekolah->logo) {
                Storage::disk('public')->delete($sekolah->logo);
            }
            $data['logo'] = $request->file('logo')->store('sekolah', 'public');
        } elseif ($request->boolean('remove_logo')) {
            if ($sekolah && $sekolah->logo) {
                Storage::disk('public')->delete($sekolah->logo);
            }
            $data['logo'] = null;
        } else {
            unset($data['logo']); // jangan timpa logo lama kalau tidak ada file baru
        }

        if ($sekolah) {
            $sekolah->update($data);
        } else {
            Sekolah::create($data);
        }

        return back()->with('success', 'Profil sekolah berhasil disimpan.');
    }
}
