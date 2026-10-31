<?php

namespace Flamix\TelegramLog;

use Monolog\Logger;

/** Laravel custom-driver factory (`'via'`) that builds a Monolog logger around TelegramHandler. */
class TelegramLogger
{
    /** @param array $config The logging.channels.telegram array; only `level` is used. */
    public function __invoke(array $config): Logger
    {
        return new Logger(
            config('app.name'),
            [
                new TelegramHandler($config),
            ]
        );
    }
}
