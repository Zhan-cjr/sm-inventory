<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MemberTier;
use App\Models\Organization;

class MemberTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orgId = Organization::first()?->id;

        $tiersData = [
            [
                'name' => 'Bronze Family',
                'badge' => 'MEMBER BARU',
                'min_points' => 0,
                'min_spend_text' => 'Gratis Pendaftaran',
                'discount_percent' => 1.00,
                'color_hex' => '#b45309',
                'color_gradient' => 'from-amber-700 via-amber-800 to-amber-950',
                'perks' => [
                    'Poin belanja 1% setiap transaksi',
                    'Voucher selamat datang Rp 15.000',
                    'Diskon hari ulang tahun'
                ]
            ],
            [
                'name' => 'Silver Privilege',
                'badge' => 'PALING POPULER',
                'min_points' => 1500,
                'min_spend_text' => 'Transaksi > Rp 1.500.000 / bln',
                'discount_percent' => 2.50,
                'color_hex' => '#475569',
                'color_gradient' => 'from-slate-400 via-slate-600 to-slate-900',
                'perks' => [
                    'Poin belanja 2.5%',
                    'Akses ke Promo Jajanan Subuh & Syariah',
                    'Gratis ongkir belanja online 2x/bulan'
                ]
            ],
            [
                'name' => 'Gold Executive',
                'badge' => 'VIP SYARIAH',
                'min_points' => 5000,
                'min_spend_text' => 'Transaksi > Rp 5.000.000 / bln',
                'discount_percent' => 5.00,
                'color_hex' => '#d97706',
                'color_gradient' => 'from-amber-400 via-yellow-500 to-amber-700',
                'perks' => [
                    'Poin belanja 5%',
                    'Layanan jalur antrean prioritas',
                    'Diskon 15% Hotel & Lounge Selamat',
                    'Undangan Event Syariah Eksklusif'
                ]
            ],
            [
                'name' => 'Platinum Sultan',
                'badge' => 'ULTIMATE',
                'min_points' => 15000,
                'min_spend_text' => 'Undangan Khusus',
                'discount_percent' => 8.00,
                'color_hex' => '#4c1d95',
                'color_gradient' => 'from-indigo-900 via-purple-900 to-slate-950',
                'perks' => [
                    'Poin belanja 8%',
                    'Personal Personal Shopping Assistant',
                    'Voucher Belanja Sultan Rp 100.000/Bulan',
                    'Cashback Poin Berkah Unlimited'
                ]
            ]
        ];

        foreach ($tiersData as $tData) {
            if ($orgId) {
                $tData['organization_id'] = $orgId;
            }
            MemberTier::updateOrCreate(['name' => $tData['name']], $tData);
        }
    }
}
