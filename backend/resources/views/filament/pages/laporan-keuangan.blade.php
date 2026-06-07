<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit="updateFilter" class="space-y-6">
            {{ $this->form }}
            
            <div class="flex justify-end gap-3" style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
                <x-filament::button tag="a" href="{{ route('print.report', ['type' => 'laporan_keuangan', 'start_date' => $this->start_date, 'end_date' => $this->end_date, 'branch_id' => $this->branch_id]) }}" target="_blank" icon="heroicon-o-printer" color="success">
                    Cetak Laporan
                </x-filament::button>
                <x-filament::button type="submit" icon="heroicon-o-funnel">
                    Filter Laporan
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    @php
        $data = $this->getViewData();
        $accountBalances = $data['accountBalances'];
        $netProfit = $data['netProfit'];

        // Kelompokkan berdasarkan tipe akun
        $assets = array_filter($accountBalances, fn($item) => $item['account']->type === 'asset');
        $liabilities = array_filter($accountBalances, fn($item) => $item['account']->type === 'liability');
        $equities = array_filter($accountBalances, fn($item) => $item['account']->type === 'equity');
        $revenues = array_filter($accountBalances, fn($item) => $item['account']->type === 'revenue');
        $expenses = array_filter($accountBalances, fn($item) => $item['account']->type === 'expense');

        $totalAssets = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquities = array_sum(array_column($equities, 'balance'));
        $totalRevenues = array_sum(array_column($revenues, 'balance'));
        $totalExpenses = array_sum(array_column($expenses, 'balance'));
        
        $retainedEarnings = $data['retainedEarnings'] ?? 0;
        
        $totalLiabEq = $totalLiabilities + $totalEquities + $retainedEarnings + $netProfit;
        $isBalanced = round($totalAssets, 2) == round($totalLiabEq, 2);
    @endphp

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
        {{-- Laporan Laba Rugi (P&L) --}}
        <x-filament::section icon="heroicon-o-chart-bar" heading="Laba Rugi (Profit & Loss)">
            
            <div style="font-size: 0.875rem; line-height: 1.25rem;">
                <!-- Pendapatan -->
                <div>
                    <h3 style="font-weight: 600; padding-bottom: 0.25rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb; color: #4b5563;">PENDAPATAN (REVENUE)</h3>
                    @forelse($revenues as $item)
                        @if($item['balance'] != 0)
                        <div style="display: flex; justify-content: space-between; padding-top: 0.25rem; padding-bottom: 0.25rem;">
                            <span>{{ $item['account']->account_code }} - {{ $item['account']->name }}</span>
                            <span style="font-family: monospace;">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                        </div>
                        @endif
                    @empty
                        <div style="color: #6b7280; font-style: italic;">Tidak ada data pendapatan.</div>
                    @endforelse
                    <div style="display: flex; justify-content: space-between; padding-top: 0.5rem; padding-bottom: 0.5rem; font-weight: 700; border-top: 1px solid #e5e7eb; margin-top: 0.5rem;">
                        <span>Total Pendapatan</span>
                        <span style="font-family: monospace; color: #16a34a;">Rp {{ number_format($totalRevenues, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Beban & HPP -->
                <div style="margin-top: 1.5rem;">
                    <h3 style="font-weight: 600; padding-bottom: 0.25rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb; color: #4b5563;">BEBAN & HPP (EXPENSE)</h3>
                    @forelse($expenses as $item)
                        @if($item['balance'] != 0)
                        <div style="display: flex; justify-content: space-between; padding-top: 0.25rem; padding-bottom: 0.25rem;">
                            <span>{{ $item['account']->account_code }} - {{ $item['account']->name }}</span>
                            <span style="font-family: monospace;">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                        </div>
                        @endif
                    @empty
                        <div style="color: #6b7280; font-style: italic;">Tidak ada data beban.</div>
                    @endforelse
                    <div style="display: flex; justify-content: space-between; padding-top: 0.5rem; padding-bottom: 0.5rem; font-weight: 700; border-top: 1px solid #e5e7eb; margin-top: 0.5rem;">
                        <span>Total Beban & HPP</span>
                        <span style="font-family: monospace; color: #dc2626;">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Laba Bersih -->
                <div style="margin-top: 2rem; padding-top: 1rem; border-top: 2px solid #d1d5db;">
                    <div style="display: flex; justify-content: space-between; align-items: center; background-color: rgba(156, 163, 175, 0.1); padding: 0.75rem; border-radius: 0.5rem;">
                        <span style="font-size: 1.125rem; font-weight: 700;">Laba Bersih (Net Profit)</span>
                        <span style="font-size: 1.25rem; font-family: monospace; font-weight: 700; color: {{ $netProfit >= 0 ? '#16a34a' : '#dc2626' }};">
                            Rp {{ number_format($netProfit, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Laporan Neraca (Balance Sheet) --}}
        <x-filament::section icon="heroicon-o-scale" heading="Neraca Saldo (Balance Sheet)">
            <div style="font-size: 0.875rem; line-height: 1.25rem;">
                <!-- Aset -->
                <div>
                    <h3 style="font-weight: 600; padding-bottom: 0.25rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb; color: #4b5563;">ASET (ASSETS)</h3>
                    @forelse($assets as $item)
                        @if($item['balance'] != 0)
                        <div style="display: flex; justify-content: space-between; padding-top: 0.25rem; padding-bottom: 0.25rem;">
                            <span>{{ $item['account']->account_code }} - {{ $item['account']->name }}</span>
                            <span style="font-family: monospace;">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                        </div>
                        @endif
                    @empty
                        <div style="color: #6b7280; font-style: italic;">Tidak ada data aset.</div>
                    @endforelse
                    <div style="display: flex; justify-content: space-between; padding-top: 0.5rem; padding-bottom: 0.5rem; font-weight: 700; border-top: 1px solid #e5e7eb; margin-top: 0.5rem;">
                        <span>Total Aset</span>
                        <span style="font-family: monospace; color: #2563eb;">Rp {{ number_format($totalAssets, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Kewajiban -->
                <div style="margin-top: 1.5rem;">
                    <h3 style="font-weight: 600; padding-bottom: 0.25rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb; color: #4b5563;">KEWAJIBAN (LIABILITIES)</h3>
                    @forelse($liabilities as $item)
                        @if($item['balance'] != 0)
                        <div style="display: flex; justify-content: space-between; padding-top: 0.25rem; padding-bottom: 0.25rem;">
                            <span>{{ $item['account']->account_code }} - {{ $item['account']->name }}</span>
                            <span style="font-family: monospace;">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                        </div>
                        @endif
                    @empty
                        <div style="color: #6b7280; font-style: italic;">Tidak ada data kewajiban.</div>
                    @endforelse
                    <div style="display: flex; justify-content: space-between; padding-top: 0.5rem; padding-bottom: 0.5rem; font-weight: 700; border-top: 1px solid #e5e7eb; margin-top: 0.5rem;">
                        <span>Total Kewajiban</span>
                        <span style="font-family: monospace; color: #ea580c;">Rp {{ number_format($totalLiabilities, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Ekuitas -->
                <div style="margin-top: 1.5rem;">
                    <h3 style="font-weight: 600; padding-bottom: 0.25rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb; color: #4b5563;">EKUITAS (EQUITY)</h3>
                    @forelse($equities as $item)
                        @if($item['balance'] != 0)
                        <div style="display: flex; justify-content: space-between; padding-top: 0.25rem; padding-bottom: 0.25rem;">
                            <span>{{ $item['account']->account_code }} - {{ $item['account']->name }}</span>
                            <span style="font-family: monospace;">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                        </div>
                        @endif
                    @empty
                        <div style="color: #6b7280; font-style: italic;">Tidak ada data ekuitas.</div>
                    @endforelse
                    
                    {{-- Tambahkan Laba Ditahan dan Laba Tahun Berjalan di Ekuitas agar Balance --}}
                    @if($retainedEarnings != 0)
                    <div style="display: flex; justify-content: space-between; padding-top: 0.25rem; padding-bottom: 0.25rem; color: #4f46e5;">
                        <span>Laba Ditahan (Periode Sebelumnya)</span>
                        <span style="font-family: monospace;">Rp {{ number_format($retainedEarnings, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    
                    <div style="display: flex; justify-content: space-between; padding-top: 0.25rem; padding-bottom: 0.25rem; color: #4f46e5; font-weight: 500;">
                        <span>Laba Bersih Berjalan</span>
                        <span style="font-family: monospace;">Rp {{ number_format($netProfit, 0, ',', '.') }}</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; padding-top: 0.5rem; padding-bottom: 0.5rem; font-weight: 700; border-top: 1px solid #e5e7eb; margin-top: 0.5rem;">
                        <span>Total Kewajiban + Ekuitas</span>
                        <span style="font-family: monospace; color: {{ $isBalanced ? '#2563eb' : '#dc2626' }};">
                            Rp {{ number_format($totalLiabEq, 0, ',', '.') }}
                        </span>
                    </div>
                    @if(!$isBalanced)
                    <div style="font-size: 0.75rem; color: #ef4444; margin-top: 0.5rem; display: flex; align-items: center; gap: 0.25rem;">
                        <span style="width: 1rem; height: 1rem; display: inline-block;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </span>
                        Peringatan: Neraca Tidak Balance! Terdapat selisih pencatatan.
                    </div>
                    @endif
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
