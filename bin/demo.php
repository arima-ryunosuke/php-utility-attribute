#!/usr/bin/env php81
<?php

require_once __DIR__ . '/../vendor/autoload.php';

(function ($filename, $hook_functions = ['var_dump']) {
    $filename       = realpath($filename);
    $hook_functions = array_flip($hook_functions);

    $outputs = [];
    ob_start(function ($buffer, $phase) use (&$outputs, $filename, $hook_functions) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
        if (($trace['file'] ?? '') === $filename && isset($hook_functions[$trace['function'] ?? ''])) {
            $outputs[$trace['line']] ??= '';
            $outputs[$trace['line']] .= $buffer;
        }
        return null;
    }, 1);

    include $filename;

    ob_end_clean();

    $content  = file_get_contents($filename);
    $contents = preg_split('#\\R#u', $content);

    foreach (token_get_all($content) as $token) {
        if (is_array($token) && $token[0] === T_COMMENT && strpos($token[1], "/*= ") === 0) {
            $lines = preg_split('#\\R#u', $token[1]);
            array_splice($contents, $token[2] - 1, count($lines), array_pad([], count($lines), null));
        }
    }

    $addition = 0;
    foreach ($outputs as $line => $buffer) {
        $stmt   = $contents[$line + $addition - 1];
        $indent = str_repeat(' ', strspn($stmt, ' '));

        $lines = preg_split('#\\R#u', "/*= " . trim($buffer) . " */");
        $lines = array_map(fn($v) => "$indent$v", $lines);

        array_splice($contents, $line + $addition, 0, $lines);
        $addition += count($lines);
    }

    $newcontent = implode("\n", array_filter($contents, fn($v) => $v !== null));
    if ($content !== $newcontent) {
        file_put_contents($filename, $newcontent);
    }
})(__DIR__ . '/../README.md');
