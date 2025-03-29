<?php

namespace App\Console\Commands;

use App\Services\EarthquakeMonitorService;
use Illuminate\Console\Command;

class MonitorEarthquakes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'earthquakes:monitor {--daemon : Run continuously as a daemon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor earthquake data source for changes';

    /**
     * Execute the console command.
     */
    public function handle(EarthquakeMonitorService $monitorService): int
    {
        if ($this->option('daemon')) {
            $this->info('Starting earthquake monitoring daemon...');
            $this->info('Press Ctrl+C to stop');
            
            while (true) {
                $this->checkForUpdates($monitorService);
                sleep(1); // Check every second
            }
        } else {
            return $this->checkForUpdates($monitorService);
        }
    }
    
    /**
     * Check for updates and report results
     */
    protected function checkForUpdates(EarthquakeMonitorService $monitorService): int
    {
        $this->info('Checking for earthquake updates...');
        
        $hasUpdates = $monitorService->checkForUpdates();
        
        if ($hasUpdates) {
            $this->info('New earthquake data detected and processed!');
            return Command::SUCCESS;
        } else {
            $this->info('No new earthquake data detected.');
            return Command::SUCCESS;
        }
    }
}