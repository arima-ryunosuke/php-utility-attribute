<?php
namespace ryunosuke\utility\attribute\ClassTrait;

use ReflectionClass;
use ReflectionException;
use ryunosuke\utility\attribute\Attribute\Friend;
use ryunosuke\utility\attribute\Utility\Reflection;

trait FriendTrait
{
    private static function ___isFriend($reflector): bool
    {
        $friend = Friend::of($reflector);
        if ($friend === null) {
            return false;
        }

        $class   = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]['class'] ?? '-';
        $friends = $friend->getNamedArgument('friends');
        foreach ($friends as $pattern) {
            if (fnmatch($pattern, $class, FNM_NOESCAPE)) {
                return true;
            }
        }
        return false;
    }

    public function __isset(string $name): bool
    {
        foreach (Reflection::getAllProperties(new ReflectionClass(static::class)) as $property) {
            if ($property->name === $name) {
                assert(static::___isFriend($property));
                return true;
            }
        }
        return false;
    }

    public function __get(string $name)
    {
        foreach (Reflection::getAllProperties(new ReflectionClass(static::class)) as $property) {
            if ($property->name === $name) {
                assert(static::___isFriend($property));
                return $property->getValue($this);
            }
        }
        throw new ReflectionException("Undefined property: " . static::class . "::$$name");
    }

    public function __call(string $name, array $arguments)
    {
        foreach (Reflection::getAllMethods(new ReflectionClass(static::class)) as $method) {
            if ($method->name === $name) {
                assert(static::___isFriend($method));
                return $method->invokeArgs($this, $arguments);
            }
        }
        throw new ReflectionException("Call to undefined method " . static::class . "::$name()");
    }

    public static function __callStatic(string $name, array $arguments)
    {
        foreach (Reflection::getAllMethods(new ReflectionClass(static::class)) as $method) {
            if ($method->name === $name) {
                assert(static::___isFriend($method));
                return $method->invokeArgs(null, $arguments);
            }
        }
        throw new ReflectionException("Call to undefined method " . static::class . "::$name()");
    }

    public static function annotate(): void
    {
        $refclass = new ReflectionClass(static::class);

        $addSpace = fn(?string $string, string $space = ' ') => $string === null ? '' : "$string$space";

        $magicals = [];

        foreach (Reflection::getAllConstants($refclass) as $constant) {
            if (Friend::of($constant)) {
                $classname              = $constant->getDeclaringClass()->name;
                $type                   = $addSpace(Reflection::stringifyType(method_exists($constant, 'getType') ? $constant->getType() : null, true));
                $magicals[$classname][] = "@const {$type}{$constant->getName()}";
            }
        }

        foreach (Reflection::getAllProperties($refclass) as $property) {
            if (Friend::of($property)) {
                $classname              = $property->getDeclaringClass()->name;
                $type                   = $addSpace(Reflection::stringifyType($property->getType(), true));
                $magicals[$classname][] = "@property {$type}\${$property->getName()}";
            }
        }

        foreach (Reflection::getAllMethods($refclass) as $method) {
            if (Friend::of($method)) {
                $classname              = $method->getDeclaringClass()->name;
                $type                   = $addSpace(Reflection::stringifyType($method->getReturnType(), true) ?? 'void');
                $static                 = $method->isStatic() ? "static " : "";
                $arguments              = Reflection::stringifyParameters($method);
                $magicals[$classname][] = "@method {$static}{$type}{$method->getName()}($arguments)";
            }
        }

        foreach ($magicals as $class => $magical) {
            Reflection::rewriteDocComment(new ReflectionClass($class), 'Friend', $magical);
        }
    }
}
