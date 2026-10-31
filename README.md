# Telegram Log

Laravel log channel `telegram`: sends log records to a Telegram chat through your bot.

## Install

```bash
composer require flamix/telegram-log
```

```dotenv
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
```

## Documentation

See [docs/](docs/) for full documentation.

AI: Laravel Boost guideline and skills in resources/boost/.

- [Configuration](docs/configuration.md) — bot, `.env`, `config/telegram-log.php`, the channel
- [Logging](docs/logging.md) — sending, exceptions, what happens on failure, templates
