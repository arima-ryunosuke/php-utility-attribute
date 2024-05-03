<?php
namespace ryunosuke\utility\attribute\Utility;

use Closure;
use Generator;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionObject;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use Reflector;

/**
 * @template T of Reflector|ReflectionClass|ReflectionClassConstant|ReflectionProperty|ReflectionMethod|ReflectionParameter
 */
abstract class Reflection
{
    /** @return T */
    public static function by($fqsen): Reflector
    {
        if ($fqsen instanceof Reflector) {
            return $fqsen;
        }

        if (is_string($fqsen) && strpos($fqsen, '::') !== false) {
            $fqsen = explode('::', $fqsen);
        }

        if ($fqsen instanceof Closure) {
            $ref = new ReflectionFunction($fqsen);
            if (strpos($ref->name, '{closure}') === false) { // isAnonymous
                if ($ref->getClosureScopeClass()) {
                    return $ref->getClosureScopeClass()->getMethod($ref->name);
                }
            }
            return $ref;
        }
        if (is_object($fqsen)) {
            return new ReflectionObject($fqsen);
        }
        if (is_array($fqsen)) {
            [$class, $member] = $fqsen + [1 => ''];
            $ref = static::by($class);
            if (($member[0] ?? '') === '$' && $ref->hasProperty($property = substr($member, 1))) {
                return new ReflectionProperty($class, $property);
            }
            if ($ref->hasConstant($member)) {
                return new ReflectionClassConstant($class, $member);
            }
            if ($ref->hasMethod($member)) {
                if (isset($fqsen[2])) {
                    return new ReflectionParameter([$class, $member], $fqsen[2]);
                }
                return new ReflectionMethod($class, $member);
            }
            throw new ReflectionException(sprintf('Reflection %s does not exist', json_encode($fqsen)));
        }
        if ((class_exists($fqsen) || trait_exists($fqsen) || interface_exists($fqsen))) {
            return new ReflectionClass($fqsen);
        }
        if (function_exists($fqsen)) {
            return new ReflectionFunction($fqsen);
        }
        throw new ReflectionException(sprintf('Reflection %s does not exist', json_encode($fqsen)));
    }

    /**
     * @param T $reflector
     * @return Generator<ReflectionClass>
     */
    public static function getParents(Reflector $reflector, bool $self): Generator
    {
        $class = $reflector instanceof ReflectionClass ? $reflector : $reflector->getDeclaringClass();
        if ($self) {
            yield $class;
        }
        while ($parent = $class->getParentClass()) {
            yield $parent;
            $class = $parent;
        }
    }

    /** @param T $reflector */
    public static function getClass(Reflector $reflector): ?ReflectionClass
    {
        return $reflector instanceof ReflectionClass ? null : $reflector->getDeclaringClass();
    }

    /**
     * @param T $reflector
     * @return ?T
     */
    public static function getParent(Reflector $reflector): ?Reflector
    {
        if ($reflector instanceof ReflectionClass) {
            foreach (static::getParents($reflector, false) as $parent) {
                return $parent;
            }
        }
        if ($reflector instanceof ReflectionClassConstant) {
            foreach (static::getParents($reflector, false) as $parent) {
                if ($parent->hasConstant($reflector->name)) {
                    return static::setAccessible($parent->getReflectionConstant($reflector->name));
                }
            }
        }
        if ($reflector instanceof ReflectionProperty) {
            foreach (static::getParents($reflector, false) as $parent) {
                if ($parent->hasProperty($reflector->name)) {
                    return static::setAccessible($parent->getProperty($reflector->name));
                }
            }
        }
        if ($reflector instanceof ReflectionMethod) {
            foreach (static::getParents($reflector, false) as $parent) {
                if ($parent->hasMethod($reflector->name)) {
                    return static::setAccessible($parent->getMethod($reflector->name));
                }
            }
        }
        if ($reflector instanceof ReflectionParameter) {
            /** @var ReflectionMethod $method */
            $method = $reflector->getDeclaringFunction();
            foreach (static::getParents($reflector, false) as $parent) {
                if ($parent->hasMethod($method->name)) {
                    $params = $parent->getMethod($method->name)->getParameters();
                    if ($params[$reflector->getPosition()]) {
                        return $params[$reflector->getPosition()];
                    }
                }
            }
        }
        return null;
    }

    /** @return Generator<T> */
    private static function getAllMember(ReflectionClass $reflector, string $memberMethod): Generator
    {
        $founds = [];
        foreach (static::getParents($reflector, true) as $parent) {
            foreach ($parent->$memberMethod() as $member) {
                $id = $member->class . '::' . $member->name;
                if (isset($founds[$id])) {
                    continue;
                }
                $founds[$id] = true;
                yield $id => static::setAccessible($member);
            }
        }
    }

    /** @return Generator<ReflectionClassConstant> */
    public static function getAllConstants(ReflectionClass $reflector): Generator
    {
        return static::getAllMember($reflector, 'getReflectionConstants');
    }

    /** @return Generator<ReflectionProperty> */
    public static function getAllProperties(ReflectionClass $reflector): Generator
    {
        return static::getAllMember($reflector, 'getProperties');
    }

    /** @return Generator<ReflectionMethod> */
    public static function getAllMethods(ReflectionClass $reflector): Generator
    {
        return static::getAllMember($reflector, 'getMethods');
    }

    /**
     * @param T $reflector
     * @return T
     */
    public static function setAccessible(Reflector $reflector)
    {
        if (method_exists($reflector, 'setAccessible')) {
            $reflector->setAccessible(true);
        }
        return $reflector;
    }

    /** @return array<array{reflectionValue: ?ReflectionObject, reflectionProperty: ReflectionProperty, name: string, class: string, field: string, value: mixed}> */
    public static function mangleProperties(object $object): array
    {
        $result = [];
        foreach (get_mangled_object_vars($object) as $name => $value) {
            $parts = explode("\0", $name);
            if (isset($parts[3])) {
                $class = $parts[1] . "\0" . $parts[2];
                $field = $parts[3];
            }
            elseif (isset($parts[2])) {
                $class = $parts[1];
                $field = $parts[2];
            }
            else {
                $class = '';
                $field = $parts[0];
            }

            $refprop       = new ReflectionProperty(class_exists($class) ? $class : $object, $field);
            $result[$name] = [
                'reflectionValue'    => is_object($value) ? new ReflectionObject($value) : null,
                'reflectionProperty' => $refprop,
                'name'               => $name,
                'class'              => $refprop->class,
                'field'              => $field,
                'value'              => $value,
            ];
        }

        // for compatible php8.1. see https://www.php.net/manual/migration81.other-changes.php#migration81.other-changes.functions.core
        if (version_compare(PHP_VERSION, 8.1) < 0) {
            $classes    = [get_class($object) => $object] + class_parents($object);
            $classOrder = array_flip(array_reverse(array_keys($classes)));
            $fieldOrder = [];
            foreach ($classes as $class => $object) {
                $fieldOrder[$class] = array_flip(array_column(static::by($object)->getProperties(), 'name'));
            }
            uasort($result, function ($a, $b) use ($classOrder, $fieldOrder) {
                return $classOrder[$a['class']] <=> $classOrder[$b['class']] ?: $fieldOrder[$a['class']][$a['field']] <=> $fieldOrder[$b['class']][$b['field']];
            });
        }

        return $result;
    }

    public static function stringifyType(?ReflectionType $type, bool $fullyQualifiedClass): ?string
    {
        if ($type === null) {
            return null;
        }

        $types = preg_split('#([' . preg_quote('?|&()') . '])#', @strval($type), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($fullyQualifiedClass) {
            $types = array_map(fn($type) => class_exists($type) || interface_exists($type) ? "\\$type" : $type, $types);
        }

        return implode('', $types);
    }

    public static function stringifyParameters(ReflectionFunctionAbstract $reflector): string
    {
        $addSpace = fn(?string $string, string $space = ' ') => $string === null ? '' : "$string$space";

        $result = [];
        foreach ($reflector->getParameters() as $parameter) {
            $arg = '';
            $arg .= $addSpace(static::stringifyType($parameter->getType(), true));
            $arg .= $parameter->isPassedByReference() ? "&" : "";
            $arg .= $parameter->isVariadic() ? "..." : "";
            $arg .= '$' . $parameter->getName();

            if ($parameter->isDefaultValueAvailable()) {
                if ($parameter->isDefaultValueConstant()) {
                    // isDefaultValueConstant returns FQSEN(Fully Qualified Structural Element Name)
                    if (defined($parameter->getDefaultValueConstantName())) {
                        $arg .= " = " . '\\' . $parameter->getDefaultValueConstantName();
                    }
                    else {
                        $arg .= " = " . array_slice(explode("\\", $parameter->getDefaultValueConstantName()), -1, 1)[0];
                    }
                }
                else {
                    if ($parameter->getDefaultValue() === null) {
                        $arg .= " = null";
                    }
                    else {
                        $arg .= " = " . var_export($parameter->getDefaultValue(), true);
                    }
                }
            }

            $result[] = $arg;
        }
        return implode(', ', $result);
    }

    public static function rewriteDocComment(ReflectionClass $reflector, string $tagname, array $annotations): ?string
    {
        if (!$annotations) {
            return null;
        }

        // rewrite source but already loaded source getStartLine is unchanged
        $file       = File::factory($reflector->getFileName(), 10);
        $source     = $file->getContents();
        $startLine  = $file->getLinePosition($reflector->getStartLine());
        $doccomment = (string) $reflector->getDocComment();

        $beginTag = "@auto-document-$tagname:begin";
        $endTag   = "@auto-document-$tagname:end";

        if (!strlen($doccomment)) {
            $indent = str_repeat(' ', strspn($file->getLine($reflector->getStartLine()), ' '));
            $offset = $startLine;
            $length = 0;

            $doccomment = <<<DOC
            $indent/**
            $indent * $beginTag
            $indent * $endTag
            $indent */
            
            DOC;
        }
        else {
            preg_match('#( *) \*/$#um', $doccomment, $m);
            $indent = str_repeat(' ', strlen($m[1]));
            $offset = strrpos($source, $doccomment, $startLine - strlen($source));
            $length = strlen($doccomment);

            if (strpos($doccomment, $beginTag) === false) {
                $lastpos    = strrpos($doccomment, "\n$indent */");
                $doccomment = substr_replace($doccomment, <<<DOC
                
                $indent * $beginTag
                $indent * $endTag
                DOC, $lastpos, 0);
            }
        }

        $annotation = "\n$indent * " . implode("\n$indent * ", $annotations) . "\n$indent * ";
        $doccomment = preg_replace("#(" . preg_quote($beginTag) . ").*?(" . preg_quote($endTag) . ")#usm", "$1$annotation$2", $doccomment);

        return $file->rewrite($doccomment, $offset, $length);
    }
}
