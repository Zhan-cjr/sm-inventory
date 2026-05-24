<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Organization extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'code', 'timezone', 'currency_code', 'logo_path', 'address', 'phone', 'email', 
        'point_conversion_rate', 'ecommerce_banner_title', 'ecommerce_banner_subtitle', 
        'ecommerce_banner_image', 'ecommerce_banner_cta_text', 'ecommerce_announcement', 'ecommerce_categories',
        'wa_gateway_type', 'wa_gateway_token', 'wa_gateway_domain', 'wa_gateway_sender',
        'allow_minus_stock'
    ];

    protected $casts = [
        'ecommerce_categories' => 'array',
        'allow_minus_stock' => 'boolean',
    ];

    protected static function booted()
    {
        static::created(function ($organization) {
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

            foreach ($settings as $setting) {
                $organization->posSettings()->create($setting);
            }
        });
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function posSettings()
    {
        return $this->hasMany(PosSetting::class);
    }
}
