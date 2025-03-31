<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'fuel_type',
        'companyID'
    ];

    public function miningCompany()
    {
        return $this->belongsTo(MiningCompany::class, 'companyID');
    }

    public function emissions()
    {
        return $this->hasMany(SourceEmission::class, 'source_id');
    }

    // Calculate total emissions (CO2 & N2O) for a given year
    public function calculateYearlyEmissions($year)
    {
        $emissions = $this->emissions()->where('year', $year)->get();

        $fuelEmission = $emissions->sum(function ($emission) {
            return $emission->fuel_consumption * $this->getEmissionFactor() * 1 / 1000; // GWP already included
        });

        $electricityEmission = $emissions->sum(function ($emission) {
            return ($emission->electricity_usage / 1000) * 0.496; // Convert kWh to MWh
        });

        return [
            'fuel_emission' => $fuelEmission,
            'electricity_emission' => $electricityEmission,
            'total_emission' => $fuelEmission + $electricityEmission,
        ];
    }

    private function getEmissionFactor()
    {
        return ($this->fuel_type === 'gasoline') ? 2.297040 : 2.712681;
    }
}
