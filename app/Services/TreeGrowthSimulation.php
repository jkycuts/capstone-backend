<?php

namespace App\Services;

class TreeGrowthSimulation
{
    protected $speciesGrowthRates = [
        'species_1' => ['height_growth_rate' => 0.5, 'dbh_growth_rate' => 1.0], // 0.5 meters per year, 1 cm per year
        'species_2' => ['height_growth_rate' => 0.3, 'dbh_growth_rate' => 0.8],
        // Add other species and their growth rates here...
    ];

    public function simulateAnnualGrowth($tree)
    {
        // Get the species growth rate
        $growthRate = $this->speciesGrowthRates[$tree->species] ?? null;

        if (!$growthRate) {
            return false; // No growth rate available for this species
        }

        // Simulate annual growth
        $tree->height += $growthRate['height_growth_rate'];  // Update tree height
        $tree->dbh += $growthRate['dbh_growth_rate'];  // Update tree DBH

        // Update tree age
        $tree->age += 1;

        // Save the updated tree data
        $tree->save();

        return true;
    }
}
