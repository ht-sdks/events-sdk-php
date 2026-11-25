<?php

namespace Hightouch\Consumer;

// Loaded only in the isolated socket transport test.
function pfsockopen($host, $port, &$errno, &$errstr, $timeout)
{
    return fopen('php://temp', 'w+');
}

function fread($socket, $length)
{
    return array_shift($GLOBALS['socketReplies']);
}
