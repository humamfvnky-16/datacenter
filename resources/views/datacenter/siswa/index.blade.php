@extends('layouts.app')
@section('title', 'Data Siswa')
@section('breadcrumb', 'Data Center / Siswa')

@section('content')
<x-page-header title="Data Siswa" subtitle="Peserta didik aktif sekolah">
    <x-slot:action>
        <a href="{{ route('siswa.import.form') }}" class="btn-secondary">
            <x-icon name="document" class="w-4 h-4"/> Import
        </a>
        <a href="{{ route('siswa.export.excel', request()->query()) }}" class="btn-secondary">
            <x-icon name="chart" class="w-4 h-4"/> Export Excel
        </a>
        <a href="{{ route('siswa.create') }}" class="btn-primary"><x-icon name="plus" class="w-4 h-4"/> Tambah Siswa</a>
    </x-slot:action>
</x-page-header>

<form class="card card-pad mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" class="input flex-1 min-w-[220px]" placeholder="Cari nama, NISN, atau NIS...">
    <select name="rombel" class="select w-full sm:w-52" onchange="this.form.submit()">
        <option value="">Semua Kelas</option>
        @foreach($rombelList as $rb)
            <option value="{{ $rb->id }}" @selected(request('rombel') == $rb->id)>{{ $rb->nama_rombel }}</option>
        @endforeach
    </select>
    <button class="btn-secondary"><x-icon name="search" class="w-4 h-4"/></button>
</form>

<form method="POST" action="{{ route('siswa.bulk-destroy') }}"
      x-data="{ selected: [], ids: @js($items->pluck('id')->map(fn ($id) => (string) $id)) }"
      @submit="if (!confirm(`Hapus ${selected.length} data siswa terpilih? Tindakan ini tidak bisa dibatalkan.`)) $event.preventDefault()">
    @csrf
    @method('DELETE')

    <div class="card card-pad mb-3 flex items-center justify-between bg-rose-50/60" x-show="selected.length > 0" x-cloak>
        <span class="text-sm font-medium text-rose-700" x-text="selected.length + ' data dipilih'"></span>
        <button type="submit" class="btn-danger">
            <x-icon name="trash" class="w-4 h-4"/> Hapus Terpilih
        </button>
    </div>

    <div class="card">
        <table class="table-modern">
            <thead><tr>
                <th class="w-10">
                    <input type="checkbox"
                           :checked="ids.length > 0 && selected.length === ids.length"
                           @change="selected = $event.target.checked ? [...ids] : []">
                </th>
                <th>NISN</th><th>Nama Siswa</th><th>JK</th><th>Kelas Sekarang</th><th>Status</th><th></th>
            </tr></thead>
            <tbody>
            @forelse($items as $s)
                <tr>
                    <td><input type="checkbox" name="ids[]" value="{{ $s->id }}" x-model="selected"></td>
                    <td class="font-mono text-xs">{{ $s->nisn }}</td>
                    <td class="flex items-center gap-2 font-semibold text-ink-900">
                        <x-avatar :src="$s->profile_photo_url" :name="$s->nama_siswa" size="w-8 h-8"/>
                        <div>
                            {{ $s->nama_siswa }}
                            <div class="text-xs text-ink-500 font-normal">NIS: {{ $s->nis ?: '—' }}</div>
                        </div>
                    </td>
                    <td>{{ $s->jenis_kelamin }}</td>
                    <td>{{ optional($s->rombelSekarang?->rombel ?? null)->nama_rombel ?? '—' }}</td>
                    <td>
                        @if($s->is_aktif)<span class="badge-success">Aktif</span>@else<span class="badge-muted">Non-aktif</span>@endif
                        @if($s->is_terkunci)
                            <span class="badge-danger block mt-1 w-fit">Terkunci</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($s->is_terkunci)
                            <button type="button" class="btn-ghost p-2 text-amber-600" title="Buka Kunci Akun"
                                    onclick="if(confirm('Buka kunci akun {{ $s->nama_siswa }} sekarang?')) document.getElementById('unlock-siswa-{{ $s->id }}').submit()">
                                <x-icon name="key"/>
                            </button>
                        @endif
                        <a href="{{ route('siswa.edit', $s) }}" class="btn-ghost p-2"><x-icon name="edit"/></a>
                        <button type="button" class="btn-ghost p-2 text-rose-600"
                                onclick="if(confirm('Hapus siswa ini?')) document.getElementById('destroy-siswa-{{ $s->id }}').submit()">
                            <x-icon name="trash"/>
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-8 text-ink-500">Belum ada data siswa.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</form>
<div class="mt-4">{{ $items->links() }}</div>

{{-- Form terpisah utk unlock/hapus per-baris, supaya tidak nested di dalam form bulk di atas --}}
@foreach($items as $s)
    @if($s->is_terkunci)
        <form id="unlock-siswa-{{ $s->id }}" method="POST" action="{{ route('siswa.unlock', $s) }}" class="hidden">@csrf</form>
    @endif
    <form id="destroy-siswa-{{ $s->id }}" method="POST" action="{{ route('siswa.destroy', $s) }}" class="hidden">@csrf @method('DELETE')</form>
@endforeach
@endsection
