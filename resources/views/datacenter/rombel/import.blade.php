@extends('layouts.app')
@section('title', 'Import Rombongan Belajar')
@section('breadcrumb', 'Data Center / Rombel / Import')

@section('content')
<x-page-header title="Import Rombongan Belajar" subtitle="Upload file Excel (.xlsx) untuk menambah / memperbarui rombel & wali kelas massal">
    <x-slot:action>
        <a href="{{ route('rombel.index') }}" class="btn-secondary">Kembali</a>
    </x-slot:action>
</x-page-header>

<div class="grid lg:grid-cols-2 gap-6">
    <form method="POST" action="{{ route('rombel.import.store') }}" enctype="multipart/form-data"
          class="card card-pad space-y-4">
        @csrf
        <h3 class="font-semibold text-ink-900">Upload File Excel</h3>

        <label class="block">
            <span class="label">Pilih file (.xlsx, .xls, .csv)</span>
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                   class="input file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 file:font-semibold">
            @error('file')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            <p class="mt-1 text-xs text-ink-500">Maks. 5 MB. Header wajib ada di baris 1.</p>
        </label>

        <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
            <button class="btn-primary">Mulai Import</button>
            <a href="{{ route('rombel.import.template') }}" class="btn-secondary">Unduh Template</a>
        </div>

        @if(session('importErrors') && count(session('importErrors')))
            <div class="mt-3 p-3 rounded-lg bg-rose-50 border border-rose-200 text-sm">
                <div class="font-semibold text-rose-700 mb-2">{{ count(session('importErrors')) }} baris gagal:</div>
                <ul class="list-disc pl-5 text-rose-600 text-xs space-y-0.5 max-h-48 overflow-auto">
                    @foreach(session('importErrors') as $err)<li>{{ $err }}</li>@endforeach
                </ul>
            </div>
        @endif
    </form>

    <div class="card card-pad space-y-3 text-sm">
        <h3 class="font-semibold text-ink-900">Format Kolom</h3>
        <code class="block text-[10px] bg-slate-50 p-2 rounded border border-slate-200 break-all">
            nama_rombel | tingkat | kode_jurusan | tahun_ajaran | nip_wali_kelas | kapasitas
        </code>
        <ul class="text-xs text-ink-600 list-disc pl-5 space-y-1">
            <li><code>nama_rombel</code> & <code>tingkat</code> wajib diisi</li>
            <li>Kunci unik: kombinasi <code>nama_rombel</code> + <code>tahun_ajaran</code>. Jika sudah ada → di-update; jika belum → dibuat baru.</li>
            <li><code>kode_jurusan</code> opsional — harus jurusan yang sudah terdaftar (kosongkan kalau rombel belum berjurusan, mis. SD/SMP).</li>
            <li><code>tahun_ajaran</code> opsional — nama TA persis (mis. <code>2024/2025 - Ganjil</code>). Kosong → pakai Tahun Ajaran aktif.</li>
            <li><code>nip_wali_kelas</code> opsional — NIP guru yang sudah terdaftar sebagai wali kelas rombel ini.</li>
            <li><code>kapasitas</code> opsional — angka. Kosong → default 36.</li>
        </ul>
    </div>
</div>
@endsection
