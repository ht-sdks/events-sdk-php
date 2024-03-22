<?php

declare(strict_types=1);

namespace Hightouch\Consumer;

/**
 * InMemory consumer intended for testing purposes
 */
class InMemory extends Consumer
{
  protected string $type = 'InMemory';

  private array $queue = [];

  public function __destruct()
  {
    $this->flush();
  }

  public function track(array $message): bool
  {
    return $this->write($message);
  }

  public function identify(array $message): bool
  {
    return $this->write($message);
  }

  public function group(array $message): bool
  {
    return $this->write($message);
  }

  public function page(array $message): bool
  {
    return $this->write($message);
  }

  public function screen(array $message): bool
  {
    return $this->write($message);
  }

  public function alias(array $message): bool
  {
    return $this->write($message);
  }

  public function flush(): bool
  {
    $this->queue = [];
    return true;
  }

  private function write(array $body): bool
  {
    $this->queue[] = $body;
    return true;
  }
}
