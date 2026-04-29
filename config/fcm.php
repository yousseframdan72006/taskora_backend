<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    | Found in Firebase Console → Project Settings → General → Project ID
    */
    'project_id' => env('FCM_PROJECT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Service Account JSON Path
    |--------------------------------------------------------------------------
    | Path to the downloaded Service Account JSON file.
    | Firebase Console → Project Settings → Service Accounts → Generate new private key
    | Store the file in: storage/app/firebase-service-account.json
    */
    'service_account_path' => env(
        'FCM_SERVICE_ACCOUNT_PATH',
        storage_path('app/firebase-service-account.json')
    ),
];
