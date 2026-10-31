# Configuration

Bot token, chat and extra sendMessage options. [Telegram Bot API: sendMessage](https://core.telegram.org/bots/api#sendmessage)

## Bot

1. Open [@BotFather](https://t.me/botfather), send `/newbot`, pick a name, copy the token.
2. Send `/start` to the bot (or add it to a group), then take the chat id from `https://api.telegram.org/bot<token>/getUpdates`.

## .env

```dotenv
TELEGRAM_BOT_TOKEN=123456:ABC...
TELEGRAM_CHAT_ID=-1001234567890       # numeric only, groups are negative; @username fails with «Unable to create configured logger»
TELEGRAM_OPTIONS={"disable_notification":true,"message_thread_id":7}   # optional, JSON object
```

## config/telegram-log.php

```php
return [
    'token'    => env('TELEGRAM_BOT_TOKEN'),
    'chat_id'  => env('TELEGRAM_CHAT_ID'),
    'template' => env('TELEGRAM_LOGGER_TEMPLATE', 'laravel-telegram-log::minimal'), // not read, see logging.md
    'options'  => json_decode(env('TELEGRAM_OPTIONS', '[]'), true), // merged into every sendMessage query
];
```

Publish it (and the `minimal` view) only to change it:

```bash
php artisan vendor:publish --provider="Flamix\TelegramLog\TelegramServiceProvider" --tag=config
php artisan vendor:publish --provider="Flamix\TelegramLog\TelegramServiceProvider" --tag=views
```

## Channel

The provider adds this channel when `config/logging.php` has no `telegram` key:

```php
'telegram' => [
    'driver' => 'custom',
    'via'    => \Flamix\TelegramLog\TelegramLogger::class,
    'level'  => 'debug',
],
```

Define it yourself to change the level. Only `level` is read from it: `token`, `chat_id` and `options` here are ignored, they come from `telegram-log.*`.

```php
'telegram' => [
    'driver' => 'custom',
    'via'    => \Flamix\TelegramLog\TelegramLogger::class,
    'level'  => 'error', // info and warning no longer reach Telegram
],
```
