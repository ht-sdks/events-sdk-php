<?php

declare(strict_types=1);

namespace Hightouch\Test;

use Hightouch\Client;
use Hightouch\Consumer\ForkCurl;
use Hightouch\Consumer\LibCurl;
use Hightouch\Consumer\QueueConsumer;
use Hightouch\HightouchException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /** @dataProvider recoverableStatuses */
    public function testHealthyBatchPassesFailedProbeAfterSourceCooldown(int $status): void
    {
        $consumer = new class ('original', ['flush_at' => 1]) extends QueueConsumer {
            protected int $flush_interval_ms = 0;
            /** @var string[] */
            public array $requests = [];
            public int $status;
            public float $clock = 10;

            protected function now(): float
            {
                return $this->clock;
            }

            protected function flushBatch(array $messages): bool
            {
                $id = $messages[0]['messageId'];
                $this->requests[] = $id;
                $status = $id === 'A' ? $this->status : 200;
                $this->recordHttpStatus($status);
                return $status === 200;
            }
        };
        $consumer->status = $status;
        $consumer->track(['messageId' => 'A']);
        ++$consumer->clock;
        $consumer->flush(); // A fails its deferred attempt too.
        $consumer->track(['messageId' => 'B']);
        $consumer->clock += 1.9;
        $consumer->flush();
        self::assertSame(['A', 'A'], $consumer->requests);
        $consumer->clock += 0.2;
        $consumer->flush();
        self::assertSame(['A', 'A', 'B', 'A'], $consumer->requests);
        $consumer->status = 200;
        $consumer->clock += 60;
        self::assertTrue($consumer->flush());
        self::assertSame(['A', 'A', 'B', 'A', 'A'], $consumer->requests);
    }

    public static function recoverableStatuses(): array
    {
        return [[408], [429], [503], [0]];
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
