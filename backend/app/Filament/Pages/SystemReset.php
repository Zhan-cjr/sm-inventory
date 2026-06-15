<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class SystemReset extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.system-reset';

    public static function getNavigationGroup(): ?string
    {
        return 'UTILITY';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-exclamation-triangle';
    }

    public static function getNavigationSort(): ?int
    {
        return 99;
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return 'Reset Database';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetDatabase')
                ->label('RESET ALL DATA')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Reset Keseluruhan Database')
                ->modalDescription('PERINGATAN KERAS! Aksi ini akan menghapus semua data transaksi, stok, produk, kategori, pelanggan, cabang, dan terminal secara permanen. Ketik "RESET SEKARANG" untuk melanjutkan.')
                ->form([
                    TextInput::make('confirmation')
                        ->label('Ketik RESET SEKARANG untuk mengonfirmasi')
                        ->required()
                        ->in(['RESET SEKARANG'])
                        ->validationMessages([
                            'in' => 'Konfirmasi tidak cocok. Harus mengetik RESET SEKARANG dengan huruf besar.',
                        ]),
                ])
                ->action(function () {
                    $this->executeReset();
                    
                    Notification::make()
                        ->title('Database Berhasil Direset')
                        ->body('Semua data operasional telah dikosongkan.')
                        ->success()
                        ->send();
                }),
        ];
    }

    private function executeReset()
    {
        Schema::disableForeignKeyConstraints();

        $tablesToTruncate = [
            // Transaksi Kasir
            'transactions', 'transaction_items', 'cash_movements', 'shifts', 'ppob_transactions', 'authorization_requests',
            // Produk & Kategori
            'products', 'product_assemblies', 'product_conversions', 'categories', 'sub_categories',
            // Stok
            'stocks', 'stock_batches', 'stock_batch_deductions', 'stock_adjustments', 'stock_adjustment_items',
            'inventory_logs', 'stock_transfers', 'stock_transfer_items', 'stock_opname_sessions', 'stock_opname_racks',
            'stock_opname_rack_sessions', 'stock_opname_items',
            // Pembelian & Supplier
            'suppliers', 'purchase_orders', 'purchase_order_items', 'goods_receipts', 'goods_receipt_items',
            'purchase_returns', 'purchase_return_items', 'purchase_payments', 'purchase_payment_items',
            'kontrabons', 'kontrabon_items', 'kontrabon_deductions', 'supplier_deductions',
            // Jurnal Keuangan (hanya isi jurnal, COA tetap)
            'journal_entries', 'journal_entry_lines', 'expenses',
            // Cabang, Terminal, Pelanggan
            'branches', 'terminals', 'pos_devices', 'pos_settings', 'customers', 'point_histories',
            'reward_redemptions', 'ecommerce_orders', 'ecommerce_order_items',
            // Lain-lain
            'fixed_assets', 'fixed_asset_depreciations', 'promotions', 'vouchers', 'rewards'
        ];

        foreach ($tablesToTruncate as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}
