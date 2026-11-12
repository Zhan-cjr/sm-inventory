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

<div style="width: 100%; margin-top: 6px;">
    @if(!$exists)
        <div style="padding: 1.5rem; text-align: center; color: #64748b; background-color: #f8fafc; border-radius: 0.5rem; border: 1px dashed #cbd5e1;">
            <svg style="width: 32px; height: 32px; margin: 0 auto 8px auto; color: #94a3b8; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p style="font-size: 0.875rem; font-weight: 500; margin: 0;">Berkas dokumen fisik tidak ditemukan di storage server.</p>
            <p style="font-size: 0.75rem; color: #94a3b8; margin: 4px 0 0 0;">Path: {{ $path }}</p>
        </div>
    @elseif($isImage)
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 10px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem;">
            <a href="{{ $url }}" target="_blank" title="Klik untuk membuka ukuran penuh di tab baru" style="display: block; max-width: 100%; text-align: center;">
                <img src="{{ $url }}" alt="Dokumen Listing" style="max-height: 520px; max-width: 100%; width: auto; height: auto; object-fit: contain; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" />
            </a>
            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; margin-top: 12px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 0.75rem; color: #64748b; flex-wrap: wrap; gap: 8px;">
                <span style="font-family: monospace; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 280px;">📄 {{ $fileName }} ({{ $fileSize }})</span>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <a href="{{ $url }}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 6px; background-color: #4f46e5; color: #ffffff; font-weight: 600; font-size: 0.75rem; text-decoration: none; box-shadow: 0 1px 2px rgba(79, 70, 229, 0.2);">
                        <svg style="width: 14px; height: 14px; min-width: 14px; min-height: 14px; max-width: 14px; max-height: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span>Buka Ukuran Penuh</span>
                    </a>
                    <a href="{{ $url }}" download="{{ $fileName }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 6px; background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-weight: 600; font-size: 0.75rem; text-decoration: none;">
                        <svg style="width: 14px; height: 14px; min-width: 14px; min-height: 14px; max-width: 14px; max-height: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Unduh</span>
                    </a>
                </div>
            </div>
        </div>
    @elseif($isPdf)
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="width: 100%; height: 550px; border-radius: 0.5rem; overflow: hidden; border: 1px solid #cbd5e1; background: #0f172a;">
                <iframe src="{{ $url }}#toolbar=1" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color: #64748b;">
                <span style="font-family: monospace;">📕 {{ $fileName }} ({{ $fileSize }})</span>
                <div style="display: flex; gap: 8px;">
                    <a href="{{ $url }}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 6px; background-color: #4f46e5; color: #ffffff; font-weight: 600; font-size: 0.75rem; text-decoration: none;">
                        <svg style="width: 14px; height: 14px; min-width: 14px; min-height: 14px; max-width: 14px; max-height: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span>Buka di Tab Baru</span>
                    </a>
                    <a href="{{ $url }}" download="{{ $fileName }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 6px; background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-weight: 600; font-size: 0.75rem; text-decoration: none;">
                        <svg style="width: 14px; height: 14px; min-width: 14px; min-height: 14px; max-width: 14px; max-height: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Unduh PDF</span>
                    </a>
                </div>
            </div>
        </div>
    @else
        <div style="padding: 2rem; text-align: center; background-color: #f8fafc; border-radius: 0.75rem; border: 1px solid #e2e8f0;">
            <div style="width: 48px; height: 48px; margin: 0 auto 12px auto; border-radius: 0.75rem; background-color: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center;">
                <svg style="width: 24px; height: 24px; min-width: 24px; min-height: 24px; max-width: 24px; max-height: 24px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h4 style="font-weight: 700; color: #0f172a; font-size: 0.95rem; margin: 0 0 4px 0;">{{ $fileName }}</h4>
            <p style="font-size: 0.75rem; color: #64748b; margin: 0 0 12px 0;">Dokumen Spreadsheet ({{ strtoupper($ext) }}) &bull; Ukuran: {{ $fileSize }}</p>
            <div style="padding-top: 6px;">
                <a href="{{ $url }}" download="{{ $fileName }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 7px 16px; border-radius: 6px; background-color: #059669; color: #ffffff; font-weight: 600; font-size: 0.8125rem; text-decoration: none; box-shadow: 0 1px 2px rgba(5, 150, 105, 0.2);">
                    <svg style="width: 16px; height: 16px; min-width: 16px; min-height: 16px; max-width: 16px; max-height: 16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Unduh Berkas Spreadsheet ({{ strtoupper($ext) }})</span>
                </a>
            </div>
        </div>
    @endif
</div>
