<?php

declare(strict_types=1);

namespace Hightouch\Consumer;

// Used only in the isolated flush-result test.
function nextCollectorStatus(): int
{
    if (empty($GLOBALS['collectorStatuses'])) {
        throw new \UnexpectedValueException('Transport exceeded its expected attempts');
    }
    return array_shift($GLOBALS['collectorStatuses']);
}

function usleep($microseconds)
{
}

function pfsockopen($host, $port, &$errno, &$errstr, $timeout)
{
    return fopen('php://temp', 'w+');
}

function fread($socket, $length)
{
    return 'HTTP/1.1 ' . nextCollectorStatus() . " Test\r\n";
}

function curl_exec($handle)
{
    return '';
}

function curl_getinfo($handle, $option)
{
    return nextCollectorStatus();
}

function curl_close($handle)
{
}
