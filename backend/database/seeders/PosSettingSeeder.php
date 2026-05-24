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
            ['key_name' => 'btn_subtotal', 'display_name' => 'Subtotal', 'shortcut_key' => 'F9'],
            ['key_name' => 'btn_disc_item_rp', 'display_name' => 'Diskon Item Rp', 'shortcut_key' => 'F1'],
            ['key_name' => 'btn_disc_item_pct', 'display_name' => 'Diskon Item %', 'shortcut_key' => 'F2'],
            ['key_name' => 'btn_disc_total_rp', 'display_name' => 'Diskon Total Rp', 'shortcut_key' => 'F3'],
            ['key_name' => 'btn_disc_total_pct', 'display_name' => 'Diskon Total %', 'shortcut_key' => 'F4'],
            ['key_name' => 'btn_tunai', 'display_name' => 'Tunai', 'shortcut_key' => 'F5'],
            ['key_name' => 'btn_card', 'display_name' => 'Credit Card', 'shortcut_key' => 'F6'],
            ['key_name' => 'btn_qty', 'display_name' => 'Ubah Qty', 'shortcut_key' => 'F7'],
            ['key_name' => 'btn_close_shift', 'display_name' => 'Tutup Shift', 'shortcut_key' => 'F8'],
            ['key_name' => 'btn_reprint_last', 'display_name' => 'Reprint Terakhir', 'shortcut_key' => 'F11'],
            ['key_name' => 'btn_reprint_old', 'display_name' => 'Reprint Lama', 'shortcut_key' => 'F12'],
            ['key_name' => 'btn_member', 'display_name' => 'Member', 'shortcut_key' => 'Home'],
            ['key_name' => 'btn_retur', 'display_name' => 'Retur', 'shortcut_key' => 'End'],
            ['key_name' => 'btn_hold', 'display_name' => 'Hold', 'shortcut_key' => 'PageUp'],
            ['key_name' => 'btn_recall', 'display_name' => 'Recall', 'shortcut_key' => 'PageDown'],
            ['key_name' => 'btn_clear', 'display_name' => 'Clear', 'shortcut_key' => 'Insert'],
            ['key_name' => 'btn_void_item', 'display_name' => 'Void Item', 'shortcut_key' => 'Delete'],
            ['key_name' => 'btn_void_all', 'display_name' => 'Void All', 'shortcut_key' => 'Escape'],
        ];

        \App\Models\PosSetting::where('key_name', 'btn_pay')->delete();

        $organizations = \App\Models\Organization::all();

        foreach ($organizations as $org) {
            foreach ($settings as $setting) {
                \App\Models\PosSetting::updateOrCreate(
                    ['key_name' => $setting['key_name'], 'organization_id' => $org->id],
                    $setting
                );
            }
        }
    }
}
