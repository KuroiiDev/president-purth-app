@extends('admin.app')

@section('content')
<div class="md:flex md:items-center md:justify-between mb-8">
    <div class="min-w-0 flex-1">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Dashboard</h2>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Card 1 -->
    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-900/5">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-users text-gray-400 text-2xl h-6 w-6 flex items-center justify-center"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500">Total Users</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">{{ $userCount }}</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <a href="{{ route('admin.users.index') }}" class="font-medium text-secondary hover:text-secondary-light">View all</a>
            </div>
        </div>
    </div>

    <!-- Card 2 -->
    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-900/5">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-md text-gray-400 text-2xl h-6 w-6 flex items-center justify-center"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500">Total Leaders</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">{{ $doctorCount }}</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <a href="{{ route('admin.doctors.index') }}" class="font-medium text-secondary hover:text-secondary-light">View all</a>
            </div>
        </div>
    </div>
</div>

<!-- Widget Aktivitas Terbaru -->
<div class="mt-8">
    <div class="sm:flex sm:items-center sm:justify-between mb-4">
        <div>
            <h3 class="text-base font-semibold leading-6 text-gray-900">Aktivitas Terbaru</h3>
            <p class="mt-1 text-sm text-gray-500">5 riwayat perubahan data terakhir pada konten sistem.</p>
        </div>
        @if(auth()->user()?->isAdmin())
        <div class="mt-3 sm:mt-0">
            <a href="{{ route('admin.history.index') }}" class="text-sm font-semibold text-secondary hover:text-secondary-light">
                Lihat Semua Riwayat &rarr;
            </a>
        </div>
        @endif
    </div>

    <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
        <div class="divide-y divide-gray-200">
            @forelse($latestBatchIds as $batchId)
                @php
                    $revisions = $latestBatches->get($batchId, collect());
                    $first = $revisions->first();
                    $user = $first?->user;
                    $createdAt = $first?->created_at;

                    $summaryCounts = [];
                    foreach ($revisions as $rev) {
                        $label = $rev->getModelNameLabel();
                        $summaryCounts[$label] = ($summaryCounts[$label] ?? 0) + 1;
                    }
                    $summaryParts = [];
                    foreach ($summaryCounts as $name => $count) {
                        $summaryParts[] = "$count $name";
                    }
                    $isRollback = !empty($first?->meta['rolled_back_batch']);
                @endphp
                <div class="p-4 sm:px-6 hover:bg-gray-50 flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-x-3">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $isRollback ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $isRollback ? 'Rollback' : 'Perubahan' }}
                            </span>
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ implode(', ', $summaryParts) }}
                            </p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2 text-xs text-gray-500">
                            <span>Oleh {{ $user?->name ?? 'Sistem' }}</span>
                            <span>&bull;</span>
                            <span>{{ $createdAt ? $createdAt->diffForHumans() : '-' }}</span>
                        </div>
                    </div>
                    @if(auth()->user()?->isAdmin())
                    <div class="ml-4 shrink-0">
                        <a href="{{ route('admin.history.show', $batchId) }}" class="rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            Detail
                        </a>
                    </div>
                    @endif
                </div>
            @empty
                <div class="p-6 text-center text-sm text-gray-500">
                    Belum ada riwayat aktivitas konten yang tercatat.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
