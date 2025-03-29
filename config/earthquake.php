<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Recipients
    |--------------------------------------------------------------------------
    |
    | List of phone numbers that should receive SMS notifications
    | for significant earthquakes (magnitude >= 4.0)
    |
    */
    'notification_recipients' => explode(',', env('EARTHQUAKE_NOTIFICATION_RECIPIENTS', '')),
];