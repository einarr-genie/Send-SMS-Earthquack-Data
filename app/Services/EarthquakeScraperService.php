<?php

namespace App\Services;

use App\Models\Earthquake;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class EarthquakeScraperService
{
    protected string $url = 'https://earthquake.tmd.go.th/';
    protected TwilioService $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * Fetch and parse earthquake data from the TMD website.
     *
     * @return array The scraped earthquake data
     */
    public function scrapeData(): array
    {
        try {
            $response = Http::get($this->url);
            
            if (!$response->successful()) {
                return [];
            }

            $html = $response->body();
            $crawler = new Crawler($html);
            
            // Remove the debug statements
            // dd($crawler);
            
            $earthquakes = [];
            
            // Find the table with earthquake data - using the correct table ID
            $crawler->filter('#table_inside_home tr.tbis_leq1, #table_inside_home tr.tbis_leq2')->each(function (Crawler $row) use (&$earthquakes) {
                // Skip rows that don't have the expected structure
                if ($row->filter('td')->count() < 6) {
                    return;
                }
                
                try {
                    // Extract date and time
                    $dateTimeNode = $row->filter('td[valign="top"][align="center"]:first-child');
                    if ($dateTimeNode->count() === 0) return;
                    
                    // Get the UTC time from the font tag
                    $utcTimeNode = $dateTimeNode->filter('font');
                    $utcTime = '';
                    if ($utcTimeNode->count() > 0) {
                        $utcTime = trim($utcTimeNode->text());
                        // Extract the date-time part from the UTC string
                        if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $utcTime, $matches)) {
                            $utcTime = $matches[1];
                        }
                    }
                    
                    // Use UTC time if available, otherwise use the main date-time
                    $dateTimeText = $utcTime ?: trim(preg_replace('/\s+/', ' ', $dateTimeNode->text()));
                    $dateTime = $this->parseDateTime($dateTimeText);
                    
                    // Extract magnitude
                    $magnitudeNode = $row->filter('td[valign="top"][align="center"]:nth-child(2)');
                    if ($magnitudeNode->count() === 0) return;
                    
                    $magnitudeText = trim($magnitudeNode->text());
                    // Remove any non-numeric characters except decimal point
                    $magnitudeText = preg_replace('/[^0-9\.]/', '', $magnitudeText);
                    $magnitude = (float) $magnitudeText;
                    
                    // Extract latitude
                    $latitudeNode = $row->filter('td[valign="top"][align="left"]:nth-child(3)');
                    if ($latitudeNode->count() === 0) return;
                    
                    $latitudeText = trim($latitudeNode->text());
                    // Extract the numeric part and handle the degree symbol
                    if (preg_match('/(\d+\.\d+)°([NS])/', $latitudeText, $matches)) {
                        $latitude = (float) $matches[1];
                        if ($matches[2] === 'S') {
                            $latitude *= -1;
                        }
                    } else {
                        return; // Skip if we can't parse the latitude
                    }
                    
                    // Extract longitude
                    $longitudeNode = $row->filter('td[valign="top"][align="left"]:nth-child(4)');
                    if ($longitudeNode->count() === 0) return;
                    
                    $longitudeText = trim($longitudeNode->text());
                    // Extract the numeric part and handle the degree symbol
                    if (preg_match('/(\d+\.\d+)°([EW])/', $longitudeText, $matches)) {
                        $longitude = (float) $matches[1];
                        if ($matches[2] === 'W') {
                            $longitude *= -1;
                        }
                    } else {
                        return; // Skip if we can't parse the longitude
                    }
                    
                    // Extract depth
                    $depthNode = $row->filter('td[valign="top"][align="center"]:nth-child(5)');
                    if ($depthNode->count() === 0) return;
                    
                    $depthText = trim($depthNode->text());
                    $depth = (float) $depthText;
                    
                    // Extract region
                    $regionNode = $row->filter('td[valign="top"]:nth-child(6)');
                    if ($regionNode->count() === 0) return;
                    
                    $region = '';
                    $regionTh = '';
                    
                    // Try to extract Thai and English region names
                    $fontNodes = $regionNode->filter('font');
                    if ($fontNodes->count() >= 1) {
                        $regionTh = trim($fontNodes->eq(0)->text());
                    }
                    if ($fontNodes->count() >= 2) {
                        $region = trim($fontNodes->eq(1)->text());
                    }
                    
                    // If we couldn't extract separate regions, use the full text
                    if (empty($region) && empty($regionTh)) {
                        $region = trim($regionNode->text());
                    }
                    
                    // Get the earthquake ID from the onclick attribute if available
                    $earthquakeId = null;
                    $onclick = $row->attr('onclick');
                    if ($onclick && preg_match('/earthquake=(\d+)/', $onclick, $matches)) {
                        $earthquakeId = $matches[1];
                    }
                    
                    // Generate a unique external ID based on time and coordinates or use the earthquake ID
                    $externalId = $earthquakeId ?: md5($dateTime->format('Y-m-d H:i:s') . $latitude . $longitude);
                    
                    // Try to get a more accurate region name from coordinates if the region is empty or unclear
                    // if (empty($region) || $region === $regionTh) {
                        $geoRegion = $this->getRegionFromCoordinates($latitude, $longitude);
                        if ($geoRegion) {
                            $region = $geoRegion;
                        }
                    // }
                    
                    $earthquakes[] = [
                        'origin_time' => $dateTime,
                        'magnitude' => $magnitude,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'depth' => $depth,
                        'region' => $region ?: $regionTh, // Use Thai region if English is not available
                        'region_th' => $regionTh,
                        'external_id' => $externalId,
                    ];
                } catch (\Exception $e) {
                    Log::warning('Failed to parse earthquake row', [
                        'error' => $e->getMessage(),
                        'row' => $row->html(),
                    ]);
                }
            });
            
            return $earthquakes;
        } catch (\Exception $e) {
            // Remove the debug statement
            // dd($e);
            Log::error('Error scraping earthquake data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    /**
     * Save the scraped earthquake data to the database.
     *
     * @return int Number of new earthquakes saved
     */
    public function fetchAndSaveData(): int
    {
        $earthquakes = $this->scrapeData();
        $count = 0;
        
        foreach ($earthquakes as $earthquakeData) {
            // Check if earthquake with this external_id already exists
            $exists = Earthquake::where('external_id', $earthquakeData['external_id'])->exists();
            
            if (!$exists) {
                $earthquake = Earthquake::create($earthquakeData);
                $count++;
                
                // Send SMS notification for significant earthquakes
                $this->sendNotificationsForEarthquake($earthquakeData);
            }
        }
        
        Log::info("Earthquake data updated: {$count} new earthquakes added");
        return $count;
    }
    
    /**
     * Send notifications about an earthquake to users who want to be notified
     *
     * @param array $earthquakeData
     * @return void
     */
    protected function sendNotificationsForEarthquake(array $earthquakeData): void
    {
        $magnitude = $earthquakeData['magnitude'];
        $region = $earthquakeData['region'];
        
        // Only send notifications for earthquakes with magnitude > 3.3 and in Myanmar region
        // if ($magnitude <= 3.3 || !str_contains(strtolower($region), 'myanmar')) {
        if ($magnitude <= 4) {
            Log::info('Skipping notification - criteria not met', [
                'magnitude' => $magnitude,
                'region' => $region,
                'required_magnitude' => '> 3.3',
                'required_region' => 'Myanmar'
            ]);
            return;
        }
        
        // Get all users with phone numbers
        $users = User::whereNotNull('phone_number')->get();
        
        if ($users->isEmpty()) {
            Log::info('No users to notify for earthquake', [
                'magnitude' => $magnitude,
                'region' => $region
            ]);
            return;
        }
        
        $notificationCount = 0;
        
        foreach ($users as $user) {
            $success = $this->twilioService->sendEarthquakeNotification(
                $user->phone_number, 
                $earthquakeData
            );
            
            if ($success) {
                $notificationCount++;
            }
        }
        
        Log::info('Earthquake notifications sent', [
            'magnitude' => $magnitude,
            'region' => $region,
            'successful_notifications' => $notificationCount,
            'total_recipients' => $users->count()
        ]);
    }

    /**
     * Get region name from latitude and longitude using reverse geocoding
     *
     * @param float $latitude
     * @param float $longitude
     * @return string|null
     */
    public function getRegionFromCoordinates(float $latitude, float $longitude): ?string
    {
        try {
            // Add a user agent as required by Nominatim's usage policy
            $response = Http::withHeaders([
                'User-Agent' => 'EarthquakeAPI/1.0 (einarr2012@gmail.com)'
            ])->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'json',
                'zoom' => 6, // Administrative region level
                'accept-language' => 'en'
            ]);
            
            $data = $response->json();
            
            // Extract the region information
            $address = $data['display_name'] ?? [];
            
            if($address){
                return $address;
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse date and time from the TMD format.
     *
     * @param string $dateTimeString
     * @return \DateTime
     */
    protected function parseDateTime(string $dateTimeString): \DateTime
    {
        // Clean up the date time string
        $dateTimeString = trim($dateTimeString);
        
        // Try to match the format "YYYY-MM-DD HH:MM:SS"
        if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $dateTimeString, $matches)) {
            return new \DateTime($matches[1]);
        }
        
        // If all else fails, try to parse as is
        return new \DateTime($dateTimeString);
    }
}