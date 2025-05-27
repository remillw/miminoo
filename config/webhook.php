<?php

return [
    'api_key' => env('WEBHOOK_API_KEY'),
    'timeout' => env('WEBHOOK_TIMEOUT', 30),
    'retry_attempts' => env('WEBHOOK_RETRY_ATTEMPTS', 3),
]; 