<?php
namespace ryunosuke\utility\attribute;

use Attribute;
use ReflectionAttribute as BaseReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;
use ryunosuke\utility\attribute\Utility\Reflection;

/**
 * extends native ReflectionAttribute
 *
 * - add getReflection()
 * - add getNamedArguments()
 * - add getNamedArgument()
 *
 * @template T of Reflector|ReflectionClass|ReflectionClassConstant|ReflectionProperty|ReflectionMethod|ReflectionParameter
 */
class ReflectionAttribute extends BaseReflectionAttribute
{
    public const FOLLOW_INHERITANCE = 1 << 16;
    public const SEE_ALSO_CLASS     = 1 << 17;
    public const MERGE_REPEATABLE   = 1 << 18;
    public const ALL                = -1;

    private BaseReflectionAttribute $original;
    private Reflector               $reflection;

    private array $namedArguments;

    /** @return static[] */
    public static function factory(Reflector $reflector, ?string $name = null, int $flags = 0): array
    {
        $polyfill = function ($reflector, $name, $flags) {
            $flags &= static::IS_INSTANCEOF;
            return array_map(fn($refattr) => new static($refattr, $reflector), $reflector->getAttributes($name, $flags));
        };

        $attrs = $polyfill($reflector, $name, $flags);

        if (($flags & static::SEE_ALSO_CLASS) && $parent = Reflection::getClass($reflector)) {
            if ($flags & static::MERGE_REPEATABLE) {
                $attrs = array_merge($attrs, $polyfill($parent, $name, $flags));
            }
            elseif (!$attrs) {
                $attrs = $polyfill($parent, $name, $flags);
            }
        }

        if (($flags & static::FOLLOW_INHERITANCE) && $parent = Reflection::getParent($reflector)) {
            if ($flags & static::MERGE_REPEATABLE) {
                $attrs = array_merge($attrs, static::factory($parent, $name, $flags));
            }
            elseif (!$attrs) {
                $attrs = static::factory($parent, $name, $flags);
            }
        }

        static $metadata = [];
        if ($flags & static::MERGE_REPEATABLE) {
            $counts = [];
            foreach ($attrs as $n => $attr) {
                $attrname = $attr->getName();

                $metadata[$attrname]['repeatable'] ??= (function () use ($polyfill, $attrname) {
                    if (!class_exists($attrname)) {
                        return false;
                    }
                    $attributeReflection = $polyfill(new ReflectionClass($attrname), Attribute::class, ReflectionAttribute::IS_INSTANCEOF)[0];
                    return (current($attributeReflection->getArguments())) & Attribute::IS_REPEATABLE;
                })();

                $counts[$attrname] = ($counts[$attrname] ?? 0) + 1;
                if (!$metadata[$attrname]['repeatable'] && $counts[$attrname] > 1) {
                    unset($attrs[$n]);
                }
            }
            $attrs = array_values($attrs);
        }

        return $attrs;
    }

    /** @noinspection PhpMissingParentConstructorInspection */
    private function __construct(BaseReflectionAttribute $original, Reflector $reflection)
    {
        $this->original   = $original;
        $this->reflection = $reflection;
    }

    public function __debugInfo(): array
    {
        return [$this->getName() => $this->getNamedArguments()];
    }

    /** @return T */
    public function getReflection(): Reflector
    {
        return $this->reflection;
    }

    public function getNamedArguments(): array
    {
        return $this->namedArguments ??= (function () {
            $attrname    = $this->original->getName();
            $arguments   = $this->original->getArguments();
            $constructor = (new ReflectionClass($attrname))->getConstructor();

            if (!$arguments && !$constructor) {
                return $arguments;
            }

            if ($arguments && !$constructor) {
                throw new ReflectionException("[$attrname] Argument is specified but does not have constructor");
            }

            $result = [
                'argument' => [],
                'variadic' => [],
            ];
            foreach ($constructor->getParameters() as $parameter) {
                $name = $parameter->getName();
                if ($parameter->isVariadic()) {
                    $result['variadic'][$name] = array_merge($arguments);
                }
                elseif (array_key_exists($key = $parameter->getName(), $arguments)) {
                    $result['argument'][$name] = $arguments[$key];
                    unset($arguments[$key]);
                }
                elseif (array_key_exists($key = $parameter->getPosition(), $arguments)) {
                    $result['argument'][$name] = $arguments[$key];
                    unset($arguments[$key]);
                }
                elseif ($parameter->isDefaultValueAvailable()) {
                    $result['argument'][$name] = $parameter->getDefaultValue();
                }
                else {
                    throw new ReflectionException("[$attrname] Parameter {$parameter->getName()} does not have default value");
                }
            }
            assert(new $attrname(...$result['argument'], ...($result['variadic'] ? reset($result['variadic']) : [])));
            return $result['argument'] + $result['variadic'];
        })();
    }

    public function getNamedArgument(string $name): mixed
    {
        $namedArguments = $this->getNamedArguments();
        if (!array_key_exists($name, $namedArguments)) {
            throw new ReflectionException("[{$this->original->getName()}] Parameter $name does not exist");
        }
        return $namedArguments[$name];
    }

    // <editor-fold desc="boilerplate">

    public function getName(): string
    {
        return $this->original->getName();
    }

    public function getArguments(): array
    {
        return $this->original->getArguments();
    }

    public function isRepeated(): bool
    {
        return $this->original->isRepeated();
    }

    public function getTarget(): int
    {
        return $this->original->getTarget();
    }

    public function newInstance(): object
    {
        return $this->original->newInstance();
    }

    // </editor-fold>
}
