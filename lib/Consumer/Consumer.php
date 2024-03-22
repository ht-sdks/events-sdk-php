<?php

declare(strict_types=1);

namespace Hightouch\Consumer;

abstract class Consumer
{
    protected string $type = 'Consumer';

    /**
     * @var array<string,mixed>
     */
    protected array $options;
    protected string $writeKey;

    /**
     * @param string $writeKey
     * @param array $options
     */
    public function __construct(string $writeKey, array $options = [])
    {
        $this->writeKey = $writeKey;
        $this->options = $options;
    }

    abstract public function __destruct();

    /**
     * Tracks a user action
     *
     * @param array $message
     * @return bool whether the track call succeeded
     */
    abstract public function track(array $message): bool;

    /**
     * Tags traits about the user.
     *
     * @param array $message
     * @return bool whether the identify call succeeded
     */
    abstract public function identify(array $message): bool;

    /**
     * Tags traits about the group.
     *
     * @param array $message
     * @return bool whether the group call succeeded
     */
    abstract public function group(array $message): bool;

    /**
     * Tracks a page view.
     *
     * @param array $message
     * @return bool whether the page call succeeded
     */
    abstract public function page(array $message): bool;

    /**
     * Tracks a screen view.
     *
     * @param array $message
     * @return bool whether the group call succeeded
     */
    abstract public function screen(array $message): bool;

    /**
     * Aliases from one user id to another
     *
     * @param array $message
     * @return bool whether the alias call succeeded
     */
    abstract public function alias(array $message): bool;

    /**
     * Flushes our queue of messages to the server
     */
    abstract public function flush(): bool;

    /**
     * Getter method for consumer type.
     *
     * @return string
     */
    public function getConsumer(): string
    {
        return $this->type;
    }

    /**
     * On an error, try and call the error handler, if debugging output to
     * error_log as well.
     * @param int $code
     * @param string|array $msg
     */
    protected function handleError(int $code, mixed $msg): void
    {
        if (isset($this->options['error_handler'])) {
            $handler = $this->options['error_handler'];
            $handler($code, $msg);
        }

        if ($this->debug()) {
            error_log('[Hightouch][' . $this->type . '] ' . $msg);
        }
    }

    /**
     * Check whether debug mode is enabled
     * @return bool
     */
    protected function debug(): bool
    {
        return $this->options['debug'] ?? false;
    }
}
