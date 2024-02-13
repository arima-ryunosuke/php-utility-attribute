<?php
namespace ryunosuke\Test;

use PHPUnit\Framework\TestCase;
use ryunosuke\PHPUnit\TestCaseTrait;

abstract class AbstractTestCase extends TestCase
{
    use TestCaseTrait;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        require_once __DIR__ . '/files/inheritance-tree.php';
    }
}
