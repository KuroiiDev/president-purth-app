@extends('admin.app')

@section('content')
<div class="sm:flex sm:items-center sm:justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Riwayat Perubahan Konten</h2>
        <p class="mt-1 text-sm text-gray-500">Audit log dan riwayat revisi konten beranda rumah sakit per sesi simpan (batch).</p>
    </div>
</div>

<!-- Filter Section -->
<div class="bg-white p-5 rounded-lg shadow-sm ring-1 ring-gray-900/5 mb-6">
    <form method="GET" action="{{ route('admin.history.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 items-end">
        <div>
            <label for="user_id" class="block text-xs font-medium text-gray-700">Pengguna</label>
            <select name="user_id" id="user_id" class="mt-1 block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-secondary focus:ring-secondary">
                <option value="">Semua Pengguna</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }} ({{ $u->role?->label() ?? 'User' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="type" class="block text-xs font-medium text-gray-700">Jenis Data</label>
            <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-secondary focus:ring-secondary">
                <option value="">Semua Jenis Data</option>
                <option value="doctors" {{ request('type') === 'doctors' ? 'selected' : '' }}>Dokter</option>
                <option value="cards" {{ request('type') === 'cards' ? 'selected' : '' }}>Kartu Layanan</option>
                <option value="testimonies" {{ request('type') === 'testimonies' ? 'selected' : '' }}>Testimoni</option>
                <option value="texts" {{ request('type') === 'texts' ? 'selected' : '' }}>Teks Beranda</option>
            </select>
        </div>

        <div>
            <label for="start_date" class="block text-xs font-medium text-gray-700">Dari Tanggal</label>
            <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" class="mt-1 block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-secondary focus:ring-secondary">
        </div>

        <div>
            <label for="end_date" class="block text-xs font-medium text-gray-700">Sampai Tanggal</label>
            <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" class="mt-1 block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-secondary focus:ring-secondary">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="flex-1 rounded-md bg-secondary px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-secondary-light">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
            @if(request()->hasAny(['user_id', 'type', 'start_date', 'end_date']))
                <a href="{{ route('admin.history.index') }}" class="rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Table Batches -->
<div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider sm:pl-6">Waktu</th>
                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pengguna</th>
                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ringkasan Perubahan</th>
                <th scope="col" class="px-3 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah Revisi</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @forelse($batches as $batch)
                @php
                    $batchRevisions = $revisionsByBatch->get($batch->batch_id, collect());
                    $first = $batchRevisions->first();
                    $user = $first?->user;
                    $createdAt = $first?->created_at;

                    $summaryCounts = [];
                    foreach ($batchRevisions as $rev) {
                        $label = $rev->getModelNameLabel();
                        $summaryCounts[$label] = ($summaryCounts[$label] ?? 0) + 1;
                    }
                    $summaryParts = [];
                    foreach ($summaryCounts as $name => $count) {
                        $summaryParts[] = "$count $name";
                    }
                    $isRollback = !empty($first?->meta['rolled_back_batch']);
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                        <div class="font-medium text-gray-900">
                            {{ $createdAt ? $createdAt->translatedFormat('d M Y, H:i') : '-' }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $createdAt ? $createdAt->diffForHumans() : '' }}
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
                        <div class="font-medium text-gray-900">{{ $user?->name ?? 'Sistem' }}</div>
                        <div class="text-xs text-gray-500">{{ $user?->email ?? ($first?->ip_address ?? '-') }}</div>
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-700">
                        <div class="flex items-center gap-x-2">
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $isRollback ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $isRollback ? 'Rollback' : 'Sesi Simpan' }}
                            </span>
                            <span class="font-medium text-gray-900">
                                {{ implode(', ', $summaryParts) }}
                            </span>
                        </div>
                        @if($isRollback)
                            <div class="text-xs text-gray-500 mt-0.5">
                                Mengembalikan batch <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">{{ substr($first->meta['rolled_back_batch'], 0, 8) }}...</code>
                            </div>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-center font-semibold text-gray-900">
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                            {{ $batchRevisions->count() }} item
                        </span>
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <a href="{{ route('admin.history.show', $batch->batch_id) }}" class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-secondary shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <i class="fas fa-eye mr-1"></i> Rincian Diff
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-12 text-center text-sm text-gray-500">
                        <i class="fas fa-history text-3xl text-gray-300 mb-3 block"></i>
                        Tidak ada catatan riwayat perubahan yang sesuai kriteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($batches->hasPages())
        <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
            {{ $batches->links() }}
        </div>
    @endif
</div>
@endsection
