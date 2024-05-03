<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/ryunosuke/phpunit-extension/inc/bootstrap.php';

\ryunosuke\PHPUnit\Actual::generateStub(__DIR__ . '/../src', __DIR__ . '/.stub');

assert_options(ASSERT_ACTIVE, true);
assert_options(ASSERT_EXCEPTION, false);
assert_options(ASSERT_WARNING, true);
