<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\MiningCompany;

class Scope1Emission extends Model
{
    use HasFactory;


    protected $table = 'scope1';

    protected $fillable = [
        'company_id',
        'quarter',
        'year',
        'parameter',
        'emission_tco2e',
    ];

    public function company()
    {
        return $this->belongsTo(MiningCompany::class);
    }

}



