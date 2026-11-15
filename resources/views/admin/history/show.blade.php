@extends('admin.app')

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-x-3 mb-2">
        <a href="{{ route('admin.history.index') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-700">
            &larr; Kembali ke Daftar Riwayat
        </a>
    </div>
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                Rincian Batch Revisi
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Batch ID: <code class="bg-gray-100 px-2 py-0.5 rounded text-xs text-gray-700">{{ $batch }}</code>
                &bull; Waktu: {{ $revisions->first()?->created_at?->translatedFormat('d M Y, H:i:s') }}
                &bull; Oleh: {{ $revisions->first()?->user?->name ?? 'Sistem' }}
            </p>
        </div>
    </div>
</div>

@if(!empty($conflicts))
    <div class="rounded-md bg-amber-50 p-4 mb-6 border-l-4 border-amber-400">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-amber-500 text-lg"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-semibold text-amber-800">
                    Peringatan: Terdeteksi Potensi Konflik Data ({{ count($conflicts) }})
                </h3>
                <div class="mt-2 text-sm text-amber-700">
                    <p>Beberapa data pada batch ini telah mengalami perubahan lagi setelah revisi ini dibuat. Jika Anda melakukan rollback, perubahan terbaru tersebut akan ditimpa.</p>
                    <ul role="list" class="list-disc pl-5 space-y-1 mt-2 text-xs">
                        @foreach($conflicts as $c)
                            <li>{{ $c['message'] }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Form Rollback -->
<div class="bg-white p-5 rounded-lg shadow-sm ring-1 ring-gray-900/5 mb-8">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h3 class="text-base font-semibold leading-6 text-gray-900">Rollback Perubahan Batch Ini</h3>
            <p class="mt-1 text-xs text-gray-500">
                Rollback akan membalikkan semua perubahan pada batch ini secara otomatis dan dicatat sebagai batch baru.
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <form method="POST" action="{{ route('admin.history.rollback', $batch) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan/rollback seluruh perubahan pada batch ini?');">
                @csrf
                @if(!empty($conflicts))
                    <div class="mb-3 flex items-center gap-x-2">
                        <input type="checkbox" id="confirm_conflict" name="confirm_conflict" value="1" required class="h-4 w-4 rounded border-gray-300 text-secondary focus:ring-secondary">
                        <label for="confirm_conflict" class="text-xs font-semibold text-amber-800">
                            Saya memahami risiko konflik dan tetap ingin rollback paksa
                        </label>
                    </div>
                @endif
                <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                    <i class="fas fa-undo mr-2"></i> Jalankan Rollback
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Daftar Revisi Diff -->
<div class="space-y-6">
    <h3 class="text-lg font-semibold text-gray-900">Daftar Revisi Per Item ({{ $revisions->count() }})</h3>

    @foreach($revisions as $index => $rev)
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 sm:px-6 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-x-3">
                    <span class="text-xs font-bold text-gray-500">#{{ $index + 1 }}</span>
                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium 
                        @if($rev->event === 'created') bg-green-100 text-green-800
                        @elseif($rev->event === 'updated') bg-blue-100 text-blue-800
                        @elseif($rev->event === 'deleted' || $rev->event === 'force_deleted') bg-red-100 text-red-800
                        @elseif($rev->event === 'restored') bg-emerald-100 text-emerald-800
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ $rev->getEventBadge() }}
                    </span>
                    <h4 class="text-sm font-semibold text-gray-900">
                        {{ $rev->getModelNameLabel() }}: {{ $rev->getRecordLabel() }}
                    </h4>
                </div>
                <div class="text-xs text-gray-500">
                    ID: {{ $rev->revisionable_id }}
                </div>
            </div>

            <div class="p-4 sm:p-6">
                @if($rev->event === 'updated')
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-2 px-3 text-left font-semibold text-gray-600 w-1/4">Field</th>
                                    <th class="py-2 px-3 text-left font-semibold text-red-700 w-3/8 bg-red-50/50">Nilai Lama</th>
                                    <th class="py-2 px-3 text-left font-semibold text-green-700 w-3/8 bg-green-50/50">Nilai Baru</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @php
                                    $changedFields = array_keys($rev->new_values ?? []);
                                @endphp
                                @foreach($changedFields as $field)
                                    @php
                                        $oldVal = $rev->old_values[$field] ?? null;
                                        $newVal = $rev->new_values[$field] ?? null;
                                    @endphp
                                    <tr>
                                        <td class="py-2 px-3 font-medium text-gray-800">{{ $field }}</td>
                                        <td class="py-2 px-3 text-gray-700 bg-red-50/20 font-mono whitespace-pre-wrap break-all">
                                            {{ is_null($oldVal) ? '(null)' : Str::limit((string) $oldVal, 200) }}
                                        </td>
                                        <td class="py-2 px-3 text-gray-700 bg-green-50/20 font-mono whitespace-pre-wrap break-all">
                                            {{ is_null($newVal) ? '(null)' : Str::limit((string) $newVal, 200) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif($rev->event === 'created' || $rev->event === 'restored')
                    <div>
                        <p class="text-xs font-semibold text-gray-600 mb-2">Snapshot Data yang Ditambahkan:</p>
                        <div class="bg-gray-50 p-3 rounded text-xs font-mono overflow-x-auto">
                            @foreach($rev->new_values ?? [] as $k => $v)
                                <div class="py-0.5"><span class="text-gray-500 font-semibold">{{ $k }}:</span> {{ is_null($v) ? 'null' : Str::limit((string) $v, 150) }}</div>
                            @endforeach
                        </div>
                    </div>
                @elseif($rev->event === 'deleted' || $rev->event === 'force_deleted')
                    <div>
                        <p class="text-xs font-semibold text-gray-600 mb-2">Snapshot Data Sebelum Dihapus:</p>
                        <div class="bg-gray-50 p-3 rounded text-xs font-mono overflow-x-auto">
                            @foreach($rev->old_values ?? [] as $k => $v)
                                <div class="py-0.5"><span class="text-gray-500 font-semibold">{{ $k }}:</span> {{ is_null($v) ? 'null' : Str::limit((string) $v, 150) }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection
