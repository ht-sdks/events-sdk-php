<?php

declare(strict_types=1);

namespace Hightouch\Test;

use Hightouch\Client;
use Hightouch\Consumer\ForkCurl;
use Hightouch\Consumer\LibCurl;
use Hightouch\Consumer\QueueConsumer;
use Hightouch\Consumer\Socket;
use Hightouch\HightouchException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private function queueConsumer(int $maxCount = 100): QueueConsumer
    {
        return new class ('original', ['flush_at' => 1, 'max_queue_size' => $maxCount]) extends QueueConsumer {
            protected int $flush_interval_ms = 0;
            protected int $max_queue_size_bytes = 350;
            /** @var string[] */
            public array $requests = [];
            public int $status = 503;
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
    }

    /** @dataProvider recoverableStatuses */
    public function testHealthyBatchPassesFailedProbeAfterSourceCooldown(int $status): void
    {
        $consumer = $this->queueConsumer();
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

    /** @dataProvider capacityLimits */
    public function testFullQueueResumesAutomaticDelivery(bool $byteLimit): void
    {
        $consumer = $this->queueConsumer($byteLimit ? 100 : 2);
        $item = $byteLimit ? ['data' => str_repeat('x', 180)] : [];
        $consumer->track(['messageId' => 'A'] + $item);
        $consumer->track(['messageId' => 'B'] + $item);
        self::assertFalse($consumer->track(['messageId' => 'early'] + $item));
        self::assertSame(['A'], $consumer->requests);
        ++$consumer->clock;
        $consumer->status = 200;
        self::assertTrue($consumer->track(['messageId' => 'C'] + $item));
        self::assertSame(['A', 'A', 'B', 'C'], $consumer->requests);
    }

    public static function capacityLimits(): array
    {
        return [[false], [true]];
    }

    public static function recoverableStatuses(): array
    {
        return [[408], [429], [503], [0]];
    }

    /** @runInSeparateProcess */
    public function testSocketRetainsBatchAfterFailedRead(): void
    {
        require __DIR__ . '/fixtures/socket-read.php';
        $GLOBALS['socketReplies'] = [false, "HTTP/1.1 200 OK\r\n"];
        $consumer = new class ('key', ['flush_at' => 1]) extends Socket {
            protected int $max_backoff_ms = 0;
            public float $clock = 10;
            protected function now(): float { return $this->clock; }
        };
        $event = ['context' => ['library' => ['name' => 'test', 'version' => '1']]];
        self::assertFalse($consumer->track($event));
        ++$consumer->clock;
        self::assertTrue($consumer->flush());
        self::assertSame([], $GLOBALS['socketReplies']);
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
