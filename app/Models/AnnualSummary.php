<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\MiningCompany;

class AnnualSummary extends Model
{
    use HasFactory;

    protected $table = 'annual_summary'; 

    protected $fillable = [
        'year',
        'company_id',
        'fuel_tco2',
        'electricity_tco2',
        'travel_tco2',
        'total_tco2' ,
        'carbon_sequestered_tco2',
        'carbon_neutrality_variance' ,
        'ghg_country_percent',
    ];

    public function company()
    {
        return $this->belongsTo(MiningCompany::class, 'company_id');
    }




    protected $casts = [
        'annual_carbon_emission' => 'float',
        'annual_carbon_sequestration' => 'float',
        'carbon_neutrality_variance' => 'float',
        'percentage_ghg_contribution' => 'float',
    ];
}
