<div wire:poll.1s>
    @if($import)
        @php
            $total = $import->total_rows > 0 ? $import->total_rows : 1;
            $processed = $import->processed_rows;
            $percentage = min(100, round(($processed / $total) * 100));
        @endphp
        <div style="position: fixed; bottom: 24px; right: 24px; z-index: 50; width: 320px; background-color: white; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e5e7eb; padding: 16px; font-family: sans-serif;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <div style="padding: 8px; background-color: #eff6ff; border-radius: 8px;">
                    <svg style="width: 20px; height: 20px; color: #3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                </div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin: 0;">Proses Import</h3>
                        <span style="font-size: 12px; font-weight: 700; color: #2563eb;">{{ $percentage }}%</span>
                    </div>
                    <p style="font-size: 10px; color: #6b7280; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">
                        {{ basename($import->file_name) }}
                    </p>
                </div>
            </div>
            
            <div style="width: 100%; background-color: #f3f4f6; border-radius: 9999px; height: 8px; margin-bottom: 8px; overflow: hidden;">
                <div style="background-color: #3b82f6; height: 8px; border-radius: 9999px; width: {{ $percentage }}%; transition: width 0.5s ease-out;"></div>
            </div>
            
            <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 500; color: #6b7280;">
                <span>{{ number_format($processed) }} / {{ number_format($import->total_rows) }} baris selesai</span>
            </div>
        </div>
    @endif
</div>
