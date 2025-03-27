<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;

    protected $table = 'source'; // Make sure it matches the actual database table

    protected $fillable = [
        'name',
        'electricity_usage',
        'fuel_type',           
        'fuel_consumption',     
        'companyID'     
    ];

    // Calculate total emissions (fuel + electricity)
    public function calculateEmissions()
    {
        // Default emission factors (kg CO₂ per unit)
        $gasolineEF = 2.297040; // kg CO₂ per liter
        $dieselEF = 2.712681;   // kg CO₂ per liter
        $electricityEF = 0.496; // kg CO₂ per kWh

        // Ensure values are set
        $fuelConsumption = $this->fuel_consumption ?? 0;
        $electricityUsage = $this->electricity_usage ?? 0;

        // Determine fuel emission factor
        $fuelEF = ($this->fuel_type === 'gasoline') ? $gasolineEF : $dieselEF;

        // Calculate emissions
        $fuelEmission = $fuelConsumption * $fuelEF;
        $electricityEmission = $electricityUsage * $electricityEF;
        $totalEmission = $fuelEmission + $electricityEmission;

        return $totalEmission;
    }

    protected $primaryKey = 'id';

    public function miningCompany()
    {
        return $this->belongsTo(MiningCompany::class, 'companyID');
    }
}
