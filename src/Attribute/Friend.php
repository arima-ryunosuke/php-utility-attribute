<?php
namespace ryunosuke\utility\attribute\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class Friend extends AbstractAttribute
{
    public function __construct(
        array $friends = ['*']
    ) {
    }
}
