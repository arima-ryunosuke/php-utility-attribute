<?php
namespace ryunosuke\utility\attribute\Attribute;

use Attribute;

/**
 * $visible:
 * - true: show
 * - false: hide
 * - array: replace for array
 * - string: format for object
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class DebugInfo extends AbstractAttribute
{
    public function __construct(
        bool|string|array $visible = false
    ) {
    }
}
