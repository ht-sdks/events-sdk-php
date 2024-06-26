<?php

declare(strict_types=1);

namespace Hightouch\Consumer;

class ForkCurl extends QueueConsumer
{
    protected string $type = 'ForkCurl';

    private string $host = 'https://us-east-1.hightouch-events.com';

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
    }

    /**
     * Make an async request to our API. Fork a curl process, immediately send
     * to the API. If debug is enabled, we wait for the response.
     * @param array $messages array of all the messages to send
     * @return bool whether the request succeeded
     */
    protected function flushBatch(array $messages): bool
    {
        $body = $this->payload($messages);
        $payload = json_encode($body);

        // Escape for shell usage.
        $payload = escapeshellarg($payload);
        $writeKey = escapeshellarg($this->writeKey);

        $url = "$this->host/v1/batch";

        $cmd = "curl -u $writeKey: -X POST -H 'Content-Type: application/json'";

        $tmpfname = '';
        if ($this->compress_request) {
            // Compress request to file
            $tmpfname = tempnam('/tmp', 'forkcurl_');
            $cmd2 = 'echo ' . $payload . ' | gzip > ' . $tmpfname;
            exec($cmd2, $output, $exit);

            if ($exit !== 0) {
                $this->handleError($exit, $output);
                return false;
            }

            $cmd .= " -H 'Content-Encoding: gzip'";

            $cmd .= " --data-binary '@" . $tmpfname . "'";
        } else {
            $cmd .= ' -d ' . $payload;
        }

        $cmd .= " '" . $url . "'";

        // Verify payload size is below 512KB
        if (strlen($payload) >= 500 * 1024) {
            $msg = 'Payload size is larger than 512KB';
            error_log('[Hightouch][' . $this->type . '] ' . $msg);

            return false;
        }

        // Send user agent in the form of {library_name}/{library_version} as per RFC 7231.
        $library = $messages[0]['context']['library'];
        $libName = $library['name'];
        $libVersion = $library['version'];
        $cmd .= " -H 'User-Agent: $libName/$libVersion'";

        if (!$this->debug()) {
            $cmd .= ' > /dev/null 2>&1 &';
        }

        exec($cmd, $output, $exit);

        if ($exit !== 0) {
            $this->handleError($exit, $output);
        }

        if ($tmpfname !== '') {
            unlink($tmpfname);
        }

        return $exit === 0;
    }
}
