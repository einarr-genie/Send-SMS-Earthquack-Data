<?php

namespace App\Console\Commands;

use App\Models\Earthquake;
use App\Services\EarthquakeScraperService;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'earthquakes:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     */
    public function handle(EarthquakeScraperService $scraperService): int
    {
        $this->info('Fetching earthquake data...');

        $earthquakes = Earthquake::all();

        if (count($earthquakes) === 0) {
            $this->info('No new earthquakes found.');
            return Command::SUCCESS;
        }
        foreach ($earthquakes as $earthquake) {
            $this->info("Magnitude: {$earthquake->magnitude}, Location: {$earthquake->location}");

            $count = $scraperService->getRegionFromCoordinates($earthquake->latitude, $earthquake->longitude);            
        }
        
        return Command::SUCCESS;
    }
}