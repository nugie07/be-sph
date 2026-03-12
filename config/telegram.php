<?php

return [

    'enabled' => env('TELEGRAM_ENABLED', false),

    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),

    'channel_id' => env('TELEGRAM_CHANNEL_ID', ''),

    'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),

];
