<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Str;

class PosDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::create([
            'id' => Str::uuid(),
            'name' => 'Hypermarket Corp',
            'code' => 'HYP',
        ]);

        // The branch ID requested by frontend is JKT-01, but branch_id is a UUID.
        // We will create a specific UUID for this branch and update the frontend if needed, 
        // or simply allow frontend to send JKT-01 if we change the branch ID to string.
        // Wait, the migration uses uuid(). So let's create a known UUID.
        $branchId = '00000000-0000-0000-0000-000000000002';
        
        $branch = Branch::create([
            'id' => $branchId,
            'organization_id' => $org->id,
            'name' => 'Cabang Jakarta 01',
            'code' => 'JKT-01',
            'address' => 'Jl. Sudirman',
        ]);

        \App\Models\User::create([
            'name' => 'Cashier JKT-01',
            'email' => 'cashier@selamat.id',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'organization_id' => $org->id,
            'branch_id' => $branch->id,
            'role' => 'CASHIER',
        ]);

        \App\Models\User::create([
            'name' => 'Manager JKT-01',
            'email' => 'manager@selamat.id',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'organization_id' => $org->id,
            'branch_id' => $branch->id,
            'role' => 'MANAGER',
        ]);

        $products = [
            ['sku' => '11111', 'name' => 'Indomie Goreng', 'price' => 3500],
            ['sku' => '22222', 'name' => 'Beras Pandan Wangi 5kg', 'price' => 75000],
            ['sku' => '33333', 'name' => 'Minyak Goreng Bimoli 2L', 'price' => 35000],
            ['sku' => '44444', 'name' => 'Telur Ayam 1kg', 'price' => 28000],
            ['sku' => '55555', 'name' => 'Susu Bear Brand', 'price' => 10500],
        ];

        foreach ($products as $i => $p) {
            // Generate a known UUID so frontend can scan it
            // Actually frontend generates random products. To test sync,
            // frontend must send REAL product UUIDs.
            // We will modify frontend to use these SKUs.
            $prod = Product::create([
                'id' => Str::uuid(),
                'organization_id' => $org->id,
                'sku' => $p['sku'],
                'barcode' => $p['sku'],
                'name' => $p['name'],
                'cost_price' => $p['price'] * 0.8,
                'selling_price' => $p['price'],
                'metadata' => ['brand' => 'Unknown']
            ]);

            Stock::create([
                'id' => Str::uuid(),
                'branch_id' => $branch->id,
                'product_id' => $prod->id,
                'quantity_on_hand' => 1000,
                'min_qty' => 10,
                'max_qty' => 5000,
            ]);
        }
    }
}
