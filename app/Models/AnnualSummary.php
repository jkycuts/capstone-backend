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
        'total_emission',
        'carbon_sequestered_tco2',
        'carbon_neutrality_variance' ,
        'ghg_country_percent',
    ];

    protected $casts = [
        'total_emission' => 'float',
        'carbon_sequestered_tco2' => 'float',
        'carbon_neutrality_variance' => 'float',
        'ghg_country_percent' => 'float',
        'year' => 'integer',
    ];

   public function company()
{
    return $this->belongsTo(MiningCompany::class, 'company_id');
}


}
