<?php

namespace App\Console\Commands;

use App\Models\Earthquake;
use App\Models\User;
use App\Services\TwilioService;
use Illuminate\Console\Command;

class SendEarthquakeNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'earthquakes:notify 
                            {earthquake_id : The ID of the earthquake to notify about} 
                            {--phone=* : Specific phone number(s) to send notification to} 
                            {--all : Send to all users with phone numbers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send an SMS notification about a specific earthquake';

    /**
     * Execute the console command.
     */
    public function handle(TwilioService $twilioService): int
    {
        $earthquakeId = $this->argument('earthquake_id');
        $phoneNumbers = $this->option('phone');
        $sendToAll = $this->option('all');
        
        $earthquake = Earthquake::all();
        
        if (!$earthquake) {
            $this->error("Earthquake with ID {$earthquakeId} not found");
            return Command::FAILURE;
        }
        
        $this->info("Preparing to send notifications about earthquake (magnitude {$earthquake->magnitude})...");
        
        $successCount = 0;
        $failureCount = 0;
        
        // Send to specific phone numbers if provided
        if (!empty($phoneNumbers)) {
            foreach ($phoneNumbers as $phoneNumber) {
                $this->info("Sending to {$phoneNumber}...");
                $success = $twilioService->sendEarthquakeNotification($phoneNumber, $earthquake->toArray());
                
                if ($success) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            }
        }
        
        // Send to all users if requested
        if ($sendToAll) {
            $users = User::whereNotNull('phone_number')->get();
            
            $this->info("Sending to {$users->count()} users with phone numbers...");
            
            foreach ($users as $user) {
                $this->line("  - Sending to {$user->phone_number}...");
                $success = $twilioService->sendEarthquakeNotification($user->phone_number, $earthquake->toArray());
                
                if ($success) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            }
        }
        
        // Show summary
        $this->newLine();
        $this->info("Notification summary:");
        $this->line("  - Successful: {$successCount}");
        $this->line("  - Failed: {$failureCount}");
        
        return ($failureCount === 0) ? Command::SUCCESS : Command::FAILURE;
    }
}