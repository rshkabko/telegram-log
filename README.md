## Requirement
- PHP 8.0 above
- Laravel 8 or higher

## Install
```bash
composer require kiriminaja/laravel-telegram-log
```

## Configurations
Please define Telegram Bot Credentials and chat id as environment parameters by modifying `.env` on your project path
```dotenv
TELEGRAM_BOT_TOKEN=null
TELEGRAM_CHAT_ID=null
TELEGRAM_LOGGER_TEMPLATE=null
TELEGRAM_OPTIONS=[]
```
Create new logging channel by modifying **config/logging.php** file
```php
'telegram' => [
    'driver' => 'custom',
    'via'    => TelegramLog\TelegramLogger::class,
    'level'  => 'debug',
]
```
Or if your default log channel is a stack, you can add it to the stack channel like this
```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['single', 'telegram'],
]
```
By default `LOG_CHANNEL` will be set into `stack` so you need to set default logger on env after setting up configurations above
```dotenv
LOG_CHANNEL=telegram
```
Publish config file and views to override
```shell
php artisan vendor:publish --provider "TelegramLog\TelegramServiceProvider"
```

```php
use Illuminate\Support\Facades\Log;

public function register()
    {
        $this->reportable(function (Throwable $e) {
            $channel = Log::channel('telegram');
            $channel->emergency($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        });
    }
```

## Create bot
For using this plugin, you need to create telegram bot
1. Go to [@BotFather](https://t.me/botfather) in the Telegram
2. Send `/newbot`
3. Set up name and bot-name for your bot.
4. Get token and add it to your .env file (it is written above)
5. Go to your bot and send `/start` message

## Change log template at runtime
1. Change config for template. 
```php
config(['telegram-logger.template'=>'laravel-telegram-logging::custom'])
```
2. Use `Log` as usual
