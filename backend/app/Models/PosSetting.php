<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosSetting extends Model
{
    protected $fillable = ['organization_id', 'key_name', 'display_name', 'shortcut_key', 'is_active'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
