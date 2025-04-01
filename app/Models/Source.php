<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;

    protected $table = 'sources'; // Ensure this matches your actual DB table

    protected $fillable = [
        'name',
        'fuel_type',
        'company_id'
    ];

    public function miningCompany()
    {
        return $this->belongsTo(MiningCompany::class, 'companyID');
    }

    public function emissions()
    {
        return $this->hasMany(SourceEmission::class, 'source_id');
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

        // Loop through all emissions for the given year and sum up the values
        foreach ($emissions as $data) {
            $result = $this->calculateEmissionsFromData($data);
            $totalCO2 += $result['fuel_emissions']['co2_emission'];
            $totalN2O += $result['fuel_emissions']['n2o_emission'];
            $totalElectricityEmission += $result['electricity_emission'];
        }

        return [
            'year' => $year,
            'fuel_emissions' => [
                'co2_emission' => round($totalCO2, 2),
                'n2o_emission' => round($totalN2O, 2),
                'total_fuel_emission' => round($totalCO2 + $totalN2O, 2),
            ],
            'electricity_emission' => round($totalElectricityEmission, 2),
        ];
    }

    public function calculateEmissionsFromData($data)
{
        if (!$data) {
            return [
                'fuel_emissions' => [
                    'co2_emission' => 0,
                    'n2o_emission' => 0,
                    'total_fuel_emission' => 0,
                ],
                'electricity_emission' => 0,
            ];
    }

    $fuelType = strtolower($this->fuel_type);
    $fuelConsumption = $data->fuel_consumption;
    $electricityUsageKWh = $data->electricity_usage;
    
    // Convert electricity usage from kWh to MWh
    $electricityUsageMWh = $electricityUsageKWh / 1000;

    // Emission factors for fuel types
    $emissionFactors = [
        'diesel'    => ['co2' => 2.712681, 'n2o' => 0.000143, 'ch4' => 0.000143],
        'gasoline'  => ['co2' => 2.297040, 'n2o' => 0.000210, 'ch4' => 0.000671],
        'biodiesel' => ['co2' => 0.000000, 'n2o' => 0.000870, 'ch4' => 0.000382],
        'ethanol'   => ['co2' => 0.000000, 'n2o' => 0.000870, 'ch4' => 0.000382],
    ];
    $GWP = ['co2' => 1, 'n2o' => 298, 'ch4' => 21];

    if (!isset($emissionFactors[$fuelType])) {
        return [
            'fuel_emissions' => ['error' => 'Invalid fuel type'],
            'electricity_emission' => 0,
        ];
    }

    // Calculate fuel emissions (CO2 and N2O)
    $co2Emission = ($fuelConsumption * $emissionFactors[$fuelType]['co2'] * $GWP['co2']) / 1000;
    $n2oEmission = ($fuelConsumption * $emissionFactors[$fuelType]['n2o'] * $GWP['n2o']) / 1000;
    $ch4Emission = ($fuelConsumption * $emissionFactors[$fuelType]['ch4'] * $GWP['ch4']) / 1000;
    $totalFuelEmission = $co2Emission + $n2oEmission;

    // Convert MWh to emissions for electricity usage
    $electricityFactor = 0.496;  
    $electricityEmission = $electricityUsageMWh * $electricityFactor;

    return [
        'fuel_emissions' => [
            'co2_emission' => round($co2Emission, 2),
            'n2o_emission' => round($n2oEmission, 2),
            'total_emissions_tco2e' => round($totalFuelEmission, 2),
        ],
        'electricity_emission' => round($electricityEmission, 2),
    ];
}

}
