<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EarthquakeMonitorService
{
    protected string $url = 'https://earthquake.tmd.go.th/';
    protected EarthquakeScraperService $scraperService;
    protected string $cacheKey = 'earthquake_html_content';

    public function __construct(EarthquakeScraperService $scraperService)
    {
        $this->scraperService = $scraperService;
    }

    /**
     * Check for updates in the earthquake data source
     *
     * @return bool Whether new data was found and processed
     */
    public function checkForUpdates(): bool
    {
        try {
            // Fetch the current HTML content
            $response = Http::get($this->url);
            
            if (!$response->successful()) {
                return false;
            }
            
            $currentHtml = $response->body();
            
            // Get the previously stored HTML content
            $previousHtml = Cache::get($this->cacheKey);
            
            // If there's no previous content or the content has changed
            if ($previousHtml === null || $this->hasContentChanged($previousHtml, $currentHtml)) {
                // Store the new HTML content
                Cache::put($this->cacheKey, $currentHtml, now()->addHours(24));
                
                // Process the new data
                $count = $this->scraperService->fetchAndSaveData();
                
                return $count > 0;
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Compare HTML content to detect meaningful changes
     *
     * @param string $previousHtml
     * @param string $currentHtml
     * @return bool
     */
    protected function hasContentChanged(string $previousHtml, string $currentHtml): bool
    {
        // Simple string comparison
        if ($previousHtml === $currentHtml) {
            return false;
        }
        
        // More sophisticated comparison could be implemented here
        // For example, extracting and comparing only the table content
        
        return true;
    }
}