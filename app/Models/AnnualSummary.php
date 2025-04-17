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
        'company_name',
        'total_emission',
        'total_sequestration',
        'carbon_variance',
        'ghg_contribution_percent',
    ];

    public function company()
    {
        return $this->belongsTo(MiningCompany::class);
    }


    protected $casts = [
        'annual_carbon_emission' => 'float',
        'annual_carbon_sequestration' => 'float',
        'carbon_neutrality_variance' => 'float',
        'percentage_ghg_contribution' => 'float',
    ];
}
