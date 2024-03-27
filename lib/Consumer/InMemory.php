<?php

declare(strict_types=1);

namespace Hightouch\Consumer;

/**
 * InMemory consumer intended for testing purposes
 */
class InMemory extends QueueConsumer
{
  protected string $type = 'InMemory';

  protected function flushBatch(array $messages): bool
  {
    return true;
  }
}
