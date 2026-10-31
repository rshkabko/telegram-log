# Logging

## Send to Telegram

```php
use Illuminate\Support\Facades\Log;

Log::channel('telegram')->error('Payment failed', ['order' => 42]);
```

Arrives as (HTML parse mode, the level emoji five times, `app.name`):

```
⛔️⛔️⛔️⛔️⛔️ Shop (ERROR)
Payment failed {"order":42}
```

## Every log

```dotenv
LOG_CHANNEL=telegram
```

Or next to the file log:

```php
// config/logging.php
'stack' => [
    'driver'   => 'stack',
    'channels' => ['single', 'telegram'],
],
```

## Exceptions

```php
// bootstrap/app.php
use Illuminate\Support\Facades\Log;

->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->report(function (Throwable $e): void {
        if (config('app.debug')) { // keep local errors out of the chat
            return;
        }
        Log::channel('telegram')->emergency($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
    });
})
```

## Failures

```php
// No token or chat id: the log call itself throws InvalidArgumentException.
// Where a failed alert must not break the request, catch it:
try {
    Log::channel('telegram')->emergency('Queue is stuck');
} catch (Throwable $e) {
    Log::warning('Telegram alert failed: '.$e->getMessage());
}
```

- Telegram unreachable or answering an error: nothing is thrown, the error goes to the `single` channel. Its message contains the request URL, bot token included.
- Messages over 4096 bytes are sent as several messages, cut by bytes, so a cut can land inside an HTML tag or a multibyte character.
- Text is not escaped: a `<` in the message or context makes Telegram reject it with HTML parse mode.

## Templates

`telegram-log.template` and `TELEGRAM_LOGGER_TEMPLATE` are not read: the handler looks at `telegram-logger.template`, which no config file sets, so the built-in format above is always used. Setting `telegram-logger.template` at runtime does render a view, but on Monolog 3 it throws `TypeError` (`array_merge()` on a `LogRecord`).
