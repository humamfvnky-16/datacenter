@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Beranda / Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Greeting banner --}}
    <div class="rounded-3xl p-6 md:p-8 relative overflow-hidden text-white shadow-glow"
         style="background: linear-gradient(135deg, #062275 0%, #00c2ae 45%, #062275 100%);">
        <div class="absolute -top-16 -right-16 w-72 h-72 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-20 -left-10 w-72 h-72 rounded-full bg-white/5 blur-3xl"></div>
        <div class="relative z-10 flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="text-brand-100 text-sm">{{ now()->translatedFormat('l, d F Y') }}</div>
                <h2 class="text-2xl md:text-3xl font-bold mt-1">Halo, {{ auth()->user()->name ?? 'Admin' }}</h2>
                <p class="text-brand-100 mt-1 text-sm">
                    Selamat datang kembali di {{ $AppCfg['app_name'] }}.
                    @if($sekolah)
                        Mengelola data induk {{ $sekolah->nama_sekolah }}.
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('siswa.create') }}" class="bg-white/15 hover:bg-white/25 backdrop-blur px-4 py-2 rounded-xl text-sm font-semibold transition border border-white/20">
                    + Tambah Siswa
                </a>
                <a href="{{ route('guru.create') }}" class="bg-white text-brand-700 hover:bg-brand-50 px-4 py-2 rounded-xl text-sm font-semibold transition">
                    + Tambah Guru
                </a>
            </div>
        </div>
    </div>

    @if($tahunAjaranAktif)
        <div class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
            Tahun ajaran aktif: <strong>{{ $tahunAjaranAktif->nama_tahun_ajaran }} &middot; {{ $tahunAjaranAktif->semester }}</strong>
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <x-stat-card label="Total Siswa" :value="number_format($stats['siswa'])" icon="users" tone="brand" href="{{ route('siswa.index') }}"/>
        <x-stat-card label="Total Guru" :value="number_format($stats['guru'])" icon="user-tie" tone="emerald" href="{{ route('guru.index') }}"/>
        <x-stat-card label="Mata Pelajaran" :value="$stats['mapel']" icon="book" tone="sky" href="{{ route('mapel.index') }}"/>
        <x-stat-card label="Jurusan" :value="$stats['jurusan']" icon="layers" tone="amber" href="{{ route('jurusan.index') }}"/>
        <x-stat-card label="Rombongan Belajar" :value="$stats['rombel']" icon="grid" tone="violet" href="{{ route('rombel.index') }}"/>
    </div>
</div>
@endsection
