<?php
namespace ryunosuke\utility\attribute\ClassTrait;

use ryunosuke\utility\attribute\Attribute\DebugInfo;
use ryunosuke\utility\attribute\ReflectionAttribute;
use ryunosuke\utility\attribute\Utility\Reflection;

trait DebugInfoTrait
{
    public function __debugInfo(): array
    {
        $result = [];
        foreach (Reflection::mangleProperties($this) as $property) {
            $propattr  = DebugInfo::of($property['reflectionProperty']);
            $valueattr = DebugInfo::of($property['reflectionValue'], ReflectionAttribute::FOLLOW_INHERITANCE);

            $propattr_visible  = $propattr?->getNamedArgument('visible');
            $valueattr_visible = $valueattr?->getNamedArgument('visible');

            if (false
                || (!$propattr && !$valueattr)
                || ($propattr_visible)
                || (!$propattr && $valueattr_visible)
            ) {
                if (is_string($propattr_visible) && is_object($property['value'])) {
                    $result[$property['name']] = sprintf($propattr_visible, get_class($property['value']), spl_object_id($property['value']));
                }
                elseif (is_array($propattr_visible) && is_array($property['value'])) {
                    $result[$property['name']] = array_intersect_key(array_replace($property['value'], $propattr_visible), $property['value']);
                }
                else {
                    $result[$property['name']] = $property['value'];
                }
            }
        }
        return $result;
    }
}
