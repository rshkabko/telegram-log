---
name: telegram-log-delivery
description: "Flamix telegram-log delivery. Use when wiring Log::channel('telegram'), TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID, TELEGRAM_OPTIONS, telegram-log.options or a telegram entry in config/logging.php. Also use for the symptoms: alerts never arrive in the chat, a log call throws «Bot token or chat id is not defined for Telegram logger», laravel.log shows «Unable to create configured logger», a custom Blade template for the message is ignored, the bot token shows up in laravel.log, a long alert arrives cut or rejected. Skip for Telegram bots and notifications that are not logging."
---

# Telegram log delivery

The `telegram` channel builds `Flamix\TelegramLog\TelegramHandler`, which calls `https://api.telegram.org/bot<token>/sendMessage` with `file_get_contents()` for every record at or above the channel level.

Full docs: vendor/flamix/telegram-log/docs/configuration.md and logging.md (token in the failure log, 4096-byte cuts, the dead template setting). Below is how to diagnose a lost alert.

## When an alert does not arrive

| What you see | Cause | Do this |
|---|---|---|
| The log call throws `InvalidArgumentException` | `telegram-log.token` or `telegram-log.chat_id` is empty. Checked on every write, not at boot | Set both in `.env`; wrap alerts that run inside user requests in `try/catch (Throwable)` |
| `Unable to create configured logger` in laravel.log, alerts go to the emergency logger | The handler could not be built, e.g. `TELEGRAM_CHAT_ID=@channel` (the property is `?int`, numeric ids only), or a published config that still does `json_decode(env('TELEGRAM_OPTIONS'))` without `true` and a JSON object in `.env` | `vendor:publish --provider="Flamix\TelegramLog\TelegramServiceProvider" --tag=config --force`, or fix that `json_decode` by hand |
| Nothing in the chat, an error in the `single` log | Telegram rejected the request: wrong chat id, the bot never got `/start`, or unescaped `<` / `&` in the message (HTML parse mode) | Fix the id; `e()` user text before logging it |
| Only some levels arrive | The app defines `logging.channels.telegram` with a higher `level` | That entry wins over the package default `debug` |
| Tests hit Telegram | `Http::fake()` does not cover `file_get_contents()` | Leave `TELEGRAM_BOT_TOKEN` empty in `phpunit.xml` and assert the exception, or swap the channel: `config(['logging.channels.telegram' => ['driver' => 'monolog', 'handler' => \Monolog\Handler\TestHandler::class]])` |

## Traps

- `token`, `chat_id`, `options` on the logging channel are ignored: `getConfigValue()` reads a `$this->config` that is never set. Two chats need two apps or a different handler, not two channels.
- SSL peer verification is off on purpose for hosts with an old OpenSSL.
