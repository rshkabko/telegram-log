<?php

namespace Flamix\TelegramLog;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Exception;

/** Monolog handler that posts each record to a Telegram chat through the Bot API sendMessage. */
class TelegramHandler extends AbstractProcessingHandler
{
    const EMOJI = [
        'emergency' => '🚨',
        'alert'     => '‼️',
        'critical'  => '🛑',
        'error'     => '⛔️',
        'warning'   => '⚠️',
        'notice'    => '🐽',
        'info'      => 'ℹ️',
        'debug'     => '🤖',
    ];

    protected ?string $botToken, $appName, $appEnv;
    protected ?int                         $chatId;
    protected ?array                       $options;

    public function __construct(array $config)
    {
        $level = Logger::toMonologLevel($config['level']);

        parent::__construct($level, true);

        $this->options  = $this->getConfigValue("options");
        $this->botToken = $this->getConfigValue("token");
        $this->chatId   = $this->getConfigValue("chat_id");

        $this->appName = config("app.name");
        $this->appEnv  = config("app.env");
    }


    // Untyped on purpose: Monolog 2 passes an array, Monolog 3 a LogRecord; both read as $record['…'].
    /** @param array|\Monolog\LogRecord $record */
    protected function write($record): void
    {
        if (!$this->botToken || !$this->chatId) {
            throw new \InvalidArgumentException('Bot token or chat id is not defined for Telegram logger');
        }

        try {
            $textChunks = str_split($this->formatText($record), 4096);

            foreach ($textChunks as $textChunk) {
                $this->sendMessage($textChunk);
            }
        } catch (Exception $exception) {
            \Log::channel('single')->error($exception->getMessage());
        }
    }

    private function sendMessage(string $text): void
    {
        $httpQuery = http_build_query(array_merge(
            [
                'text'       => $text,
                'chat_id'    => $this->chatId,
                'parse_mode' => 'html',
            ],
            $this->options
        ));

        // Peer verification is off for hosts with an old OpenSSL; turning it on breaks them.
        $arrayContextOptions = [
            "ssl" => [
                "verify_peer"      => false,
                "verify_peer_name" => false
            ]
        ];

        file_get_contents(
            'https://api.telegram.org/bot' . $this->botToken . '/sendMessage?' . $httpQuery,
            false,
            stream_context_create($arrayContextOptions)
        );
    }

    /** @return string|array|null */
    private function getConfigValue(string $key, ?string $defaultConfigKey = null)
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        return config($defaultConfigKey ?: "telegram-log.$key");
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter("%message% %context% %extra%\n", null, false, true);
    }

    /** @param array|\Monolog\LogRecord $record */
    private function formatText($record): string
    {
        $appName = $this->getFormatEmoji($record['level_name'])." ".$this->appName;
        if ($template = config('telegram-logger.template')) {
            return view($template, array_merge($record, [
                    'appName' => $appName,
                    'appEnv'  => $this->appEnv,
                ])
            );
        }

        return sprintf("<b>%s</b> (%s)\n%s", $appName, $record['level_name'], $record['formatted']);
    }

    private function getFormatEmoji(string $levelName): string
    {
        $emoji_replace = self::EMOJI[strtolower($levelName)] ?? "❓";

        $emoji_text = "";
        for ($i = 1; $i <= 5; $i++) {
            $emoji_text .= $emoji_replace;
        }

        return $emoji_text;
    }
}
