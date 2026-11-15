@extends('admin.app')

@section('content')
<div class="sm:flex sm:items-center sm:justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Arsip Data Terhapus</h2>
        <p class="mt-1 text-sm text-gray-500">Data konten yang telah dihapus sementara (soft delete). Anda dapat memulihkan kembali atau menghapusnya secara permanen.</p>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="border-b border-gray-200 mb-6">
    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <a href="{{ route('admin.archive.index', ['tab' => 'doctors']) }}"
           class="{{ $currentTab === 'doctors' ? 'border-secondary text-secondary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
            <i class="fas fa-user-md mr-1.5"></i> Dokter / Leaders
            <span class="ml-2 rounded-full bg-gray-100 py-0.5 px-2.5 text-xs font-semibold text-gray-600">
                {{ $trashedDoctors->total() }}
            </span>
        </a>

        <a href="{{ route('admin.archive.index', ['tab' => 'cards']) }}"
           class="{{ $currentTab === 'cards' ? 'border-secondary text-secondary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
            <i class="fas fa-th-large mr-1.5"></i> Kartu Layanan
            <span class="ml-2 rounded-full bg-gray-100 py-0.5 px-2.5 text-xs font-semibold text-gray-600">
                {{ $trashedCards->total() }}
            </span>
        </a>

        <a href="{{ route('admin.archive.index', ['tab' => 'testimonies']) }}"
           class="{{ $currentTab === 'testimonies' ? 'border-secondary text-secondary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
            <i class="fas fa-comment-alt mr-1.5"></i> Testimoni
            <span class="ml-2 rounded-full bg-gray-100 py-0.5 px-2.5 text-xs font-semibold text-gray-600">
                {{ $trashedTestimonies->total() }}
            </span>
        </a>
    </nav>
</div>

<!-- Tab Content -->
@if($currentTab === 'doctors')
    <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider sm:pl-6">Foto & Nama</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jabatan / Spesialis</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu Dihapus</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($trashedDoctors as $doc)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    @if($doc->image)
                                        <img class="h-10 w-10 rounded-full object-cover" src="{{ asset('images/profile/' . $doc->image) }}" alt="">
                                    @else
                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs">
                                            No pic
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="font-medium text-gray-900">{{ $doc->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $doc->title }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $doc->deleted_at ? $doc->deleted_at->translatedFormat('d M Y, H:i') : '-' }}
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-2">
                            <form action="{{ route('admin.archive.restore', ['type' => 'doctors', 'id' => $doc->id]) }}" method="POST" class="inline-block">
                                @csrf
                                <button type="submit" class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 shadow-sm ring-1 ring-inset ring-emerald-300 hover:bg-emerald-100">
                                    <i class="fas fa-trash-restore mr-1"></i> Pulihkan
                                </button>
                            </form>
                            @if(auth()->user()?->isAdmin())
                                <form action="{{ route('admin.archive.force-delete', ['type' => 'doctors', 'id' => $doc->id]) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokter ini secara permanen? Data tidak dapat dikembalikan lagi.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-100">
                                        <i class="fas fa-times-circle mr-1"></i> Hapus Permanen
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-sm text-gray-500">
                            Tidak ada data dokter di dalam arsip.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($trashedDoctors->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
                {{ $trashedDoctors->appends(['tab' => 'doctors'])->links() }}
            </div>
        @endif
    </div>
@elseif($currentTab === 'cards')
    <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider sm:pl-6">Bagian & Ikon</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Judul</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu Dihapus</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($trashedCards as $card)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                            <div class="flex items-center gap-x-2">
                                <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600 font-mono">{{ $card->section }}</span>
                                <i class="{{ $card->icon }} text-gray-500"></i>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">{{ $card->title }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $card->deleted_at ? $card->deleted_at->translatedFormat('d M Y, H:i') : '-' }}
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-2">
                            <form action="{{ route('admin.archive.restore', ['type' => 'cards', 'id' => $card->id]) }}" method="POST" class="inline-block">
                                @csrf
                                <button type="submit" class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 shadow-sm ring-1 ring-inset ring-emerald-300 hover:bg-emerald-100">
                                    <i class="fas fa-trash-restore mr-1"></i> Pulihkan
                                </button>
                            </form>
                            @if(auth()->user()?->isAdmin())
                                <form action="{{ route('admin.archive.force-delete', ['type' => 'cards', 'id' => $card->id]) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kartu ini secara permanen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-100">
                                        <i class="fas fa-times-circle mr-1"></i> Hapus Permanen
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-sm text-gray-500">
                            Tidak ada data kartu layanan di dalam arsip.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($trashedCards->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
                {{ $trashedCards->appends(['tab' => 'cards'])->links() }}
            </div>
        @endif
    </div>
@elseif($currentTab === 'testimonies')
    <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider sm:pl-6">Nama</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jabatan / Profesi</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu Dihapus</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($trashedTestimonies as $testi)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6 font-medium text-gray-900">{{ $testi->name }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $testi->title }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $testi->deleted_at ? $testi->deleted_at->translatedFormat('d M Y, H:i') : '-' }}
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-2">
                            <form action="{{ route('admin.archive.restore', ['type' => 'testimonies', 'id' => $testi->id]) }}" method="POST" class="inline-block">
                                @csrf
                                <button type="submit" class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 shadow-sm ring-1 ring-inset ring-emerald-300 hover:bg-emerald-100">
                                    <i class="fas fa-trash-restore mr-1"></i> Pulihkan
                                </button>
                            </form>
                            @if(auth()->user()?->isAdmin())
                                <form action="{{ route('admin.archive.force-delete', ['type' => 'testimonies', 'id' => $testi->id]) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus testimoni ini secara permanen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-100">
                                        <i class="fas fa-times-circle mr-1"></i> Hapus Permanen
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-sm text-gray-500">
                            Tidak ada data testimoni di dalam arsip.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($trashedTestimonies->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
                {{ $trashedTestimonies->appends(['tab' => 'testimonies'])->links() }}
            </div>
        @endif
    </div>
@endif
@endsection
