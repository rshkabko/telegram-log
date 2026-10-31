@php /** @var \Laravel\Boost\Install\GuidelineAssist $assist */ @endphp
# Flamix Telegram Log

A Laravel log channel named `telegram` that posts records to one Telegram chat through a bot. It owns the channel and the message format; it does not send notifications, queue messages or talk to Telegram in any other way.

- Do not write a Telegram HTTP client for alerts: `Log::channel('telegram')->error('…', [...])`, or add `telegram` to the `stack` channels.
- The channel registers itself unless `config/logging.php` already has a `telegram` key; then only that entry's `level` is used.
- Credentials come only from `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`, `TELEGRAM_OPTIONS` (JSON object) via `telegram-log.*`. A `token` or `chat_id` on the logging channel is ignored.
- Without a token or chat id the log call throws `InvalidArgumentException`. Wrap alerts in code paths that must not fail (or guard on `config('telegram-log.token') && config('telegram-log.chat_id')`).
- Telegram errors are not thrown; they go to the `single` channel with the bot token in the URL.
- `telegram-log.template` / `TELEGRAM_LOGGER_TEMPLATE` are not read; the format is fixed. Do not set `telegram-logger.template`: it throws on Monolog 3.
- Publish the config only to change it: {{ $assist->artisanCommand('vendor:publish --provider="Flamix\TelegramLog\TelegramServiceProvider" --tag=config') }}.
- Full docs: `vendor/flamix/telegram-log/docs/*.md` (configuration, logging).

Detail lives in the skill: `telegram-log-delivery`.
