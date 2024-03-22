<?php

declare(strict_types=1);

namespace Hightouch\Consumer;

class LibCurl extends QueueConsumer
{
    protected string $type = 'LibCurl';

    private string $host = 'https://us-east-1.hightouch-events.com';
    private int $timeout = 0; // infinite
    private int $connectTimeout = 300;

    /**
     * @param string $writeKey
     * @param array $options
     */
    public function __construct(string $writeKey, array $options = [])
    {
        parent::__construct($writeKey, $options);

        if (isset($options['host'])) {
            $this->host = $options['host'];
        }

        if (isset($options['timeout'], $options['curl_timeout'])) {
            $this->timeout = $options['timeout'] || $options['curl_timeout'];
        }

        if (isset($options['connecttimeout'], $options['curl_connecttimeout'])) {
            $this->connectTimeout = $options['connecttimeout'] || $options['curl_connecttimeout'];
        }
    }

    /**
     * Make a sync request to our API. If debug is
     * enabled, we wait for the response
     * and retry once to diminish impact on performance.
     * @param array $messages array of all the messages to send
     * @return bool whether the request succeeded
     */
    protected function flushBatch(array $messages): bool
    {
        $body = $this->payload($messages);
        $payload = json_encode($body);

        if ($this->compress_request) {
            $payload = gzencode($payload);
        }

        $url = "$this->host/v1/batch";

        $backoff = 100; // Set initial waiting time to 100ms

        while ($backoff < $this->max_backoff_ms) {
            // open connection
            $ch = curl_init();

            // set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_USERPWD, $this->writeKey . ':');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);

            // set variables for headers
            $header = [];
            $header[] = 'Content-Type: application/json';

            if ($this->compress_request) {
                $header[] = 'Content-Encoding: gzip';
            }

            // Send user agent in the form of {library_name}/{library_version} as per RFC 7231.
            $library = $messages[0]['context']['library'];
            $libName = $library['name'];
            $libVersion = $library['version'];
            $header[] = "User-Agent: $libName/$libVersion";

            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // retry failed requests just once to diminish impact on performance
            $responseContent = curl_exec($ch);

            $err = curl_error($ch);
            if ($err) {
                $this->handleError(curl_errno($ch), $err);
                return false;
            }

            $responseCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //close connection
            curl_close($ch);

            if ($responseCode !== 200) {
                // log error
                $this->handleError($responseCode, $responseContent);

                if (($responseCode >= 500 && $responseCode <= 600) || $responseCode === 429) {
                    // If status code is greater than 500 and less than 600, it indicates server error
                    // Error code 429 indicates rate limited.
                    // Retry uploading in these cases.
                    usleep($backoff * 1000);
                    $backoff *= 2;
                } elseif ($responseCode >= 400) {
                    break;
                }
            } else {
                break; // no error
            }
        }

        return true;
    }
}
