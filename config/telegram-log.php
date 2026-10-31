<?php

return [
    'token' => env('TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_CHAT_ID'),
    'template' => env('TELEGRAM_LOGGER_TEMPLATE', 'laravel-telegram-log::minimal'),

    // Always an array: stdClass breaks the handler's ?array, null breaks array_merge() on every send.
    'options' => json_decode(env('TELEGRAM_OPTIONS') ?: '[]', true) ?? [],
];
