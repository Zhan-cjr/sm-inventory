<x-filament-panels::page>
    @php
        $branches = \App\Models\Branch::all();
        $stats = $this->branch_stats;
    @endphp

    <style>
        .mig-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            font-family: inherit;
        }
        .mig-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border: 1px solid #334155;
            border-radius: 1rem;
            padding: 1.5rem 1.75rem;
            color: #ffffff;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.15);
        }
        .dark .mig-hero {
            background: linear-gradient(135deg, #090d16 0%, #111827 100%);
            border-color: #1f2937;
        }
        .mig-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }
        .mig-stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .dark .mig-stat-card {
            background: #1e293b;
            border-color: #334155;
            color: #f8fafc;
        }
        .mig-stat-val {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0284c7;
        }
        .dark .mig-stat-val {
            color: #38bdf8;
        }
        .mig-stat-label {
            font-size: 0.813rem;
            font-weight: 500;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .dark .mig-stat-label {
            color: #94a3b8;
        }
        .mig-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .dark .mig-panel {
            background: #1e293b;
            border-color: #334155;
            color: #f8fafc;
        }
        .mig-dropzone {
            border: 2px dashed #94a3b8;
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .mig-dropzone:hover {
            border-color: #0284c7;
            background: #f0f9ff;
        }
        .dark .mig-dropzone {
            background: #0f172a;
            border-color: #475569;
        }
        .dark .mig-dropzone:hover {
            border-color: #38bdf8;
            background: #1e293b;
        }
        .mig-badge-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
    </style>

    <div class="mig-container">
        <!-- Hero Banner -->
        <div class="mig-hero">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 0.25rem;">
                        Migrasi Histori Penjualan & Min-Max Dinamis
                    </h2>
                    <p style="font-size: 0.875rem; color: #94a3b8; max-width: 780px;">
                        Mengimpor rekapitulasi penjualan bulanan dari aplikasi lama (format XLS) dan menghitung batas stok 
                        <strong>Min Qty, Max Qty, serta Safety Stock</strong> secara otomatis berbasis statistik (Moving Average & Standar Deviasi Sheet 6).
                    </p>
                </div>
                <div style="background: rgba(14, 165, 233, 0.15); border: 1px solid rgba(56, 189, 248, 0.3); padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.8rem; color: #38bdf8;">
                    <div style="font-weight: 600;">Metode Hybrid Aktif</div>
                    <div style="color: #cbd5e1; font-size: 0.75rem;">Kasir Riil (Juli 2026+) &bull; Excel Lama (&lt; Juli 2026)</div>
                </div>
            </div>
        </div>

        <!-- Branch Selector & Metrics -->
        <div class="mig-panel">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <label for="branch_select" style="font-weight: 600; font-size: 0.9rem;">Cabang Target:</label>
                    <select id="branch_select" wire:model.live="selected_branch_id" style="padding: 0.45rem 1rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; font-size: 0.875rem; background: inherit; color: inherit; min-width: 260px;">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-size: 0.85rem; color: #64748b;">Evaluasi Periode:</label>
                    <select wire:model.live="eval_months" style="padding: 0.45rem 0.75rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; font-size: 0.85rem; background: inherit; color: inherit;">
                        <option value="6">6 Bulan Terakhir</option>
                        <option value="9">9 Bulan Terakhir</option>
                        <option value="12">12 Bulan Terakhir (Baku)</option>
                    </select>
                    <button wire:click="runRecalculate" 
                            type="button" 
                            style="padding: 0.45rem 1rem; background: #0284c7; color: #fff; border-radius: 0.5rem; font-weight: 600; font-size: 0.85rem; border: none; cursor: pointer;">
                        Hitung Ulang Min-Max
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="mig-stat-grid">
                <div class="mig-stat-card">
                    <span class="mig-stat-label">Histori Terimpor</span>
                    <span class="mig-stat-val">{{ number_format($stats['total_migration_records']) }} <span style="font-size: 0.9rem; font-weight: normal; color: #64748b;">baris</span></span>
                </div>
                <div class="mig-stat-card">
                    <span class="mig-stat-label">Produk Unik</span>
                    <span class="mig-stat-val">{{ number_format($stats['unique_products_migrated']) }} <span style="font-size: 0.9rem; font-weight: normal; color: #64748b;">SKU</span></span>
                </div>
                <div class="mig-stat-card">
                    <span class="mig-stat-label">Kasir SM Inventory Aktif</span>
                    <span class="mig-stat-val" style="font-size: 1.15rem; color: #10b981;">{{ $stats['earliest_live_pos'] }}</span>
                </div>
                <div class="mig-stat-card">
                    <span class="mig-stat-label">Stok Aktif Cabang</span>
                    <span class="mig-stat-val" style="color: #6366f1;">{{ number_format($stats['total_active_stocks']) }} <span style="font-size: 0.9rem; font-weight: normal; color: #64748b;">item</span></span>
                </div>
            </div>
        </div>

        <!-- Uploader Section -->
        <div class="mig-panel">
            <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.75rem;">
                Unggah File Laporan Penjualan Lama (.xls / .xlsx)
            </h3>
            <p style="font-size: 0.825rem; color: #64748b; margin-bottom: 1rem;">
                Mendukung <strong>1 File Multi-Sheet</strong> (beda bulan beda sheet) maupun <strong>Multi-File Batch</strong> (banyak file bulanan ditarik sekaligus). Sistem otomatis membaca Barcode, Nama, Qty, dan Periode tanggal.
            </p>

            <div x-data="{ isDragging: false, uploading: false, progress: 0, uploadError: null }"
                 x-on:dragover.prevent="isDragging = true"
                 x-on:dragleave.prevent="isDragging = false"
                 x-on:drop.prevent="isDragging = false; if ($event.dataTransfer.files.length) { $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true })); }"
                 x-on:livewire-upload-start="uploading = true; progress = 0; uploadError = null"
                 x-on:livewire-upload-finish="uploading = false; uploadError = null"
                 x-on:livewire-upload-error="uploading = false; uploadError = 'Gagal mengunggah file. Pastikan ukuran file di bawah 100MB dan format .xls / .xlsx.'"
                 x-on:livewire-upload-progress="progress = $event.detail.progress"
                 class="mig-dropzone"
                 :style="isDragging ? 'border-color: #0284c7; background: #f0f9ff;' : ''"
                 style="position: relative;">

                <label for="file_input" style="cursor: pointer; display: block; width: 100%;">
                    <svg style="width: 48px; height: 48px; margin: 0 auto 0.75rem; color: #0284c7;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <div style="font-weight: 600; font-size: 1rem; color: #0f172a; margin-bottom: 0.25rem;">
                        Klik untuk Pilih File atau Seret & Tarik file Excel ke sini
                    </div>
                    <div style="font-size: 0.825rem; color: #64748b;">
                        Mendukung file .xls / .xlsx (Format Rekapitulasi Penjualan Barang)
                    </div>
                </label>

                <input type="file" 
                       id="file_input" 
                       x-ref="fileInput"
                       wire:model="files" 
                       multiple 
                       accept=".xlsx,.xls,.csv" 
                       style="position: absolute; top: 0; left: 0; width: 1px; height: 1px; opacity: 0; z-index: -1;">

                <!-- Progress Bar saat Mengunggah -->
                <div x-show="uploading" style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px dashed #cbd5e1; text-align: left;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; color: #0284c7; margin-bottom: 0.35rem;">
                        <span>Mengunggah file ke server...</span>
                        <span x-text="progress + '%'"></span>
                    </div>
                    <div style="width: 100%; background: #e2e8f0; border-radius: 9999px; height: 10px; overflow: hidden;">
                        <div style="background: linear-gradient(90deg, #0284c7, #38bdf8); height: 100%; transition: width 0.2s;" :style="'width: ' + progress + '%'"></div>
                    </div>
                </div>

                <!-- Pesan Error jika Upload Gagal -->
                <div x-show="uploadError" x-text="uploadError" style="margin-top: 0.75rem; color: #dc2626; font-size: 0.85rem; font-weight: 600;"></div>
                @error('files.*')
                    <div style="margin-top: 0.5rem; color: #dc2626; font-size: 0.85rem; font-weight: 600;">{{ $message }}</div>
                @enderror
                @error('files')
                    <div style="margin-top: 0.5rem; color: #dc2626; font-size: 0.85rem; font-weight: 600;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Uploaded files indicator -->
            @if (!empty($files))
                <div style="margin-top: 1rem; padding: 1rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <div style="font-size: 0.9rem; font-weight: 700; color: #166534;">
                            &check; {{ count($files) }} file berhasil diunggah dan siap diproses:
                        </div>
                        <button wire:click="clearFiles" type="button" style="color: #dc2626; font-size: 0.8rem; font-weight: 600; background: none; border: none; cursor: pointer;">
                            Batal / Hapus File
                        </button>
                    </div>
                    <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; color: #15803d;">
                        @foreach ($files as $f)
                            <li>
                                <strong>{{ is_object($f) && method_exists($f, 'getClientOriginalName') ? $f->getClientOriginalName() : 'File Terpilih' }}</strong>
                                <span style="color: #64748b; font-size: 0.8rem;">
                                    ({{ is_object($f) && method_exists($f, 'getSize') ? round($f->getSize() / 1024, 1) . ' KB' : '' }})
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Action Controls -->
            <div style="margin-top: 1.25rem; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" id="auto_rec" wire:model="auto_recalculate" style="width: 1rem; height: 1rem; border-radius: 0.25rem;">
                    <label for="auto_rec" style="font-size: 0.875rem; cursor: pointer;">
                        Otomatis perbarui Min-Max dinamis produk setelah import selesai
                    </label>
                </div>

                <div style="display: flex; gap: 0.75rem;">
                    <button wire:click="runPreview" 
                            type="button" 
                            wire:loading.attr="disabled"
                            style="padding: 0.55rem 1.25rem; background: #e0f2fe; color: #0369a1; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; border: 1px solid #bae6fd; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <span wire:loading.remove wire:target="runPreview">Pratinjau / Cek File</span>
                        <span wire:loading.inline-flex wire:target="runPreview" style="display: none; align-items: center; gap: 0.4rem;">
                            <svg class="animate-spin" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Membaca File...
                        </span>
                    </button>

                    <button wire:click="runImport" 
                            type="button" 
                            wire:loading.attr="disabled"
                            style="padding: 0.55rem 1.5rem; background: #16a34a; color: #fff; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <span wire:loading.remove wire:target="runImport">Proses Import & Simpan</span>
                        <span wire:loading.inline-flex wire:target="runImport" style="display: none; align-items: center; gap: 0.4rem;">
                            <svg class="animate-spin" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Sedang Memproses...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Loading Progress Banner saat Proses Berjalan -->
            <div wire:loading wire:target="runImport" style="display: none; margin-top: 1.25rem; padding: 1.25rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                    <svg class="animate-spin" style="width: 22px; height: 22px; color: #0284c7;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <div>
                        <div style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">
                            Sedang Menyimpan Data Histori & Mengkalkulasi Min-Max...
                        </div>
                        <div style="font-size: 0.8rem; color: #3b82f6;">
                            Proses ini melakukan parsing ribuan data Excel, penyimpanan database analitik secara bulk, dan kalkulasi statistik 5.000+ produk. Harap jangan menutup halaman ini.
                        </div>
                    </div>
                </div>
                <!-- Animated pulse bar -->
                <div style="width: 100%; background: #dbeafe; border-radius: 9999px; height: 8px; overflow: hidden; margin-top: 0.75rem;">
                    <div style="background: linear-gradient(90deg, #0284c7, #38bdf8, #0284c7); height: 100%; width: 100%; animation: pulse 1.5s infinite;"></div>
                </div>
            </div>

            <!-- Loading Banner saat Pratinjau -->
            <div wire:loading wire:target="runPreview" style="display: none; margin-top: 1.25rem; padding: 1rem 1.25rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <svg class="animate-spin" style="width: 20px; height: 20px; color: #16a34a;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <div style="font-size: 0.875rem; color: #166534; font-weight: 600;">
                        Sedang membaca sheet dan mencocokkan barcode master produk...
                    </div>
                </div>
            </div>

            <!-- Loading Banner saat Hitung Ulang -->
            <div wire:loading wire:target="runRecalculate" style="display: none; margin-top: 1.25rem; padding: 1rem 1.25rem; background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <svg class="animate-spin" style="width: 20px; height: 20px; color: #9333ea;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <div style="font-size: 0.875rem; color: #6b21a8; font-weight: 600;">
                        Sedang mengkalkulasi ulang formula Moving Average & Min-Max untuk 5.000+ stok cabang...
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Results -->
        @if ($preview_data)
            <div class="mig-panel">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>Hasil Pratinjau Analisa File</span>
                    <span class="mig-badge-pill" style="background: #e0f2fe; color: #0284c7;">
                        {{ $preview_data['total_sheets'] }} Sheet Terdeteksi
                    </span>
                </h3>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                        <div style="font-size: 0.75rem; color: #64748b;">TOTAL BARIS DATA</div>
                        <div style="font-size: 1.25rem; font-weight: 700;">{{ number_format($preview_data['total_rows_parsed']) }}</div>
                    </div>
                    <div style="background: #f0fdf4; padding: 1rem; border-radius: 0.5rem; border: 1px solid #bbf7d0;">
                        <div style="font-size: 0.75rem; color: #166534;">PRODUK COCOK (BARCODE/SKU)</div>
                        <div style="font-size: 1.25rem; font-weight: 700; color: #15803d;">{{ number_format($preview_data['matched_records_count']) }}</div>
                    </div>
                    <div style="background: #fef2f2; padding: 1rem; border-radius: 0.5rem; border: 1px solid #fecaca;">
                        <div style="font-size: 0.75rem; color: #991b1b;">TIDAK COCOK (BELUM TERDAFTAR)</div>
                        <div style="font-size: 1.25rem; font-weight: 700; color: #b91c1c;">{{ number_format($preview_data['unmatched_records_count']) }}</div>
                    </div>
                    <div style="background: #f5f3ff; padding: 1rem; border-radius: 0.5rem; border: 1px solid #ddd6fe;">
                        <div style="font-size: 0.75rem; color: #5b21b6;">PERIODE BULAN TERDETEKSI</div>
                        <div style="font-size: 0.9rem; font-weight: 700; color: #6d28d9;">
                            {{ implode(', ', $preview_data['periods_detected']) ?: '-' }}
                        </div>
                    </div>
                </div>

                <!-- Breakdown per Sheet -->
                <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 0.5rem;">Rincian Tiap Sheet / File:</h4>
                <div style="overflow-x: auto; margin-bottom: 1.5rem;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="background: #f1f5f9; text-align: left; border-bottom: 2px solid #cbd5e1;">
                                <th style="padding: 0.6rem 0.75rem;">Nama File</th>
                                <th style="padding: 0.6rem 0.75rem;">Sheet</th>
                                <th style="padding: 0.6rem 0.75rem;">Periode</th>
                                <th style="padding: 0.6rem 0.75rem; text-align: right;">Total Baris</th>
                                <th style="padding: 0.6rem 0.75rem; text-align: right;">Cocok</th>
                                <th style="padding: 0.6rem 0.75rem; text-align: right;">Tidak Cocok</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($preview_data['sheets_detail'] as $row)
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 0.5rem 0.75rem;">{{ $row['file'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; font-weight: 600;">{{ $row['sheet_name'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem;"><span class="mig-badge-pill" style="background: #e0e7ff; color: #3730a3;">{{ $row['period'] }}</span></td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: right;">{{ number_format($row['total_rows']) }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: right; color: #16a34a; font-weight: 600;">{{ number_format($row['matched']) }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: right; color: {{ $row['unmatched'] > 0 ? '#dc2626' : '#64748b' }};">{{ number_format($row['unmatched']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Unmatched sample alert -->
                @if (!empty($preview_data['unmatched_samples']))
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 0.5rem; padding: 1rem;">
                        <div style="font-weight: 700; color: #92400e; font-size: 0.875rem; margin-bottom: 0.5rem;">
                            Contoh Barcode/Produk yang Tidak Ditemukan di Master Barang SM Inventory:
                        </div>
                        <div style="font-size: 0.8rem; color: #b45309; margin-bottom: 0.75rem;">
                            Data baris ini akan dilewati karena barcode atau kode barang tidak cocok dengan master produk.
                        </div>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
                                <thead>
                                    <tr style="text-align: left; color: #78350f;">
                                        <th style="padding: 0.35rem 0.5rem;">Baris</th>
                                        <th style="padding: 0.35rem 0.5rem;">Kd Brg</th>
                                        <th style="padding: 0.35rem 0.5rem;">Barcode</th>
                                        <th style="padding: 0.35rem 0.5rem;">Nama Barang</th>
                                        <th style="padding: 0.35rem 0.5rem; text-align: right;">Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($preview_data['unmatched_samples'] as $item)
                                        <tr style="border-top: 1px solid #fef3c7;">
                                            <td style="padding: 0.35rem 0.5rem;">{{ $item['row'] }}</td>
                                            <td style="padding: 0.35rem 0.5rem; font-family: monospace;">{{ $item['sku'] }}</td>
                                            <td style="padding: 0.35rem 0.5rem; font-family: monospace;">{{ $item['barcode'] }}</td>
                                            <td style="padding: 0.35rem 0.5rem;">{{ $item['name'] }}</td>
                                            <td style="padding: 0.35rem 0.5rem; text-align: right; font-weight: 600;">{{ $item['qty'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Recalculation Results -->
        @if ($recalculate_result)
            <div class="mig-panel">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.75rem; color: #0284c7;">
                    Ringkasan Hasil Perhitungan Min-Max Dinamis
                </h3>
                <p style="font-size: 0.825rem; color: #64748b; margin-bottom: 1rem;">
                    Batas stok Min & Max diperbarui otomatis menggunakan Moving Average 12 Bulan dan Safety Stock Statistik.
                </p>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="background: #f1f5f9; text-align: left; border-bottom: 2px solid #cbd5e1;">
                                <th style="padding: 0.6rem 0.75rem;">Produk</th>
                                <th style="padding: 0.6rem 0.75rem; text-align: right;">Rata-rata/Bln</th>
                                <th style="padding: 0.6rem 0.75rem; text-align: right;">ADS</th>
                                <th style="padding: 0.6rem 0.75rem; text-align: right;">Gejolak (SD)</th>
                                <th style="padding: 0.6rem 0.75rem; text-align: right;">Safety Stock</th>
                                <th style="padding: 0.6rem 0.75rem; text-align: center;">Min Qty (Lama &rarr; Baru)</th>
                                <th style="padding: 0.6rem 0.75rem; text-align: center;">Max Qty (Lama &rarr; Baru)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recalculate_result['sample_results'] as $res)
                                @php
                                    $p = \App\Models\Product::find($res['product_id']);
                                @endphp
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 0.5rem 0.75rem; font-weight: 600;">
                                        {{ $p?->name ?? $res['product_id'] }}
                                        <div style="font-size: 0.75rem; font-weight: normal; color: #64748b;">{{ $p?->barcode }}</div>
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: right;">{{ $res['avg_monthly_sales'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: right;">{{ $res['ads'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: right;">{{ $res['std_dev'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: right; font-weight: 700; color: #0284c7;">{{ $res['safety_stock'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: center;">
                                        <span style="color: #94a3b8; text-decoration: line-through;">{{ $res['old_min'] }}</span>
                                        &rarr;
                                        <strong style="color: #16a34a;">{{ $res['new_min'] }}</strong>
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: center;">
                                        <span style="color: #94a3b8; text-decoration: line-through;">{{ $res['old_max'] }}</span>
                                        &rarr;
                                        <strong style="color: #0284c7;">{{ $res['new_max'] }}</strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
