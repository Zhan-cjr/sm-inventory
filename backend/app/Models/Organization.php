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

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function posSettings()
    {
        return $this->hasMany(PosSetting::class);
    }
}
