<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CPSetting;
use App\Models\CPFacility;
use App\Models\CPBranch;

class CompanyProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Settings
        $settings = [
            ['key' => 'company_name', 'value' => 'Toserba Selamat Group', 'type' => 'string', 'label' => 'Nama Perusahaan'],
            ['key' => 'tagline', 'value' => 'Lengkap, Nyaman, dan Penuh Berkah.', 'type' => 'string', 'label' => 'Tagline Utama'],
            ['key' => 'description', 'value' => 'Toserba Selamat menghadirkan pengalaman belanja dan fasilitas premium untuk seluruh keluarga dengan nilai-nilai syariah yang menenangkan.', 'type' => 'text', 'label' => 'Deskripsi Singkat'],
            ['key' => 'hero_image_url', 'value' => 'https://images.unsplash.com/photo-1604719312566-8912e9227c6a?q=80&w=2574&auto=format&fit=crop', 'type' => 'image', 'label' => 'Hero Image URL'],
            ['key' => 'google_maps_api_key', 'value' => '', 'type' => 'string', 'label' => 'Google Maps API Key'],
        ];

        foreach ($settings as $setting) {
            CPSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        // 2. Facilities
        $facilitiesData = [
            ['identifier' => 'supermarket', 'name' => 'Supermarket', 'description' => 'Pusat belanja kebutuhan harian lengkap dan segar.', 'icon' => 'ShoppingCart'],
            ['identifier' => 'fashion', 'name' => 'Fashion', 'description' => 'Pakaian tren terbaru untuk seluruh anggota keluarga.', 'icon' => 'Shirt'],
            ['identifier' => 'moslem-house', 'name' => 'Moslem House', 'description' => 'Pusat busana muslimah dan perlengkapan ibadah.', 'icon' => 'Heart'],
            ['identifier' => 'hotel-syariah', 'name' => 'Hotel Syariah', 'description' => 'Penginapan nyaman, bersih, dan berprinsip syariah.', 'icon' => 'Hotel'],
            ['identifier' => 'jajanan-subuh', 'name' => 'Jajanan Subuh', 'description' => 'Pusat kuliner tradisional dan aneka kue subuh.', 'icon' => 'Coffee'],
            ['identifier' => 'fitness-center', 'name' => 'SHSC Fitness Center', 'description' => 'Pusat kebugaran dengan alat modern dan profesional.', 'icon' => 'Dumbbell'],
            ['identifier' => 'padel', 'name' => 'Padel Court', 'description' => 'Fasilitas lapangan olahraga Padel terkini.', 'icon' => 'Activity'],
            ['identifier' => 'kids-arena', 'name' => 'Arena Bermain Anak', 'description' => 'Area bermain yang aman, nyaman, dan edukatif.', 'icon' => 'Gamepad2'],
            ['identifier' => 'tenant-kuliner', 'name' => 'Tenant Kuliner & Ritel', 'description' => 'Menampilkan brand populer seperti Tomoro, Miniso, dll.', 'icon' => 'Store'],
            ['identifier' => 'salon-muslimah', 'name' => 'Salon Muslimah', 'description' => 'Perawatan kecantikan dengan privasi khusus wanita.', 'icon' => 'Scissors'],
            ['identifier' => 'autocare', 'name' => 'Autocare', 'description' => 'Jasa cuci dan service kendaraan selagi Anda berbelanja.', 'icon' => 'Car'],
        ];

        $facilityMap = [];
        foreach ($facilitiesData as $fData) {
            $fac = CPFacility::updateOrCreate(['identifier' => $fData['identifier']], $fData);
            $facilityMap[$fData['identifier']] = $fac->id;
        }

        // 3. Branches
        $branchesData = [
            [
                'name' => 'Toserba Selamat Pusat',
                'address' => 'Jl. A. Yani No. 1, Pusat Kota',
                'lat' => -6.914744,
                'lng' => 107.609810,
                'open_hours' => '08:00 - 22:00',
                'facilities' => ['supermarket', 'fashion', 'moslem-house', 'hotel-syariah', 'fitness-center', 'padel', 'tenant-kuliner']
            ],
            [
                'name' => 'Toserba Selamat Cabang Timur',
                'address' => 'Jl. Timur Raya No. 45',
                'lat' => -6.920000,
                'lng' => 107.620000,
                'open_hours' => '07:00 - 21:00',
                'facilities' => ['supermarket', 'fashion', 'jajanan-subuh', 'kids-arena', 'autocare']
            ],
            [
                'name' => 'Toserba Selamat Cabang Barat',
                'address' => 'Jl. Barat Jaya No. 12',
                'lat' => -6.910000,
                'lng' => 107.600000,
                'open_hours' => '08:00 - 21:30',
                'facilities' => ['supermarket', 'moslem-house', 'salon-muslimah', 'tenant-kuliner']
            ]
        ];

        foreach ($branchesData as $bData) {
            $facilitiesIds = array_map(function($identifier) use ($facilityMap) {
                return $facilityMap[$identifier];
            }, $bData['facilities']);
            
            unset($bData['facilities']); // Remove from array so we can create branch
            
            $branch = CPBranch::updateOrCreate(['name' => $bData['name']], $bData);
            $branch->facilities()->sync($facilitiesIds);
        }

        // 4. Member Tiers
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

        $orgId = \App\Models\Organization::first()?->id;

        foreach ($tiersData as $tData) {
            if ($orgId) {
                $tData['organization_id'] = $orgId;
            }
            \App\Models\MemberTier::updateOrCreate(['name' => $tData['name']], $tData);
        }
    }
}
