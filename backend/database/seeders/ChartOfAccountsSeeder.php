<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\Organization;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::first();

        if (!$organization) {
            $this->command->warn('Tidak ada Organisasi ditemukan. Melewati proses seeder COA.');
            return;
        }

        $defaultAccounts = [
            // ASET
            ['account_code' => '1110', 'name' => 'Kas di Tangan (Cash)', 'type' => 'asset', 'description' => 'Uang tunai di laci kasir'],
            ['account_code' => '1120', 'name' => 'Kas Bank (Bank)', 'type' => 'asset', 'description' => 'Saldo uang di rekening bank'],
            ['account_code' => '1130', 'name' => 'Piutang Usaha (Receivable)', 'type' => 'asset', 'description' => 'Tagihan pelanggan yang belum dibayar'],
            ['account_code' => '1140', 'name' => 'Persediaan Barang Dagang (Inventory)', 'type' => 'asset', 'description' => 'Nilai stok barang fisik'],
            ['account_code' => '1210', 'name' => 'Aset Tetap (Fixed Assets)', 'type' => 'asset', 'description' => 'Komputer, Rak, Peralatan Toko'],
            
            // KEWAJIBAN
            ['account_code' => '2110', 'name' => 'Hutang Usaha (Payable)', 'type' => 'liability', 'description' => 'Hutang ke supplier/pemasok'],
            ['account_code' => '2120', 'name' => 'Hutang Pajak (Tax Payable)', 'type' => 'liability', 'description' => 'Pajak keluaran/PPN'],

            // EKUITAS
            ['account_code' => '3110', 'name' => 'Modal Pemilik (Owner Equity)', 'type' => 'equity', 'description' => 'Modal awal disetor'],
            ['account_code' => '3120', 'name' => 'Laba Ditahan (Retained Earnings)', 'type' => 'equity', 'description' => 'Akumulasi laba yang tidak dibagi'],

            // PENDAPATAN
            ['account_code' => '4110', 'name' => 'Pendapatan Penjualan (Sales)', 'type' => 'revenue', 'description' => 'Pendapatan utama dari penjualan POS/Ecommerce'],
            ['account_code' => '4120', 'name' => 'Pendapatan Layanan (Service)', 'type' => 'revenue', 'description' => 'Pendapatan dari layanan/PPOB'],
            ['account_code' => '4130', 'name' => 'Diskon Penjualan (Sales Discount)', 'type' => 'revenue', 'description' => 'Potongan harga (pengurang pendapatan)'],

            // BEBAN
            ['account_code' => '5110', 'name' => 'Harga Pokok Penjualan (HPP / COGS)', 'type' => 'expense', 'description' => 'Modal dasar dari barang yang terjual'],
            ['account_code' => '5210', 'name' => 'Beban Gaji (Salary Expense)', 'type' => 'expense', 'description' => 'Gaji karyawan'],
            ['account_code' => '5220', 'name' => 'Beban Utilitas (Utility Expense)', 'type' => 'expense', 'description' => 'Listrik, Air, Internet'],
            ['account_code' => '5290', 'name' => 'Beban Lain-lain (Other Expense)', 'type' => 'expense', 'description' => 'Pengeluaran operasional lainnya'],
        ];

        foreach ($defaultAccounts as $acc) {
            Account::firstOrCreate([
                'organization_id' => $organization->id,
                'account_code' => $acc['account_code'],
            ], [
                'name' => $acc['name'],
                'type' => $acc['type'],
                'description' => $acc['description'],
                'is_active' => true,
            ]);
        }

        $this->command->info('Default Chart of Accounts (COA) berhasil ditambahkan!');
    }
}
