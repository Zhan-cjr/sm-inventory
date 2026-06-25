<div class="flex flex-wrap gap-4 justify-center">
    @foreach($images as $image)
        <div class="flex flex-col items-center">
            <a href="{{ Storage::url($image) }}" target="_blank" class="border rounded p-2 block hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                @if(\Illuminate\Support\Str::endsWith(strtolower($image), '.pdf'))
                    <div class="h-32 w-32 flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded">
                        <span class="text-gray-500 font-bold">PDF FILE</span>
                    </div>
                @else
                    <img src="{{ Storage::url($image) }}" class="h-48 object-contain rounded" alt="Bukti Faktur" />
                @endif
            </a>
            <a href="{{ Storage::url($image) }}" target="_blank" class="text-xs text-blue-500 mt-2 hover:underline">Buka di Tab Baru</a>
        </div>
    @endforeach
</div>
