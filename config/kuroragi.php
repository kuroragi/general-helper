<?php
return [
    'activity_log_path' => storage_path('logs/activity'),
    'activity_log_file_prefix' => 'activity-',
    'roll_day' => 'monday',
    'roll_time' => '01:00', // HH:MM
    'default_reader_limit' => 50,
    'auth_model' => null, // null => use config('auth.providers.users.model')
];
