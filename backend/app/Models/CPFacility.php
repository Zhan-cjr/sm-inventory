<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CPFacility extends Model
{
    use HasFactory;

    protected $table = 'cp_facilities';

    protected $fillable = [
        'identifier',
        'name',
        'description',
        'icon',
        'image_url',
    ];

    public function branches()
    {
        return $this->belongsToMany(CPBranch::class, 'cp_branch_facility', 'c_p_facility_id', 'c_p_branch_id');
    }
}
