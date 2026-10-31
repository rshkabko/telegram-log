<?php

namespace Flamix\TelegramLog\Tests\Feature;

use Flamix\TelegramLog\TelegramServiceProvider;
use Flamix\TelegramLog\Tests\Fixtures\FakeTelegramApi;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Orchestra\Testbench\TestCase;

require_once __DIR__ . '/../Fixtures/FakeTelegramApi.php';

class TelegramChannelTest extends TestCase
{
    private string $singleLog;

    protected function getPackageProviders($app)
    {
        return [TelegramServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $this->singleLog = sys_get_temp_dir() . '/telegram-log-test-' . getmypid() . '.log';
        @unlink($this->singleLog);

        $app['config']->set('app.name', 'Shop');
        $app['config']->set('logging.channels.single.path', $this->singleLog);
        $app['config']->set('telegram-log.token', 'fake-token');
        $app['config']->set('telegram-log.chat_id', '-100500');
    }

    protected function appDefinesTheChannel($app)
    {
        $app['config']->set('logging.channels.telegram', [
            'driver' => 'custom',
            'via' => \Flamix\TelegramLog\TelegramLogger::class,
            'level' => 'error',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        FakeTelegramApi::install();
    }

    protected function tearDown(): void
    {
        FakeTelegramApi::uninstall();
        @unlink($this->singleLog);
        parent::tearDown();
    }

    public function test_an_error_reaches_the_configured_chat()
    {
        Log::channel('telegram')->error('Payment failed', ['order' => 42]);

        $this->assertSame([[
            'token' => 'fake-token',
            'query' => [
                'text' => "<b>⛔️⛔️⛔️⛔️⛔️ Shop</b> (ERROR)\nPayment failed {\"order\":42} \n",
                'chat_id' => '-100500',
                'parse_mode' => 'html',
            ],
        ]], FakeTelegramApi::$sent);
    }

    public function test_options_from_the_env_json_reach_telegram()
    {
        $this->assertSame([], $this->optionsFromEnv(''));

        config(['telegram-log.options' => $this->optionsFromEnv('{"disable_notification":true,"message_thread_id":7}')]);

        Log::channel('telegram')->error('Quiet alert');

        $this->assertSame('1', FakeTelegramApi::$sent[0]['query']['disable_notification']);
        $this->assertSame('7', FakeTelegramApi::$sent[0]['query']['message_thread_id']);
    }

    private function optionsFromEnv(string $json): mixed
    {
        putenv("TELEGRAM_OPTIONS={$json}");
        try {
            return (require __DIR__ . '/../../config/telegram-log.php')['options'];
        } finally {
            putenv('TELEGRAM_OPTIONS');
        }
    }

    public function test_a_long_message_is_sent_in_4096_byte_chunks()
    {
        Log::channel('telegram')->info(str_repeat('a', 5000));

        $this->assertCount(2, FakeTelegramApi::$sent);
        $this->assertSame(4096, strlen(FakeTelegramApi::$sent[0]['query']['text']));
    }

    #[DefineEnvironment('appDefinesTheChannel')]
    public function test_an_app_defined_channel_is_not_replaced()
    {
        Log::channel('telegram')->info('Below the channel level');
        Log::channel('telegram')->critical('Above it');

        $this->assertCount(1, FakeTelegramApi::$sent);
        $this->assertStringContainsString('Above it', FakeTelegramApi::$sent[0]['query']['text']);
    }

    public function test_logging_throws_when_the_token_is_missing()
    {
        config(['telegram-log.token' => null]);

        $this->expectException(InvalidArgumentException::class);

        Log::channel('telegram')->error('Nobody will see this');
    }

    public function test_an_unreachable_telegram_is_reported_to_the_single_channel()
    {
        FakeTelegramApi::$down = true;

        Log::channel('telegram')->error('Lost alert');

        $this->assertStringContainsString('Failed to open stream', file_get_contents($this->singleLog));
    }

    public function test_an_unreachable_telegram_does_not_write_the_bot_token_to_the_log()
    {
        $this->markTestIncomplete('Bot token leaks into laravel.log: the caught warning carries the full URL, src/TelegramHandler.php:59,87');

        FakeTelegramApi::$down = true;

        Log::channel('telegram')->error('Lost alert');

        $this->assertStringNotContainsString('fake-token', file_get_contents($this->singleLog));
    }

    public function test_a_token_on_the_logging_channel_overrides_the_package_config()
    {
        $this->markTestIncomplete('Channel-level token/chat_id/options are ignored: getConfigValue() reads $this->config, which is never set, src/TelegramHandler.php:100');

        config(['logging.channels.telegram.token' => 'channel-token']);

        Log::channel('telegram')->error('Boom');

        $this->assertSame('channel-token', FakeTelegramApi::$sent[0]['token']);
    }

    public function test_the_configured_template_renders_the_message()
    {
        $this->markTestIncomplete('telegram-log.template is never read: the handler asks for telegram-logger.template, src/TelegramHandler.php:119');

        Log::channel('telegram')->error('Boom');

        $this->assertStringContainsString('<pre>', FakeTelegramApi::$sent[0]['query']['text']);
    }
}
