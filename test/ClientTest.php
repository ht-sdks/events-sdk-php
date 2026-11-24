<?php

declare(strict_types=1);

namespace Hightouch\Test;

use PHPUnit\Framework\TestCase;
use Hightouch\Client;
use Hightouch\HightouchException;
use Hightouch\Consumer\ForkCurl;
use Hightouch\Consumer\LibCurl;
use Hightouch\Consumer\QueueConsumer;

class ClientTest extends TestCase
{
    /** @dataProvider recoverableStatuses */
    public function testRecoverableFailureRetainsBatchAndDefersAnotherFlush(int $status): void
    {
        $consumer = new class ('original') extends QueueConsumer {
            public array $requests = [];
            public int $status;

            protected function flushBatch(array $messages): bool
            {
                $this->requests[] = $messages;
                $this->recordHttpStatus($this->status);
                return false;
            }

            public function pending(): array
            {
                return $this->queue;
            }
        };
        $consumer->status = $status;
        $event = ['messageId' => 'original-event'];
        self::assertTrue($consumer->track($event));
        self::assertFalse($consumer->flush());
        self::assertFalse($consumer->flush());
        self::assertSame([[$event]], $consumer->requests);
        self::assertSame([$event], $consumer->pending());
    }

    public static function recoverableStatuses(): array
    {
        return [[408], [429], [503], [0]];
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
