<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'fuel_consumption',
        'electricity_usage',
        'companyID'
    ];

    public function miningCompany()
    {
        return $this->belongsTo(MiningCompany::class, 'companyID');
    }

      // Calculate total emissions (fuel + electricity)
    public function calculateEmissions()
    {
        // Default emission factors
        $gasolineEF = 2.297040; // kg CO₂ per liter
        $dieselEF = 2.712681;   // kg CO₂ per liter
        $electricityEF = 0.496; // kg CO₂ per kWh

        // Retrieve stored values
        $fuelConsumption = $this->fuel_consumption;
        $electricityUsage = $this->electricity_usage;

        // Determine fuel emission factor
        $fuelEF = ($this->fuel_type === 'gasoline') ? $gasolineEF : $dieselEF;

        // Calculate emissions
        $fuelEmission = $fuelConsumption * $fuelEF;
        $electricityEmission = $electricityUsage * $electricityEF;
        $totalEmission = $fuelEmission + $electricityEmission;

        return $totalEmission;
    }

    public function company()
    {
        return $this->belongsTo(MiningCompany::class);
    }
}
