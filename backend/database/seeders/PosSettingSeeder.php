<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PosSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key_name' => 'btn_pay', 'display_name' => 'Bayar', 'shortcut_key' => 'F10'],
            ['key_name' => 'btn_subtotal', 'display_name' => 'Subtotal', 'shortcut_key' => 'F9'],
            ['key_name' => 'btn_disc_item_rp', 'display_name' => 'Diskon Item Rp', 'shortcut_key' => 'F1'],
            ['key_name' => 'btn_disc_item_pct', 'display_name' => 'Diskon Item %', 'shortcut_key' => 'F2'],
            ['key_name' => 'btn_disc_total_rp', 'display_name' => 'Diskon Total Rp', 'shortcut_key' => 'F3'],
            ['key_name' => 'btn_disc_total_pct', 'display_name' => 'Diskon Total %', 'shortcut_key' => 'F4'],
            ['key_name' => 'btn_tunai', 'display_name' => 'Tunai', 'shortcut_key' => 'F5'],
            ['key_name' => 'btn_card', 'display_name' => 'Credit Card', 'shortcut_key' => 'F6'],
            ['key_name' => 'btn_void_item', 'display_name' => 'Void Item', 'shortcut_key' => 'Delete'],
            ['key_name' => 'btn_void_all', 'display_name' => 'Void All', 'shortcut_key' => 'Escape'],
        ];

        foreach ($settings as $setting) {
            \App\Models\PosSetting::updateOrCreate(
                ['key_name' => $setting['key_name']],
                $setting
            );
        }
    }
}
