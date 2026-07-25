<x-filament-panels::page>
    @php
        $items = $this->data['print_items'] ?? [];
        $totalItems = count($items);
        $totalCopies = collect($items)->sum(fn($i) => (int)($i['copies'] ?? 1));
        $estPricecardPages = ceil($totalCopies / 18);
    @endphp

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Section 1: Form Selector & Fast Product Picker -->
        <x-filament::section
            icon="heroicon-o-magnifying-glass"
            header-heading="Pencarian & Pengaturan Cetak"
            header-subheading="Pilih lokasi cabang, tipe tanggal, dan cari produk untuk dimasukkan ke antrean cetak."
        >
            {{ $this->form }}
        </x-filament::section>

        <!-- Section 2: Shopping Cart Queue Table -->
        <x-filament::section
            icon="heroicon-o-shopping-cart"
            header-heading="Keranjang Antrean Cetak Barcode & Pricecard"
            header-subheading="Kelola jumlah lembar cetak per produk sebelum mencetak ke kertas label tempel atau pricecard rak."
        >
            <x-slot name="headerActions">
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <span style="background: rgba(16,185,129,0.15); color: #10b981; font-size: 0.75rem; font-weight: 800; padding: 4px 12px; border-radius: 99px;">
                        📦 {{ $totalItems }} Produk
                    </span>
                    <span style="background: rgba(245,158,11,0.15); color: #f59e0b; font-size: 0.75rem; font-weight: 800; padding: 4px 12px; border-radius: 99px;">
                        📄 {{ $totalCopies }} Total Copies
                    </span>
                    <span style="background: rgba(99,102,241,0.15); color: #6366f1; font-size: 0.75rem; font-weight: 800; padding: 4px 12px; border-radius: 99px;">
                        🖨️ Est. {{ $estPricecardPages }} Hal A4
                    </span>
                </div>
            </x-slot>

            @if($totalItems > 0)
                <div style="overflow-x: auto; margin-top: 0.5rem;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(128,128,128,0.2); font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.7;">
                                <th style="padding: 12px 8px; text-align: center; width: 40px;">#</th>
                                <th style="padding: 12px;">Nama Produk & Kode</th>
                                <th style="padding: 12px; text-align: right;">Harga Jual</th>
                                <th style="padding: 12px; text-align: center; width: 220px;">Jumlah Cetak (Copies)</th>
                                <th style="padding: 12px; text-align: center; width: 70px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="border-bottom: 1px solid rgba(128,128,128,0.2);">
                            @foreach($items as $index => $item)
                                <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                                    <td style="padding: 14px 8px; text-align: center; font-weight: 800; opacity: 0.5;">
                                        {{ $index + 1 }}
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <div style="font-weight: 800; font-size: 0.98rem; line-height: 1.3;">
                                            {{ $item['name'] ?? '-' }}
                                        </div>
                                        <div style="display: flex; gap: 8px; margin-top: 4px; font-size: 0.75rem; font-family: monospace;">
                                            <span style="background: rgba(128,128,128,0.15); padding: 2px 8px; border-radius: 6px; font-weight: 600;">
                                                SKU: {{ $item['sku'] ?? '-' }}
                                            </span>
                                            <span style="background: rgba(16,185,129,0.15); color: #10b981; padding: 2px 8px; border-radius: 6px; font-weight: 800;">
                                                Barcode: {{ $item['barcode'] ?? '-' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 12px; text-align: right; font-weight: 900; font-size: 1.05rem; color: #10b981;">
                                        Rp {{ number_format((float)($item['price'] ?? 0), 0, ',', '.') }}
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                            <button 
                                                type="button" 
                                                wire:click="changeCopiesStep({{ $index }}, -1)"
                                                style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(128,128,128,0.3); background: rgba(128,128,128,0.1); font-weight: 900; cursor: pointer; color: inherit;"
                                            >-</button>
                                            
                                            <input 
                                                type="number" 
                                                min="1" 
                                                value="{{ $item['copies'] ?? 1 }}"
                                                wire:change="updateCopies({{ $index }}, $event.target.value)"
                                                style="width: 64px; text-align: center; padding: 6px; border-radius: 8px; border: 1px solid rgba(128,128,128,0.3); font-weight: 800; font-size: 0.95rem; background: transparent; color: inherit;"
                                            >

                                            <button 
                                                type="button" 
                                                wire:click="changeCopiesStep({{ $index }}, 1)"
                                                style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(128,128,128,0.3); background: rgba(128,128,128,0.1); font-weight: 900; cursor: pointer; color: inherit;"
                                            >+</button>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 12px; text-align: center;">
                                        <button 
                                            type="button" 
                                            wire:click="removeItem({{ $index }})"
                                            style="background: none; border: none; cursor: pointer; color: #ef4444; padding: 6px;"
                                            title="Hapus Produk dari Antrean"
                                        >
                                            <x-filament::icon icon="heroicon-m-trash" style="width: 20px; height: 20px;" class="text-rose-500" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Footer Toolbar -->
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(128,128,128,0.15); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; font-weight: 700;">
                        <span style="opacity: 0.7;">Ubah Kuantitas Massal:</span>
                        <button type="button" wire:click="batchSetCopies(1)" style="padding: 4px 12px; border-radius: 8px; border: 1px solid rgba(128,128,128,0.25); background: rgba(128,128,128,0.1); cursor: pointer; font-weight: 700; color: inherit;">
                            Set All = 1
                        </button>
                        <button type="button" wire:click="batchSetCopies(5)" style="padding: 4px 12px; border-radius: 8px; border: 1px solid rgba(128,128,128,0.25); background: rgba(128,128,128,0.1); cursor: pointer; font-weight: 700; color: inherit;">
                            Set All = 5
                        </button>
                        <button type="button" wire:click="batchSetCopies(10)" style="padding: 4px 12px; border-radius: 8px; border: 1px solid rgba(128,128,128,0.25); background: rgba(128,128,128,0.1); cursor: pointer; font-weight: 700; color: inherit;">
                            Set All = 10
                        </button>
                    </div>

                    <button 
                        type="button" 
                        wire:click="clearAll" 
                        wire:confirm="Apakah Anda yakin ingin mengosongkan seluruh antrean?"
                        style="background: none; border: none; color: #ef4444; font-weight: 800; font-size: 0.82rem; cursor: pointer; display: flex; align-items: center; gap: 4px;"
                    >
                        <x-filament::icon icon="heroicon-m-trash" style="width: 16px; height: 16px;" class="text-rose-500" />
                        Kosongkan Seluruh Antrean
                    </button>
                </div>
            @else
                <!-- Empty Shopping Cart State -->
                <div style="padding: 3rem 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(128,128,128,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                        <x-filament::icon icon="heroicon-o-shopping-cart" style="width: 32px; height: 32px;" class="text-gray-400" />
                    </div>
                    <h4 style="font-size: 1.1rem; font-weight: 800; margin: 0 0 4px 0;">Antrean Cetak Masih Kosong</h4>
                    <p style="font-size: 0.88rem; opacity: 0.7; margin: 0; max-width: 450px;">
                        Ketik nama produk, SKU, atau scan barcode pada kolom pencarian di atas untuk menambahkan produk ke keranjang antrean cetak.
                    </p>
                </div>
            @endif
        </x-filament::section>

        <!-- Section 3: Launch Print Actions -->
        <x-filament::section
            icon="heroicon-o-printer"
            header-heading="Proses Cetak Barcode & Pricecard"
            header-subheading="Pilih format cetak yang diinginkan. Hasil cetak akan otomatis terbuka di tab baru."
        >
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 1rem;">
                    <x-filament::button wire:click="printLabel" color="success" icon="heroicon-m-qr-code" size="lg" style="font-weight: 800;">
                        Cetak Label Tempel (Sticker 32x18mm)
                    </x-filament::button>

                    <x-filament::button wire:click="printPricecard" color="warning" icon="heroicon-m-tag" size="lg" style="font-weight: 800;">
                        Cetak Pricecard Rak (A4 60x30mm)
                    </x-filament::button>
                </div>
                
                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; opacity: 0.8; background: rgba(128,128,128,0.08); padding: 8px 14px; border-radius: 12px; border: 1px solid rgba(128,128,128,0.15);">
                    <x-filament::icon icon="heroicon-m-information-circle" style="width: 18px; height: 18px;" class="text-emerald-500" />
                    <span>Pastikan izin Popup pada browser Anda diaktifkan untuk membuka tab cetak secara otomatis.</span>
                </div>
            </div>
        </x-filament::section>

        <!-- Section 4: Hardware Driver & Black Mark Sensor Guidance -->
        <x-filament::section
            icon="heroicon-o-wrench-screwdriver"
            header-heading="Panduan Setup Driver Printer Barcode (Toshiba TEC B-SA4TP / Zebra / TSC)"
            header-subheading="Petunjuk konfigurasi driver printer thermal dengan sensor Reflective (Garis Hitam Belakang Kertas)."
            collapsible
            collapsed
        >
            <div style="font-size: 0.85rem; line-height: 1.6; display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <span style="background: rgba(16,185,129,0.15); color: #10b981; font-weight: 800; padding: 2px 8px; border-radius: 6px;">1</span>
                    <div>
                        <strong>Pengaturan Tipe Media (Sensor Reflective / Black Mark)</strong>: Pada driver printer (Toshiba TEC / Windows Printer Properties), buka tab <em>Page Setup / Stock</em>, ubah Tipe Sensor (Media Type) dari <em>Continuous / Gap</em> menjadi <strong>Reflective / Black Mark</strong>.
                    </div>
                </div>
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <span style="background: rgba(16,185,129,0.15); color: #10b981; font-weight: 800; padding: 2px 8px; border-radius: 6px;">2</span>
                    <div>
                        <strong>Ukuran Kertas Label Roll (3 Column)</strong>: Buat Stock Size / User-Defined Paper dengan lebar <strong>96mm</strong> dan tinggi <strong>18mm</strong> (3 Kolom @ 32x18mm).
                    </div>
                </div>
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <span style="background: rgba(16,185,129,0.15); color: #10b981; font-weight: 800; padding: 2px 8px; border-radius: 6px;">3</span>
                    <div>
                        <strong>Pengaturan Browser Print Dialog</strong>: Pada jendela print browser (Ctrl + P), pastikan Margin diset ke <strong>None / 0mm</strong> dan Skala diset ke <strong>100% (Default)</strong> tanpa Header & Footer.
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>

    @script
    <script>
        $wire.on('open-url-new-tab', (event) => {
            let url = event[0] ? event[0].url : event.url;
            if(url) {
                window.open(url, '_blank');
            }
        });
    </script>
    @endscript
</x-filament-panels::page>
