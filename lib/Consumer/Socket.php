<?php

declare(strict_types=1);

namespace Hightouch\Consumer;

class Socket extends QueueConsumer
{
    protected string $type = 'Socket';

    private string $host = 'us-east-1.hightouch-events.com';
    private string $protocol = 'ssl'; // ssl or tls
    private float $timeout = 5;

    /**
     * Creates a new socket consumer for dispatching async requests immediately.
     *
     * @param string $writeKey
     * @param array $options
     *     number "timeout" - the timeout for connecting
     *     bool "tls" - if we should use TLS instead of SSL.
     */
    public function __construct(string $writeKey, array $options = [])
    {
        parent::__construct($writeKey, $options);

        if (isset($options['host'])) {
            $this->host = $options['host'];
        }

        if (isset($options['tls']) && $options['tls']) {
            $this->protocol = 'tls';
        }

        if (isset($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }
    }

    protected function flushBatch($batch): bool
    {
        $socket = $this->createSocket();

        if (!$socket) {
            return false;
        }

        $payload = $this->payload($batch);
        $payload = json_encode($payload);

        $body = $this->createBody($this->host, $payload);
        if ($body === false) {
            return false;
        }

        return $this->makeRequest($socket, $body);
    }

    /**
     * Open a connection to the target host.
     *
     * @return false|resource
     */
    private function createSocket()
    {
        $port = 443;

        // Open our socket to the API Server.
        $socket = @pfsockopen(
            $this->protocol . '://' . $this->host,
            $port,
            $errno,
            $errstr,
            $this->timeout
        );

        // If we couldn't open the socket, handle the error.
        if ($socket === false) {
            $this->recordHttpStatus(0);
            $this->handleError($errno, $errstr);
        }

        return $socket;
    }

    /**
     * Create the request body.
     *
     * @param string $host
     * @param string $content
     * @return string body
     */
    private function createBody(string $host, string $content)
    {
        $req = "POST /v1/batch HTTP/1.1\r\n";
        $req .= 'Host: ' . $host . "\r\n";
        $req .= "Content-Type: application/json\r\n";
        $req .= 'Authorization: Basic ' . base64_encode($this->writeKey . ':') . "\r\n";
        $req .= "Accept: application/json\r\n";

        // Send user agent in the form of {library_name}/{library_version} as per RFC 7231.
        $content_json = json_decode($content, true);
        $library = $content_json['batch'][0]['context']['library'];
        $libName = $library['name'];
        $libVersion = $library['version'];
        $req .= "User-Agent: $libName/$libVersion\r\n";

        // Compress content if compress_request is true
        if ($this->compress_request) {
            $content = gzencode($content);

            $req .= "Content-Encoding: gzip\r\n";
        }

        $req .= 'Content-length: ' . strlen($content) . "\r\n";
        $req .= "\r\n";
        $req .= $content;

        // Verify payload size is below 512KB
        if (strlen($req) >= 500 * 1024) {
            $msg = 'Payload size is larger than 512KB';
            /** @noinspection ForgottenDebugOutputInspection */
            error_log('[Hightouch][' . $this->type . '] ' . $msg);

            return false;
        }

        return $req;
    }

    /**
     * Attempt to write the request to the socket, wait for response if debug
     * mode is enabled.
     *
     * @param resource|false $socket the handle for the socket
     * @param string $req request body
     * @return bool
     */
    private function makeRequest($socket, string $req): bool
    {
        $bytes_total = strlen($req);

        // Retries with exponential backoff until success
        $backoff = 100; // Set initial waiting time to 100ms

        while (true) {
            $bytes_written = 0;
            $closed = false;
            // Send request to server
            while (!$closed && $bytes_written < $bytes_total) {
                $written = @fwrite($socket, substr($req, $bytes_written));
                if (!$written) {
                    $this->recordHttpStatus(0);
                    $this->handleError(13, 'Failed to write to socket.');
                    $closed = true;
                } else {
                    $bytes_written += $written;
                }
            }

            // Get response for request
            $statusCode = 0;

            if (!$closed) {
                $res = self::parseResponse(fread($socket, 2048));
                $statusCode = (int)$res['status'];
            }
            fclose($socket);

            $retryable = $this->recordHttpStatus($statusCode);
            if ($statusCode >= 200 && $statusCode < 300) {
                return true;
            }
            if (!$retryable || $backoff >= $this->max_backoff_ms) {
                $this->handleError($statusCode, $res['message'] ?? 'No HTTP response');
                return false;
            }

            usleep($backoff * 1000);
            $backoff *= 2;
            $socket = $this->createSocket();
            if (!$socket) {
                return false;
            }
        }
    }

    /**
     * Parse our response from the server, check header and body.
     * @param string $res
     * @return array
     *     string $status HTTP code, e.g. "200"
     *     string $message JSON response from the api
     */
    private static function parseResponse(string $res): array
    {
        [$first,] = explode("\n", $res, 2);

        // Response comes back as HTTP/1.1 200 OK
        // Final line contains HTTP response.
        [, $status, $message] = explode(' ', $first, 3);

        return [
            'status'  => $status ?? null,
            'message' => $message,
        ];
    }
}
