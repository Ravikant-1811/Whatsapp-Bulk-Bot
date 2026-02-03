<?php

declare(strict_types=1);

return [
    'api_url' => getenv('WASENDER_API_URL') ?: 'https://www.wasenderapi.com/api/send-message',
    'api_key' => getenv('WASENDER_API_KEY') ?: '',
    'min_delay_seconds' => 45,
    'max_delay_seconds' => 90,
    'send_window_start' => '09:00',
    'send_window_end' => '20:00',
    'daily_limit' => 200,
];
