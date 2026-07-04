@extends('layouts.app')
@section('title', 'Profil Sekolah')
@section('breadcrumb', 'Data Center / Profil Sekolah')

@section('content')
<x-page-header title="Profil Sekolah" subtitle="Identitas lembaga dan kontak"/>
<form method="POST" action="{{ route('sekolah.update') }}" enctype="multipart/form-data" class="card card-pad space-y-5">
    @csrf @method('PUT')

    {{-- Logo sekolah = sumber tunggal branding. Logo ini otomatis dipakai
         aplikasi CBT & landing-page (sebagai logo sekaligus favicon) via API
         publik /api/v1/public/branding. --}}
    <div class="flex flex-wrap items-center gap-4 pb-4 border-b border-slate-100">
        <div class="w-20 h-20 rounded-xl border border-slate-200 bg-slate-50 grid place-items-center overflow-hidden shrink-0">
            @if($sekolah->logo)
                <img src="{{ Storage::disk('public')->url($sekolah->logo) }}" alt="Logo" class="max-w-full max-h-full object-contain">
            @else
                <span class="text-xs text-slate-400">Belum ada<br>logo</span>
            @endif
        </div>
        <div class="flex-1 min-w-[220px]">
            <label class="block text-sm font-semibold text-slate-700 mb-1">Logo Sekolah</label>
            <input type="file" name="logo" accept="image/*"
                   class="input file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 file:font-semibold">
            <p class="text-xs text-slate-400 mt-1">PNG/JPG/SVG/WEBP, maks 2MB. Dipakai bersama oleh CBT &amp; landing-page.</p>
            @error('logo')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            @if($sekolah->logo)
                <label class="inline-flex items-center gap-2 text-xs text-rose-600 mt-2">
                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                    Hapus logo saat ini
                </label>
            @endif
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <x-field name="npsn" label="NPSN" :value="$sekolah->npsn" required/>
        <x-field name="nama_sekolah" label="Nama Sekolah" :value="$sekolah->nama_sekolah" required/>
        <x-field type="select" name="jenjang" label="Jenjang" :value="$sekolah->jenjang"
                 :options="['SD'=>'SD','SMP'=>'SMP','SMA'=>'SMA','SMK'=>'SMK','MA'=>'MA']" required/>
        <x-field name="telepon" label="Telepon" :value="$sekolah->telepon"/>
        <x-field name="email" type="email" label="Email" :value="$sekolah->email"/>
        <x-field name="website" label="Website" :value="$sekolah->website"/>
        <x-field name="kepala_sekolah" label="Kepala Sekolah" :value="$sekolah->kepala_sekolah"/>
        <x-field name="nip_kepala_sekolah" label="NIP Kepala Sekolah" :value="$sekolah->nip_kepala_sekolah"/>
    </div>
    <x-field name="alamat" label="Alamat" :value="$sekolah->alamat"/>
    <div class="grid md:grid-cols-4 gap-4">
        <x-field name="kelurahan" label="Kelurahan" :value="$sekolah->kelurahan"/>
        <x-field name="kecamatan" label="Kecamatan" :value="$sekolah->kecamatan"/>
        <x-field name="kabupaten" label="Kabupaten" :value="$sekolah->kabupaten"/>
        <x-field name="provinsi" label="Provinsi" :value="$sekolah->provinsi"/>
    </div>
    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
        <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> Simpan</button>
    </div>
</form>
@endsection
