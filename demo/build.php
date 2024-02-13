#!/usr/bin/env php81
<?php
namespace demo;

require_once __DIR__ . '/../vendor/autoload.php';

const DEMOFILE   = __DIR__ . '/sample.php';
const READMEFILE = __DIR__ . '/../README.md';

$outputs = [];

function var_dump(...$args)
{
    global $outputs;

    $line = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['line'];

    ob_start();
    \var_dump(...$args);
    $outputs[$line] = rtrim(ob_get_clean());
}

include DEMOFILE;

$lines = file(DEMOFILE, FILE_IGNORE_NEW_LINES);
foreach ($outputs as $line => $output) {
    $lines[$line - 1] .= "/* $output */";
}
$demo = rtrim(implode("\n", $lines));

$readme = file_get_contents(READMEFILE);
$readme = preg_replace_callback('#^```php.*?^```#usm', fn() => "```php\n$demo\n```", $readme);
file_put_contents(READMEFILE, $readme);
