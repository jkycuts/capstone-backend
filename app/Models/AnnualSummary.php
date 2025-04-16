<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualSummary extends Model
{
    use HasFactory;

    protected $table = 'annual_summary'; 

    protected $fillable = [
        'year',
        'company_name',
        'annual_carbon_emission',
        'annual_carbon_sequestration',
        'carbon_neutrality_variance',
        'ghg_percentage_national',
        'company_id',
    ];
}
