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
        return $this->hasMany(SourceEmission::class, 'sourceID');
    }

    public function calculateQuarterlyEmissions($year, $quarter)
    {
        $emissions = $this->emissions()
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->first();
    
        return $this->calculateEmissionsFromData($emissions);
    }
    
    public function calculateYearlyEmissions($year)
    {
        $emissions = $this->emissions()->where('year', $year)->get();
    
        $totalCO2 = 0;
        $totalN2O = 0;
        $totalElectricityEmission = 0;
    
        foreach ($emissions as $data) {
            $result = $this->calculateEmissionsFromData($data);
            $totalCO2 += $result['fuel_emissions']['co2_emission'];
            $totalN2O += $result['fuel_emissions']['n2o_emission'];
            $totalElectricityEmission += $result['electricity_emission'];
        }
    
        return [
            'year' => $year,
            'co2_emission' => round($totalCO2, 2),
            'n2o_emission' => round($totalN2O, 2),
            'total_fuel_emission' => round($totalCO2 + $totalN2O, 2),
            'electricity_emission' => round($totalElectricityEmission, 2),
            'total_emission' => round($totalCO2 + $totalN2O + $totalElectricityEmission, 2)
        ];
    }
    
    private function calculateEmissionsFromData($data)
    {
        if (!$data) {
            return [
                'fuel_emissions' => [
                    'co2_emission' => 0,
                    'n2o_emission' => 0,
                    'total_fuel_emission' => 0,
                ],
                'electricity_emission' => 0,
                'total_emission' => 0
            ];
        }
    
        $fuelType = strtolower($this->fuel_type);
        $fuelConsumption = $data->fuel_consumption;
        $electricityUsageKWh = $data->electricity_usage;
        $electricityUsageMWh = $electricityUsageKWh / 1000;
    
        // Emission factors
        $emissionFactors = [
            'diesel' => ['co2' => 2.712681, 'n2o' => 0.00012],
            'gasoline' => ['co2' => 2.297040, 'n2o' => 0.00016],
        ];
        $GWP = ['co2' => 1, 'n2o' => 298];
    
        if (!isset($emissionFactors[$fuelType])) {
            return [
                'fuel_emissions' => ['error' => 'Invalid fuel type'],
                'electricity_emission' => 0,
                'total_emission' => 0
            ];
        }
    
        // Calculate emissions
        $co2Emission = ($fuelConsumption * $emissionFactors[$fuelType]['co2'] * $GWP['co2']) / 1000;
        $n2oEmission = ($fuelConsumption * $emissionFactors[$fuelType]['n2o'] * $GWP['n2o']) / 1000;
        $totalFuelEmission = $co2Emission + $n2oEmission;
    
        $electricityFactor = 0.496;
        $electricityEmission = $electricityUsageKWh * $electricityFactor;
    
        return [
            'fuel_emissions' => [
                'co2_emission' => round($co2Emission, 2),
                'n2o_emission' => round($n2oEmission, 2),
                'total_fuel_emission' => round($totalFuelEmission, 2),
            ],
            'electricity_emission' => round($electricityEmission, 2),
            'total_emission' => round($totalFuelEmission + $electricityEmission, 2),
        ];
    }
    
    public function Source()
    {
        return $this->hasMany(SourceEmission::class, 'sourceID');
    }
    
}
