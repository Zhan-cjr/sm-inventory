<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CPBranch extends Model
{
    use HasFactory;

    protected $table = 'cp_branches';

    protected $fillable = [
        'name',
        'address',
        'open_hours',
        'lat',
        'lng',
    ];

    public function facilities()
    {
        return $this->belongsToMany(CPFacility::class, 'cp_branch_facility', 'c_p_branch_id', 'c_p_facility_id');
    }
}
