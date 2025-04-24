<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TreeGrowth;
use App\Models\Plantation;
use Illuminate\Http\Request;
use App\Services\TreeGrowthSimulation;

class TreeGrowthController extends Controller
{
    public function simulateTreeGrowth($treeId)
    {
        $growths = TreeGrowth::where('tree_id', $treeId)
            ->orderBy('year')
            ->get();

        if ($growths->count() < 2) {
            return response()->json(['message' => 'Not enough data to simulate growth.'], 422);
        }

        $results = [];

        for ($i = 1; $i < $growths->count(); $i++) {
            $prev = $growths[$i - 1];
            $curr = $growths[$i];

            $results[] = [
                'year' => $curr->year,
                'starting_dbh' => $prev->dbh,
                'ending_dbh' => $curr->dbh,
                'dbh_growth' => round($curr->dbh - $prev->dbh, 2),

                'starting_height' => $prev->height,
                'ending_height' => $curr->height,
                'height_growth' => round($curr->height - $prev->height, 2),
            ];
        }

        return response()->json($results);
    }
}
