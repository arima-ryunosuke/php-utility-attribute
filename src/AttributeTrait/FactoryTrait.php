<?php
namespace ryunosuke\utility\attribute\AttributeTrait;

use ryunosuke\utility\attribute\ReflectionAttribute;
use ryunosuke\utility\attribute\Utility\Reflection;

/**
 * get ReflectionAttribute by self and single version
 *
 * - e.g. HogeAttribute::arrayOf(new ReflectionClass('Fuga'))
 *   - same as (new ReflectionClass('Fuga'))->getAttributes(HogeAttribute::class)
 * - e.g. HogeAttribute::arrayOf($fuga)
 *   - same as (new ReflectionObject($fuga))->getAttributes(HogeAttribute::class)
 * - e.g. HogeAttribute::arrayOf($fuga->method(...))
 *   - same as (new ReflectionMethod($fuga, 'method'))->getAttributes(HogeAttribute::class)
 * - e.g. HogeAttribute::of(new ReflectionClass('Fuga'))
 *   - same as (new ReflectionClass('Fuga'))->getAttributes(HogeAttribute::class)[0] ?? null
 * - e.g. HogeAttribute::of($fuga)
 *   - same as (new ReflectionObject($fuga))->getAttributes(HogeAttribute::class)[0] ?? null
 * - e.g. HogeAttribute::of($fuga->method(...))
 *   - same as (new ReflectionMethod($fuga, 'method'))->getAttributes(HogeAttribute::class)[0] ?? null
 */
trait FactoryTrait
{
    /** @return ReflectionAttribute[] */
    public static function arrayOf($reflector, int $flags = 0): array
    {
        if ($reflector === null) {
            return [];
        }
        return ReflectionAttribute::factory(Reflection::by($reflector), static::class, $flags);
    }

    public static function of($reflector, int $flags = 0): ?ReflectionAttribute
    {
        $attrs = static::arrayOf($reflector, $flags);
        assert(count($attrs) <= 1);
        return $attrs[0] ?? null;
    }
}
