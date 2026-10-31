<?php

namespace Flamix\TelegramLog\Tests\Fixtures;

/** An https:// stream wrapper that records every sendMessage call instead of reaching Telegram. */
class FakeTelegramApi
{
    /** @var list<array{token: string, query: array<string, string>}> */
    public static array $sent = [];

    public static bool $down = false;

    /** @var resource|null */
    public $context;

    private bool $read = false;

    public static function install(): void
    {
        self::$sent = [];
        self::$down = false;
        // The handler calls file_get_contents(), not Http, so Http::fake() never sees it.
        stream_wrapper_unregister('https');
        stream_wrapper_register('https', self::class);
    }

    public static function uninstall(): void
    {
        stream_wrapper_restore('https');
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened): bool
    {
        if (self::$down) {
            return false;
        }

        preg_match('~^https://api\.telegram\.org/bot([^/]+)/sendMessage\?(.*)$~', $path, $m);
        parse_str($m[2] ?? '', $query);
        self::$sent[] = ['token' => $m[1] ?? '', 'query' => $query];

        return true;
    }

    // PHP keeps calling stream_read() until it returns '', whatever stream_eof() says.
    public function stream_read(int $count): string
    {
        if ($this->read) {
            return '';
        }
        $this->read = true;

        return '{"ok":true}';
    }

    public function stream_eof(): bool
    {
        return $this->read;
    }

    public function stream_stat(): array
    {
        return [];
    }
}
