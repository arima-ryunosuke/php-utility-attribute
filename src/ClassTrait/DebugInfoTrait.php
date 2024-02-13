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
            if (false
                || (!$propattr && !$valueattr)
                || ($propattr && $propattr->getNamedArgument('visible'))
                || (!$propattr && $valueattr && $valueattr->getNamedArgument('visible'))
            ) {
                $result[$property['name']] = $property['value'];
            }
        }
        return $result;
    }
}
