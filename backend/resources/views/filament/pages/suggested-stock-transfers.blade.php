<x-filament-panels::page>
    @php
        $summary = $this->getFleetSummaryData();
    @endphp

    {{-- Fleet & Capital Rebalancing Metric Cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 0.5rem;">
        
        {{-- Card 1: Total Produk Kritis --}}
        <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 0.8rem; font-weight: 600; color: #64748b;">Produk Siap Dimutasi</span>
                <span style="display: flex; height: 2rem; width: 2rem; align-items: center; justify-content: center; border-radius: 0.5rem; background-color: rgba(147, 51, 234, 0.1); color: #9333ea;">
                    <x-filament::icon icon="heroicon-o-arrows-right-left" class="h-5 w-5" />
                </span>
            </div>
            <div style="font-size: 1.5rem; font-weight: 800; color: inherit; margin-top: 0.5rem;">
                {{ number_format($summary['total_items'] ?? 0, 0, ',', '.') }}
                <span style="font-size: 0.85rem; font-weight: 500; color: #64748b;">SKU</span>
            </div>
            <p style="font-size: 0.75rem; color: #9333ea; margin-top: 0.25rem;">
                Laris di satu cabang, macet di cabang lain
            </p>
        </div>

        {{-- Card 2: Total Unit Fisik Siap Angkut --}}
        <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 0.8rem; font-weight: 600; color: #64748b;">Estimasi Muatan Fisik</span>
                <span style="display: flex; height: 2rem; width: 2rem; align-items: center; justify-content: center; border-radius: 0.5rem; background-color: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                    <x-filament::icon icon="heroicon-o-truck" class="h-5 w-5" />
                </span>
            </div>
            <div style="font-size: 1.5rem; font-weight: 800; color: inherit; margin-top: 0.5rem;">
                {{ number_format($summary['total_units'] ?? 0, 0, ',', '.') }}
                <span style="font-size: 0.85rem; font-weight: 500; color: #64748b;">PCS</span>
            </div>
            <p style="font-size: 0.75rem; color: #3b82f6; margin-top: 0.25rem;">
                Siap diangkut kendaraan keliling / armada
            </p>
        </div>

        {{-- Card 3: Modal Dead Stock yang Diselamatkan --}}
        <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 0.8rem; font-weight: 600; color: #64748b;">Modal Mati Diselamatkan</span>
                <span style="display: flex; height: 2rem; width: 2rem; align-items: center; justify-content: center; border-radius: 0.5rem; background-color: rgba(16, 185, 129, 0.1); color: #10b981;">
                    <x-filament::icon icon="heroicon-o-banknotes" class="h-5 w-5" />
                </span>
            </div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #10b981; margin-top: 0.5rem;">
                Rp {{ number_format($summary['total_capital_freed'] ?? 0, 0, ',', '.') }}
            </div>
            <p style="font-size: 0.75rem; color: #059669; margin-top: 0.25rem;">
                Hemat kas perusahaan tanpa buat PO baru
            </p>
        </div>

        {{-- Card 4: Rute Cabang Aktif --}}
        <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 0.8rem; font-weight: 600; color: #64748b;">Rute Siap Distribusi</span>
                <span style="display: flex; height: 2rem; width: 2rem; align-items: center; justify-content: center; border-radius: 0.5rem; background-color: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                    <x-filament::icon icon="heroicon-o-map-pin" class="h-5 w-5" />
                </span>
            </div>
            <div style="font-size: 1.5rem; font-weight: 800; color: inherit; margin-top: 0.5rem;">
                {{ number_format($summary['total_routes'] ?? 0, 0, ',', '.') }}
                <span style="font-size: 0.85rem; font-weight: 500; color: #64748b;">Rute Pasangan</span>
            </div>
            <p style="font-size: 0.75rem; color: #d97706; margin-top: 0.25rem;">
                P2P Store-to-Store (Tanpa Gudang DC)
            </p>
        </div>

    </div>

    {{-- Main Filament Table --}}
    {{ $this->table }}
</x-filament-panels::page>
