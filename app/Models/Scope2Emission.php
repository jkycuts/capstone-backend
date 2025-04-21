<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\MiningCompany;

class Scope2Emission extends Model
{
    use HasFactory;

    protected $table = 'scope2_emission';

    protected $fillable = [
        'company_id',
        'year',
        'quarter',   
        'electricity_kwh',    
        'emission_tco2e',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(MiningCompany::class);
    }
}
