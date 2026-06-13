<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Branch extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id', 'name', 'code', 'address', 
        'phone', 'manager_id', 'is_active', 'latitude', 'longitude',
        'receipt_type', 'receipt_footer_layout', 'receipt_show_logo', 'receipt_show_tax', 'receipt_tax_message',
        'receipt_tax_rate', 'receipt_tax_rate_message', 'receipt_dpp_rate', 'receipt_dpp_message', 'receipt_total_tax_message',
        'receipt_header_line1', 'receipt_header_line1_bold', 'receipt_header_line2', 'receipt_header_line2_bold',
        'receipt_header_line3', 'receipt_header_line3_bold', 'receipt_header_line4', 'receipt_header_line4_bold',
        'receipt_footer_line1', 'receipt_footer_line1_bold', 'receipt_footer_line2', 'receipt_footer_line2_bold',
        'receipt_footer_line3', 'receipt_footer_line3_bold', 'receipt_footer_line4', 'receipt_footer_line4_bold',
        'receipt_footer_line5', 'receipt_footer_line5_bold', 'receipt_footer_line6', 'receipt_footer_line6_bold'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function outgoingTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'from_branch_id');
    }

    public function incomingTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'to_branch_id');
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'branch_promotion');
    }
}
