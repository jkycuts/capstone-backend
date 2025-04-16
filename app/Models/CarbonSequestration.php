<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarbonSequestration extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'plantation_id',
        
        'dbh',
        'height',
        'agb',
        'bgb',
        'carbon_content',
        'co2_sequestered',
        'latitude',
        'longitude',
        'year_recorded',
        'remarks',
    ];

    public function company()
    {
        return $this->belongsTo(MiningCompany::class);
    }

    public function plantation()
    {
        return $this->belongsTo(Plantation::class);
    }
}
