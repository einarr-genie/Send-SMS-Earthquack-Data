<?php

namespace App\Console\Commands;

use App\Services\EarthquakeScraperService;
use Illuminate\Console\Command;

class FetchEarthquakeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'earthquakes:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch earthquake data from the Thai Meteorological Department website';

    /**
     * Execute the console command.
     */
    public function handle(EarthquakeScraperService $scraperService): int
    {
        $this->info('Fetching earthquake data...');
        
        $count = $scraperService->fetchAndSaveData();
        
        $this->info("Earthquake data updated: {$count} new earthquakes added");
        
        return Command::SUCCESS;
    }
}