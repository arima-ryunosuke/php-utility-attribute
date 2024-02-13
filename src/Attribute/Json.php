<?php
namespace ryunosuke\utility\attribute\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class Json extends AbstractAttribute
{
    public function __construct(
        bool $jsonable = false
    ) {
    }
}
