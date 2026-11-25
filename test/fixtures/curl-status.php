<?php

namespace Hightouch\Consumer;

// Loaded only in the isolated LibCurl transport test.
function curl_exec($handle)
{
    return '';
}

function curl_getinfo($handle, $option)
{
    return array_shift($GLOBALS['curlStatuses']);
}
