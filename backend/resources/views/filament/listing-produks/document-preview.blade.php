@php
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    $exists = !empty($path) && $disk->exists($path);
    $url = $exists ? $disk->url($path) : null;
    $ext = strtolower(pathinfo($path ?? '', PATHINFO_EXTENSION));
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    $isPdf = $ext === 'pdf';
    $isSpreadsheet = in_array($ext, ['xlsx', 'xls', 'csv']);
    $fileName = basename($path ?? '');
    $fileSize = $exists ? round($disk->size($path) / 1024, 1) . ' KB' : null;
@endphp

<div class="space-y-4">
    @if(!$exists)
        <div class="p-6 text-center text-gray-500 bg-gray-50 dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-700">
            <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="font-medium text-sm">Berkas dokumen fisik tidak ditemukan di storage server.</p>
            <p class="text-xs text-gray-400 mt-1">Path: {{ $path }}</p>
        </div>
    @elseif($isImage)
        <div class="flex flex-col items-center justify-center p-2 bg-gray-900/5 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-800">
            <a href="{{ $url }}" target="_blank" title="Klik untuk membuka ukuran penuh di tab baru">
                <img src="{{ $url }}" alt="Dokumen Listing" class="max-h-[68vh] w-auto object-contain rounded-lg shadow-sm hover:opacity-95 transition" />
            </a>
            <div class="flex items-center justify-between w-full mt-3 pt-3 border-t border-gray-200 dark:border-gray-700/60 px-2 text-xs text-gray-500">
                <span class="font-mono truncate max-w-xs">{{ $fileName }} ({{ $fileSize }})</span>
                <div class="flex gap-2">
                    <a href="{{ $url }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        Buka Ukuran Penuh
                    </a>
                    <a href="{{ $url }}" download="{{ $fileName }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-200 font-medium text-xs transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Unduh
                    </a>
                </div>
            </div>
        </div>
    @elseif($isPdf)
        <div class="space-y-3">
            <div class="w-full h-[65vh] rounded-lg overflow-hidden border border-gray-300 dark:border-gray-700 shadow-sm bg-gray-100 dark:bg-gray-900">
                <iframe src="{{ $url }}#toolbar=1" class="w-full h-full" frameborder="0"></iframe>
            </div>
            <div class="flex items-center justify-between px-2 text-xs text-gray-500">
                <span class="font-mono">{{ $fileName }} ({{ $fileSize }})</span>
                <div class="flex gap-2">
                    <a href="{{ $url }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        Buka di Tab Baru
                    </a>
                    <a href="{{ $url }}" download="{{ $fileName }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-200 font-medium text-xs transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Unduh PDF
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="p-8 text-center bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700 space-y-4">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-400 flex items-center justify-center shadow-inner">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <h4 class="font-bold text-gray-900 dark:text-gray-100 text-base">{{ $fileName }}</h4>
                <p class="text-xs text-gray-500 mt-1">Dokumen Spreadsheet ({{ strtoupper($ext) }}) &bull; Ukuran: {{ $fileSize }}</p>
                <p class="text-xs text-gray-400 mt-2 max-w-md mx-auto">Dokumen ini telah diproses untuk ekstraksi daftar barang listing dan tersimpan sebagai arsip.</p>
            </div>
            <div class="pt-2">
                <a href="{{ $url }}" download="{{ $fileName }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm shadow transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Unduh Berkas Spreadsheet ({{ strtoupper($ext) }})
                </a>
            </div>
        </div>
    @endif
</div>
