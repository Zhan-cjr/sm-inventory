<div class="w-full">
    @if(!$hasData)
        <div class="p-4 text-center text-sm text-gray-500">
            Simpan produk terlebih dahulu untuk memuat Analisis Kinerja Produk.
        </div>
    @else
        <div style="margin-top: 0.5rem; margin-bottom: 0.5rem; display: flex; flex-direction: column; gap: 1rem;">

            {{-- INTERACTIVE BRANCH SWITCHER BAR --}}
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.65rem 0.85rem; border-radius: 0.75rem; background: linear-gradient(135deg, rgba(241, 245, 249, 0.9) 0%, rgba(248, 250, 252, 0.9) 100%); border: 1px solid rgba(203, 213, 225, 0.8);" class="dark:border-gray-700 dark:bg-gray-800">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="display: flex; height: 1.75rem; width: 1.75rem; align-items: center; justify-content: center; border-radius: 0.5rem; background: #0284c7; color: #fff;">
                        <x-filament::icon icon="heroicon-o-building-storefront" class="h-4 w-4" />
                    </span>
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; color: #1e293b;" class="dark:text-white">
                            Konteks Analisis Cabang: <span style="color: #0284c7;">{{ $branchLabel }}</span>
                        </div>
                        <div style="font-size: 0.7rem; color: #64748b;">
                            @if($isUserBranchLocked)
                                🔒 Terkunci pada cabang akun Anda
                            @else
                                Klik tombol cabang di bawah untuk mengganti fokus analisis toko secara instan
                            @endif
                        </div>
                    </div>
                </div>

                @if(!$isUserBranchLocked && count($allBranchesList) > 1)
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem;">
                        <button type="button" 
                                wire:click="selectBranch('all')" 
                                style="padding: 0.25rem 0.6rem; font-size: 0.7rem; font-weight: 700; border-radius: 0.375rem; cursor: pointer; transition: all 0.2s; {{ empty($activeSelectedBranchId) ? 'background: #0284c7; color: #fff; border: 1px solid #0284c7;' : 'background: #fff; color: #475569; border: 1px solid #cbd5e1;' }}">
                            🏢 Semua Cabang
                        </button>
                        @foreach($allBranchesList as $b)
                            <button type="button" 
                                    wire:click="selectBranch('{{ $b->id }}')" 
                                    style="padding: 0.25rem 0.6rem; font-size: 0.7rem; font-weight: 700; border-radius: 0.375rem; cursor: pointer; transition: all 0.2s; {{ $activeSelectedBranchId === $b->id ? 'background: #0284c7; color: #fff; border: 1px solid #0284c7;' : 'background: #fff; color: #475569; border: 1px solid #cbd5e1;' }}">
                                🏪 {{ $b->name }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- 3-Column Grid Intelligence Panels --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(310px, 1fr)); gap: 1rem;">

                {{-- PANEL 1: TREN PENJUALAN 6 BULAN --}}
                <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(156, 163, 175, 0.2); padding-bottom: 0.5rem; margin-bottom: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="display: flex; height: 1.75rem; width: 1.75rem; align-items: center; justify-content: center; border-radius: 0.5rem; background-color: rgba(14, 165, 233, 0.1); color: #0284c7;">
                                <x-filament::icon icon="heroicon-o-chart-bar" class="h-4 w-4" />
                            </span>
                            <div>
                                <h4 style="font-size: 0.875rem; font-weight: 700; margin: 0; color: inherit;">Tren Penjualan 6 Bulan</h4>
                                <p style="font-size: 0.75rem; margin: 0; color: #64748b;">Jumlah barang keluar per bulan ({{ $branchLabel }})</p>
                            </div>
                        </div>
                        <span style="font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 9999px; background: rgba(16, 185, 129, 0.1); color: #059669;">
                            Rp {{ number_format($sellingPrice, 0, ',', '.') }} / {{ $unit }}
                        </span>
                    </div>

                    {{-- Visual Bar Chart (6 Bulan) --}}
                    <div style="margin-top: 0.5rem;">
                        <div style="display: flex; align-items: flex-end; justify-content: space-between; height: 85px; gap: 0.5rem; padding: 0.5rem 0.25rem 0; border-bottom: 1px solid rgba(156, 163, 175, 0.2);">
                            @foreach($monthlyTrends as $item)
                                @php
                                    $heightPct = $maxMonthQty > 0 ? max(8, round(($item['qty'] / $maxMonthQty) * 70)) : 8;
                                @endphp
                                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; justify-content: flex-end;">
                                    <span style="font-size: 0.65rem; font-weight: 700; color: {{ $item['qty'] > 0 ? '#0284c7' : '#94a3b8' }}; margin-bottom: 2px;">
                                        {{ $item['qty'] }}
                                    </span>
                                    <div style="width: 100%; max-width: 24px; height: {{ $heightPct }}px; border-radius: 4px 4px 0 0; background: {{ $item['qty'] > 0 ? 'linear-gradient(180deg, #0284c7 0%, #38bdf8 100%)' : '#e2e8f0' }}; transition: height 0.3s ease;"></div>
                                </div>
                            @endforeach
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 0.25rem; font-size: 0.65rem; color: #64748b;">
                            @foreach($monthlyTrends as $item)
                                <span style="flex: 1; text-align: center;">{{ explode(' ', $item['month'])[0] }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div style="margin-top: 0.75rem; border-radius: 0.5rem; background: rgba(241, 245, 249, 0.6); padding: 0.5rem; font-size: 0.75rem; color: #475569; display: flex; align-items: center; gap: 0.35rem;">
                        <span>⚡</span>
                        @if($sales30Days > 0)
                            <span><strong>Laju Penjualan:</strong> Rata-rata keluar <strong>{{ $dailyAvg }} {{ $unit }}/hari</strong>. Perputaran modal sangat sehat.</span>
                        @else
                            <span><strong>Laju Penjualan:</strong> Belum ada transaksi penjualan produk ini di <strong>{{ $branchLabel }}</strong> dalam 30 hari terakhir.</span>
                        @endif
                    </div>
                </div>

                {{-- PANEL 2: MARKET BASKET ANALYSIS (CROSS-SELLING) --}}
                <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(156, 163, 175, 0.2); padding-bottom: 0.5rem; margin-bottom: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="display: flex; height: 1.75rem; width: 1.75rem; align-items: center; justify-content: center; border-radius: 0.5rem; background-color: rgba(168, 85, 247, 0.1); color: #9333ea;">
                                <x-filament::icon icon="heroicon-o-shopping-bag" class="h-4 w-4" />
                            </span>
                            <div>
                                <h4 style="font-size: 0.875rem; font-weight: 700; margin: 0; color: inherit;">Sering Dibeli Bersamaan</h4>
                                <p style="font-size: 0.75rem; margin: 0; color: #64748b;">Barang lain yang sering dibeli bareng di kasir</p>
                            </div>
                        </div>
                        <span style="font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 9999px; background: rgba(168, 85, 247, 0.1); color: #9333ea;">
                            Ide Promo Kasir
                        </span>
                    </div>

                    {{-- Daftar Top 3 Pasangan Keranjang --}}
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        @forelse($topAffinityItems as $idx => $aff)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.4rem 0.6rem; border-radius: 0.5rem; background: rgba(248, 250, 252, 0.8); border: 1px solid rgba(226, 232, 240, 0.8); font-size: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; overflow: hidden;">
                                    <span style="display: flex; height: 1.25rem; width: 1.25rem; align-items: center; justify-content: center; border-radius: 9999px; background: #9333ea; color: #fff; font-size: 0.65rem; font-weight: 700; flex-shrink: 0;">
                                        {{ $idx + 1 }}
                                    </span>
                                    <span style="font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;" title="{{ $aff['name'] }}">
                                        {{ $aff['name'] }}
                                    </span>
                                </div>
                                <span style="font-size: 0.7rem; font-weight: 700; color: #7e22ce; background: rgba(243, 232, 255, 0.8); padding: 0.15rem 0.4rem; border-radius: 4px; flex-shrink: 0;">
                                    {{ $aff['confidence'] }}
                                </span>
                            </div>
                        @empty
                            <div style="padding: 1.5rem 0.5rem; text-align: center; color: #94a3b8; font-size: 0.75rem;">
                                <span>Belum cukup riwayat struk bersama untuk produk ini.</span>
                            </div>
                        @endforelse
                    </div>

                    <div style="margin-top: 0.75rem; border-radius: 0.5rem; background: rgba(243, 232, 255, 0.5); padding: 0.5rem; font-size: 0.75rem; color: #6b21a8; display: flex; align-items: center; gap: 0.35rem;">
                        <span>🛒</span>
                        <span><strong>Saran Rak Toko:</strong> Letakkan produk ini di dekat barang di atas agar belanjaan pembeli bertambah.</span>
                    </div>
                </div>

                {{-- PANEL 3: SARAN TINDAKAN OTOMATIS & MUTASI CABANG --}}
                <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(156, 163, 175, 0.2); padding-bottom: 0.5rem; margin-bottom: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="display: flex; height: 1.75rem; width: 1.75rem; align-items: center; justify-content: center; border-radius: 0.5rem; background-color: rgba(245, 158, 11, 0.1); color: #d97706;">
                                <x-filament::icon icon="heroicon-o-light-bulb" class="h-4 w-4" />
                            </span>
                            <div>
                                <h4 style="font-size: 0.875rem; font-weight: 700; margin: 0; color: inherit;">Saran Tindakan Cerdas</h4>
                                <p style="font-size: 0.75rem; margin: 0; color: #64748b;">Rekomendasi stok, mutasi & pesanan suplier</p>
                            </div>
                        </div>
                        <span style="font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 9999px; background: rgba(245, 158, 11, 0.1); color: #d97706;">
                            Kesesuaian Suplier: {{ $otifScore }}%
                        </span>
                    </div>

                    {{-- Action Alerts --}}
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        @forelse($prescriptiveActions as $act)
                            @php
                                $type = $act['type'] ?? 'info';
                                if ($type === 'transfer') {
                                    $bgColor = 'rgba(245, 243, 255, 0.95)';
                                    $borderColor = '#ddd6fe';
                                    $textColor = '#6d28d9';
                                    $btnBg = '#7c3aed';
                                } elseif ($type === 'danger') {
                                    $bgColor = 'rgba(254, 242, 242, 0.95)';
                                    $borderColor = '#fecaca';
                                    $textColor = '#991b1b';
                                    $btnBg = '#dc2626';
                                } elseif ($type === 'warning') {
                                    $bgColor = 'rgba(254, 252, 232, 0.95)';
                                    $borderColor = '#fef08a';
                                    $textColor = '#854d0e';
                                    $btnBg = '#d97706';
                                } elseif ($type === 'neutral') {
                                    $bgColor = 'rgba(248, 250, 252, 0.95)';
                                    $borderColor = '#e2e8f0';
                                    $textColor = '#475569';
                                    $btnBg = '#475569';
                                } elseif ($type === 'success') {
                                    $bgColor = 'rgba(240, 253, 244, 0.95)';
                                    $borderColor = '#bbf7d0';
                                    $textColor = '#166534';
                                    $btnBg = '#16a34a';
                                } else {
                                    $bgColor = 'rgba(239, 246, 255, 0.95)';
                                    $borderColor = '#bfdbfe';
                                    $textColor = '#1e40af';
                                    $btnBg = '#2563eb';
                                }
                            @endphp
                            <div style="padding: 0.55rem 0.65rem; border-radius: 0.5rem; background: {{ $bgColor }}; border: 1px solid {{ $borderColor }}; font-size: 0.75rem; color: {{ $textColor }};">
                                <div style="font-weight: 700; margin-bottom: 0.2rem;">{{ $act['title'] }}</div>
                                <div style="font-size: 0.72rem; line-height: 1.4; opacity: 0.95;">{{ $act['message'] }}</div>
                                @if(!empty($act['action_label']) && !empty($act['action_url']))
                                    <div style="margin-top: 0.45rem;">
                                        <a href="{{ $act['action_url'] }}" 
                                           target="_blank" 
                                           style="display: inline-block; padding: 0.25rem 0.65rem; font-size: 0.7rem; font-weight: 700; border-radius: 4px; background: {{ $btnBg }}; color: #fff; text-decoration: none; transition: opacity 0.2s ease;">
                                            👉 {{ $act['action_label'] }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div style="padding: 1.5rem 0.5rem; text-align: center; color: #94a3b8; font-size: 0.75rem;">
                                <span>🟢 Kondisi stok dan perputaran barang ini berada dalam batas normal dan aman.</span>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

            {{-- MATRIKS KOMPARASI RINCIAN PER CABANG (JIKA LEBIH DARI 1 CABANG) --}}
            @if(count($branchBreakdown) > 1)
                <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900" style="margin-top: 0.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(156, 163, 175, 0.2); padding-bottom: 0.5rem; margin-bottom: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="display: flex; height: 1.75rem; width: 1.75rem; align-items: center; justify-content: center; border-radius: 0.5rem; background-color: rgba(99, 102, 241, 0.1); color: #4f46e5;">
                                <x-filament::icon icon="heroicon-o-arrows-right-left" class="h-4 w-4" />
                            </span>
                            <div>
                                <h4 style="font-size: 0.875rem; font-weight: 700; margin: 0; color: inherit;">Rincian Stok & Penjualan Per Toko</h4>
                                <p style="font-size: 0.75rem; margin: 0; color: #64748b;">Perbandingan persediaan fisik dan perputaran barang di tiap cabang</p>
                            </div>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.75rem; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(226, 232, 240, 0.8); background: rgba(248, 250, 252, 0.8);">
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 700;">Nama Cabang / Toko</th>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 700; text-align: right;">Sisa Stok Fisik</th>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 700; text-align: right;">Penjualan 30 Hari</th>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 700; text-align: right;">Laju Harian</th>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 700;">Status Operasional</th>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 700; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($branchBreakdown as $b)
                                    <tr style="border-bottom: 1px solid rgba(241, 245, 249, 0.8); {{ $b['isSelected'] ? 'background: rgba(239, 246, 255, 0.5);' : '' }}">
                                        <td style="padding: 0.55rem 0.75rem; font-weight: 600;">
                                            {{ $b['name'] }}
                                            @if($b['isSelected'])
                                                <span style="margin-left: 0.35rem; font-size: 0.65rem; background: #0284c7; color: #fff; padding: 0.1rem 0.35rem; border-radius: 4px;">Sedang Dilihat</span>
                                            @endif
                                        </td>
                                        <td style="padding: 0.55rem 0.75rem; text-align: right; font-weight: 700; color: {{ $b['qoh'] <= 0 ? '#dc2626' : '#16a34a' }};">
                                            {{ number_format($b['qoh'], 0, ',', '.') }} {{ $unit }}
                                        </td>
                                        <td style="padding: 0.55rem 0.75rem; text-align: right; font-weight: 600;">
                                            {{ number_format($b['sales30Days'], 0, ',', '.') }} {{ $unit }}
                                        </td>
                                        <td style="padding: 0.55rem 0.75rem; text-align: right; color: #64748b;">
                                            ~{{ $b['dailyAvg'] }} {{ $unit }}/hr
                                        </td>
                                        <td style="padding: 0.55rem 0.75rem;">
                                            @if($b['badgeColor'] === 'danger')
                                                <span style="background: #fee2e2; color: #991b1b; padding: 0.15rem 0.45rem; border-radius: 4px; font-weight: 600; font-size: 0.7rem;">🚨 {{ $b['status'] }}</span>
                                            @elseif($b['badgeColor'] === 'warning')
                                                <span style="background: #fef3c7; color: #92400e; padding: 0.15rem 0.45rem; border-radius: 4px; font-weight: 600; font-size: 0.7rem;">⚠️ {{ $b['status'] }}</span>
                                            @elseif($b['badgeColor'] === 'success')
                                                <span style="background: #dcfce7; color: #166534; padding: 0.15rem 0.45rem; border-radius: 4px; font-weight: 600; font-size: 0.7rem;">🟢 {{ $b['status'] }}</span>
                                            @else
                                                <span style="background: #f1f5f9; color: #475569; padding: 0.15rem 0.45rem; border-radius: 4px; font-weight: 600; font-size: 0.7rem;">⚪ {{ $b['status'] }}</span>
                                            @endif
                                        </td>
                                        <td style="padding: 0.55rem 0.75rem; text-align: center;">
                                            @if(!$isUserBranchLocked)
                                                <button type="button" 
                                                        wire:click="selectBranch('{{ $b['id'] }}')" 
                                                        style="padding: 0.2rem 0.5rem; font-size: 0.68rem; font-weight: 600; border-radius: 4px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; cursor: pointer;">
                                                    Fokus Cabang Ini
                                                </button>
                                            @else
                                                <span style="color: #94a3b8; font-size: 0.7rem;">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    @endif
</div>