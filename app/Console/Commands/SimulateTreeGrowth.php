<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TreeGrowth;
use App\Services\TreeGrowthSimulation;

class SimulateTreeGrowth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:simulate-tree-growth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $trees = TreeGrowth::all();
        $growthSimulation = new TreeGrowthSimulation();

        foreach ($trees as $tree) {
            if ($growthSimulation->simulateAnnualGrowth($tree)) {
                $this->info("Tree {$tree->id} growth simulated successfully.");
            } else {
                $this->error("Failed to simulate growth for tree {$tree->id}.");
            }
        }
    }
    

    
}
