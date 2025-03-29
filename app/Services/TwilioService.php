<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class TwilioService
{
    protected Client $client;
    protected string $fromNumber;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->fromNumber = config('services.twilio.from_number');
    }

    /**
     * Send an SMS message
     *
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @return bool Success status
     */
    public function sendSms(string $to, string $message): bool
    {
        try {
            $this->client->messages->create(
                $to,
                [
                    'from' => $this->fromNumber,
                    'body' => $message
                ]
            );
            
            Log::info('SMS sent successfully', [
                'to' => $to,
                'message_length' => strlen($message)
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send SMS', [
                'error' => $e->getMessage(),
                'to' => $to
            ]);
            
            return false;
        }
    }

    /**
     * Send earthquake notification
     *
     * @param string $to Recipient phone number
     * @param array $earthquake Earthquake data
     * @return bool Success status
     */
    public function sendEarthquakeNotification(string $to, array $earthquake): bool
    {
        $message = $this->formatEarthquakeMessage($earthquake);
        return $this->sendSms($to, $message);
    }

    /**
     * Format earthquake data into a readable message
     *
     * @param array $earthquake Earthquake data
     * @return string Formatted message
     */
    protected function formatEarthquakeMessage(array $earthquake): string
    {
        $dateTime = $earthquake['origin_time'] instanceof \DateTime 
            ? $earthquake['origin_time']->format('Y-m-d H:i:s') 
            : $earthquake['origin_time'];
            
        $message = "EARTHQUAKE ALERT\n";
        $message .= "Time: {$dateTime}\n";
        $message .= "Magnitude: {$earthquake['magnitude']}\n";
        $message .= "Location: {$earthquake['region']}\n";
        $message .= "Coordinates: {$earthquake['latitude']}°, {$earthquake['longitude']}°\n";
        $message .= "Depth: {$earthquake['depth']} km";
        
        return $message;
    }
}