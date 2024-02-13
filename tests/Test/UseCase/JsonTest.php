<?php
namespace ryunosuke\Test\UseCase;

use JsonSerializable;
use ryunosuke\utility\attribute\Attribute\Json;
use ryunosuke\utility\attribute\ClassTrait\JsonTrait;

class JsonTest extends \ryunosuke\Test\AbstractTestCase
{
    function test()
    {
        $concrete = new class extends ConcreteJson {
            private $anonymousField;
        };
        $array    = json_decode(json_encode($concrete), true);

        that($array)->hasKey('visibleField');
        that($array)->notHasKey('invisibleField');
        that($array)->notHasKey('invisiblePublicField');
        that($array)->notHasKey('publicSelf');
        that($array)->notHasKey('anonymousField');
    }
}

#[Json(false)]
abstract class AbstractJson implements JsonSerializable
{
    use JsonTrait;

    #[Json(false)]
    private $invisibleField;

    #[Json(true)]
    private $visibleField;
}

class ConcreteJson extends AbstractJson
{
    #[Json(false)]
    private $invisibleField;

    #[Json(true)]
    private $visibleField;

    #[Json(false)]
    public $invisiblePublicField;

    public $publicSelf;

    public function __construct()
    {
        $this->publicSelf = $this;
    }
}
