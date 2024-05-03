<?php
namespace ryunosuke\utility\attribute\ClassTrait;

use ryunosuke\utility\attribute\Attribute\Json;
use ryunosuke\utility\attribute\ReflectionAttribute;
use ryunosuke\utility\attribute\Utility\Reflection;

trait JsonTrait
{
    public function jsonSerialize(): mixed
    {
        $result = [];
        foreach (Reflection::mangleProperties($this) as $property) {
            $propattr  = Json::of($property['reflectionProperty']);
            $valueattr = Json::of($property['reflectionValue'], ReflectionAttribute::FOLLOW_INHERITANCE);
            if (false
                || (!$propattr && !$valueattr && $property['reflectionProperty']->isPublic())
                || ($propattr && $propattr->getNamedArgument('jsonable'))
                || (!$propattr && $valueattr && $valueattr->getNamedArgument('jsonable'))
            ) {
                $result[$property['field']] = $property['value'];
            }
        }
        return $result;
    }
}
