<?php

namespace App\Console\Commands;

use App\Services\Transportation\LocationService;
use Illuminate\Console\Command;

class PruneTransportationLocations extends Command
{
    protected $signature = 'transportation:prune-locations {--days=30 : Days of location history to keep}';

    protected $description = 'Prune old bus GPS location updates (privacy + storage)';

    public function handle()
    {
        $deleted = app(LocationService::class)->prune(
            keepPerTrip: 2000,
            keepDays: (int) $this->option('days')
        );

        $this->info("Pruned {$deleted} location updates.");

        return Command::SUCCESS;
    }
}
