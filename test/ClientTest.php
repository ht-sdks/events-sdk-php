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
