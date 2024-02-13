<?php
namespace ryunosuke\Test\UseCase;

use ryunosuke\utility\attribute\Attribute\DebugInfo;
use ryunosuke\utility\attribute\ClassTrait\DebugInfoTrait;

class DebugInfoTest extends \ryunosuke\Test\AbstractTestCase
{
    function test()
    {
        $concrete  = new class extends ConcreteDebugInfo {
            private $anonymousField;
        };
        $debugInfo = print_r($concrete, true);

        that($debugInfo)->contains('anonymousField:');
        that($debugInfo)->contains('visibleField:' . AbstractDebugInfo::class . ':private');
        that($debugInfo)->contains('visibleField:' . ConcreteDebugInfo::class . ':private');
        that($debugInfo)->notContains('invisibleField');
        that($debugInfo)->notContains('publicSelf');
    }
}

#[DebugInfo(false)]
abstract class AbstractDebugInfo
{
    use DebugInfoTrait;

    #[DebugInfo(false)]
    private $invisibleField;

    #[DebugInfo(true)]
    private $visibleField;
}

class ConcreteDebugInfo extends AbstractDebugInfo
{
    #[DebugInfo(false)]
    private $invisibleField;

    #[DebugInfo(true)]
    private $visibleField;

    public $publicSelf;

    public function __construct()
    {
        $this->publicSelf = $this;
    }
}
