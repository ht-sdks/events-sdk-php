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
