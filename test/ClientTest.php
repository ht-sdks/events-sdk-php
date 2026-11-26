<?php

declare(strict_types=1);

namespace Hightouch\Test;

use PHPUnit\Framework\TestCase;
use Hightouch\Client;
use Hightouch\HightouchException;
use Hightouch\Consumer\ForkCurl;
use Hightouch\Consumer\LibCurl;

class ClientTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @dataProvider collectorResponses
     */
    public function testFlushReportsCollectorResult(string $consumer, array $statuses, bool $success): void
    {
        require __DIR__ . '/fixtures/collector-response.php';
        $GLOBALS['collectorStatuses'] = $statuses;
        $client = new Client('key', ['consumer' => $consumer]);
        self::assertTrue($client->track(['event' => 'test', 'userId' => 'user']));
        self::assertSame($success, $client->flush());
        self::assertSame([], $GLOBALS['collectorStatuses']);
    }

    public static function collectorResponses(): array
    {
        return [
            'exhausted libcurl' => ['lib_curl', array_fill(0, 7, 503), false],
            'exhausted socket' => ['socket', array_fill(0, 8, 503), false],
            'rejected libcurl' => ['lib_curl', [400], false],
            'rejected socket' => ['socket', [400], false],
            'successful libcurl retry' => ['lib_curl', [503, 200], true],
            'successful socket retry' => ['socket', [503, 200], true],
        ];
    }

    /** @dataProvider httpStatuses */
    public function testTransientHttpStatus(int $status, bool $expected): void
    {
        $consumer = new class ('test') extends LibCurl {
            public function retryable(int $status): bool
            {
                return self::isTransientHttpStatus($status);
            }
        };
        self::assertSame($expected, $consumer->retryable($status));
    }

    public static function httpStatuses(): array
    {
        return [[400, false], [408, true], [429, true], [500, true], [599, true], [600, false]];
    }

    public function testClientUsesLibCurlConsumerAsDefault(): void
    {
        $client = new Client('foobar', []);
        self::assertInstanceOf(LibCurl::class, $client->getConsumer());
    }

    public function testClientCanProvideConsumerConfigurationAsString(): void
    {
        $client = new Client('foobar', ['consumer' => 'fork_curl']);
        self::assertInstanceOf(ForkCurl::class, $client->getConsumer());
    }

    public function testClientCanProvideConsumerConfigurationAsClassNamespace(): void
    {
        $client = new Client('foobar', [
            'consumer' => ForkCurl::class,
        ]);
        self::assertInstanceOf(ForkCurl::class, $client->getConsumer());
    }

    public function testClientThrowsIfConsumerNameIsInvalid(): void
    {
        $this->expectException(HightouchException::class);
        new Client('foobar', [
            'consumer' => 'nonexistent_consumer',
        ]);
    }

    public function testClientThrowsIfConsumerDoesNotExtendInterface(): void
    {
        $this->expectException(HightouchException::class);
        new Client('foobar', [
            'consumer' => ClientTest::class,
        ]);
    }
}
