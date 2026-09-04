<?php

return [
    'temporary_file_upload' => [
        // Keep temp uploads private under storage/app/private.
        'disk' => env('LIVEWIRE_TEMP_DISK', 'local'),
        'directory' => env('LIVEWIRE_TEMP_DIR', 'livewire-tmp'),
        // Match Filament's 50MB upload limit (value in KB).
        'rules' => 'file|max:51200',
        // Allow slower connections more time to finish large uploads.
        'max_upload_time' => 15,
    ],
];
