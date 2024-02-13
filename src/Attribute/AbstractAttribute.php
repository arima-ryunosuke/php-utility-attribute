<?php
namespace ryunosuke\utility\attribute\Attribute;

use Attribute;
use ryunosuke\utility\attribute\AttributeTrait\FactoryTrait;

#[Attribute(Attribute::TARGET_ALL)]
class AbstractAttribute
{
    use FactoryTrait;
}
