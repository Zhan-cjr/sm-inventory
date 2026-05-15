<div class="mb-6 space-y-4">
    <!-- Filter Section -->
    <div class="grid grid-cols-2 gap-4">
        <div class="p-4 border-2 border-blue-300 bg-blue-50 rounded shadow-sm">
            <h4 class="text-blue-800 font-bold border-b border-blue-200 mb-2 pb-1 text-center uppercase text-xs">Kelompok Barang</h4>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-blue-900">Kelompok</span>
                    <select class="border border-blue-300 rounded px-2 py-1 bg-white text-xs w-2/3">
                        <option>All</option>
                    </select>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-blue-900">Sub Kelompok</span>
                    <select class="border border-blue-300 rounded px-2 py-1 bg-white text-xs w-2/3">
                        <option>All</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="p-4 border-2 border-blue-300 bg-blue-50 rounded shadow-sm">
            <h4 class="text-blue-800 font-bold border-b border-blue-200 mb-2 pb-1 text-center uppercase text-xs">Status Barang</h4>
            <div class="flex justify-center gap-4 text-sm font-medium text-blue-900">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="status" checked class="text-blue-600 focus:ring-blue-500"> Active
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="status" class="text-blue-600 focus:ring-blue-500"> Discontinued
                </label>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="flex items-center gap-4 bg-gray-100 p-2 border border-gray-300 rounded shadow-inner">
        <span class="text-blue-900 font-bold text-sm min-w-max">Kata Kunci</span>
        <input type="text" class="flex-1 border border-gray-400 rounded px-3 py-1 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Ketik pencarian...">
        <button class="bg-gray-200 border border-gray-400 px-6 py-1 rounded text-sm font-bold hover:bg-gray-300 transition shadow-sm">Cari</button>
    </div>

    <!-- Alphabet Buttons -->
    <div class="flex flex-wrap gap-1 bg-white p-2 border border-gray-300 rounded shadow-sm overflow-x-auto">
        @foreach(range('A', 'Z') as $char)
            <button class="w-7 h-7 flex items-center justify-center border border-blue-300 rounded text-xs font-bold text-blue-700 hover:bg-blue-600 hover:text-white transition shadow-sm bg-blue-50">
                {{ $char }}
            </button>
        @endforeach
        <button class="h-7 px-2 flex items-center justify-center border border-red-300 rounded text-[10px] font-bold text-red-700 hover:bg-red-600 hover:text-white transition shadow-sm bg-red-50 ml-auto">
            SM
        </button>
    </div>
</div>
