<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CPSetting extends Model
{
    use HasFactory;

    protected $table = 'cp_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'label',
    ];
}
