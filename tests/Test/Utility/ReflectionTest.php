<?php
namespace ryunosuke\Test\Utility;

use AncestorClass;
use ArrayObject;
use ChildClass;
use Closure;
use Dummy;
use ParentClass;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionObject;
use ReflectionParameter;
use ReflectionProperty;
use ryunosuke\utility\attribute\Utility\Reflection;

class ReflectionTest extends \ryunosuke\Test\AbstractTestCase
{
    function test_by()
    {
        $child = new ChildClass();

        that(Reflection::by(ChildClass::class))->isInstanceOf(ReflectionClass::class);
        that(Reflection::by(ChildClass::class . '::const'))->isInstanceOf(ReflectionClassConstant::class);
        that(Reflection::by(ChildClass::class . '::$property'))->isInstanceOf(ReflectionProperty::class);
        that(Reflection::by(ChildClass::class . '::method'))->isInstanceOf(ReflectionMethod::class);
        that(Reflection::by([ChildClass::class, 'method', 0]))->isInstanceOf(ReflectionParameter::class);

        that(Reflection::by([$child, 'method']))->isInstanceOf(ReflectionMethod::class);
        that(Reflection::by(Closure::fromCallable([$child, 'child'])))->isInstanceOf(ReflectionMethod::class);
        that(Reflection::by(fn(...$args) => $child->child(...$args)))->isInstanceOf(ReflectionFunction::class);

        that(Reflection::by(new ChildClass()))->isInstanceOf(ReflectionObject::class);
        that(Reflection::by('trim'))->isInstanceOf(ReflectionFunction::class);
        that(Reflection::by(Closure::fromCallable('trim')))->isInstanceOf(ReflectionFunction::class);
        that(Reflection::by(fn(...$args) => trim(...$args)))->isInstanceOf(ReflectionFunction::class);

        that(Reflection::class)::by('undefined-string')->wasThrown('does not exist');
        that(Reflection::class)::by([ChildClass::class, 'undefined'])->wasThrown('does not exist');
    }

    function test_getClass()
    {
        $child    = new ChildClass();
        $refclass = new ReflectionClass($child);

        that(Reflection::getClass($refclass))->isNull();
        that(Reflection::getClass($refclass->getReflectionConstant('const')))->getName()->is(ChildClass::class);
        that(Reflection::getClass($refclass->getProperty('property')))->getName()->is(ChildClass::class);
        that(Reflection::getClass($refclass->getMethod('method')))->getName()->is(ChildClass::class);
        that(Reflection::getClass($refclass->getMethod('method')->getParameters()[0]))->getName()->is(ChildClass::class);
    }

    function test_getParent()
    {
        $child    = new ChildClass();
        $refclass = new ReflectionClass($child);

        $parent = Reflection::getParent($refclass);
        that($parent)->getName()->is(Dummy::class);
        $parent = Reflection::getParent($parent);
        that($parent)->getName()->is(ParentClass::class);
        $parent = Reflection::getParent($parent);
        that($parent)->getName()->is(AncestorClass::class);
        $parent = Reflection::getParent($parent);
        that($parent)->is(null);

        $parent = Reflection::getParent($refclass->getReflectionConstant('const'));
        that($parent)->getValue()->is(2);
        $parent = Reflection::getParent($parent);
        that($parent)->getValue()->is(1);
        $parent = Reflection::getParent($parent);
        that($parent)->is(null);

        $parent = Reflection::getParent($refclass->getProperty('property'));
        that($parent)->getValue($child)->is(2);
        $parent = Reflection::getParent($parent);
        that($parent)->getValue($child)->is(1);
        $parent = Reflection::getParent($parent);
        that($parent)->is(null);

        $parent = Reflection::getParent($refclass->getMethod('method'));
        that($parent)->getReturnType()->getName()->is(ParentClass::class);
        $parent = Reflection::getParent($parent);
        that($parent)->getReturnType()->getName()->is(AncestorClass::class);
        $parent = Reflection::getParent($parent);
        that($parent)->is(null);

        $parent = Reflection::getParent($refclass->getMethod('method')->getParameters()[0]);
        that($parent)->getDefaultValue()->is(2);
        $parent = Reflection::getParent($parent);
        that($parent)->getDefaultValue()->is(1);
        $parent = Reflection::getParent($parent);
        that($parent)->is(null);
    }

    function test_getAllMember()
    {
        $child    = new ChildClass();
        $refclass = new ReflectionClass($child);

        that(array_keys(iterator_to_array(Reflection::getAllConstants($refclass))))->is([
            "ChildClass::const",
            "ChildClass::child",
            "ParentClass::const",
            "AncestorClass::const",
        ]);
        that(array_keys(iterator_to_array(Reflection::getAllProperties($refclass))))->is([
            "ChildClass::property",
            "ChildClass::child",
            "ParentClass::property",
            "AncestorClass::property",
        ]);
        that(array_keys(iterator_to_array(Reflection::getAllMethods($refclass))))->is([
            "ChildClass::method",
            "ChildClass::child",
            "ParentClass::method",
            "AncestorClass::method",
        ]);
    }

    function test_mangleProperties()
    {
        $object                = new class extends ChildClass {
            private int $uninitialized;
            private int $anonymous = 123;
        };
        $object->dynamicField1 = new class ( ) { };
        $object->dynamicField2 = new class ( ) { };

        $properties = Reflection::mangleProperties($object);
        $property   = array_shift($properties);
        that($property)->subsetEquals([
            "reflectionValue" => null,
            "name"            => "\0AncestorClass\0property",
            "class"           => "AncestorClass",
            "field"           => "property",
            "value"           => 1,
        ]);
        $property = array_shift($properties);
        that($property)->subsetEquals([
            "reflectionValue" => null,
            "name"            => "\0ParentClass\0property",
            "class"           => "ParentClass",
            "field"           => "property",
            "value"           => 2,
        ]);
        $property = array_shift($properties);
        that($property)->subsetEquals([
            "reflectionValue" => null,
            "name"            => "\0ChildClass\0property",
            "class"           => "ChildClass",
            "field"           => "property",
            "value"           => 3,
        ]);
        $property = array_shift($properties);
        that($property)->subsetEquals([
            "reflectionValue" => null,
            "name"            => "\0ChildClass\0child",
            "class"           => "ChildClass",
            "field"           => "child",
            "value"           => 4,
        ]);
        $property = array_shift($properties);
        that($property)->subsetEquals([
            "reflectionValue" => null,
            "class"           => get_class($object),
            "field"           => "anonymous",
            "value"           => 123,
        ]);
        that($property["name"])->contains(get_class($object));
        $property = array_shift($properties);
        that($property)->subsetEquals([
            "name"  => "dynamicField1",
            "class" => get_class($object),
            "field" => "dynamicField1",
            "value" => $object->dynamicField1,
        ]);
        that($property["reflectionValue"])->isInstanceOf(ReflectionObject::class);
        $property = array_shift($properties);
        that($property)->subsetEquals([
            "name"  => "dynamicField2",
            "class" => get_class($object),
            "field" => "dynamicField2",
            "value" => $object->dynamicField2,
        ]);
        that($property["reflectionValue"])->isInstanceOf(ReflectionObject::class);
    }

    function test_stringifyType()
    {
        $type = (new ReflectionFunction(fn(): ?ArrayObject => null))->getReturnType();
        that(Reflection::stringifyType(null, false))->is(null);
        that(Reflection::stringifyType(null, true))->is(null);
        that(Reflection::stringifyType($type, false))->is('?ArrayObject');
        that(Reflection::stringifyType($type, true))->is('?\\ArrayObject');
    }

    function test_stringifyParameters()
    {
        $func = (new ReflectionFunction(fn(string $a, int &$b = 0, $c = SORT_ASC, ...$z) => null));
        that(Reflection::stringifyParameters($func))->is('string $a, int &$b = 0, $c = SORT_ASC, ...$z');

        $func = (new ReflectionFunction(fn(string $a, int &$b = 0, $c = \SORT_ASC, ...$z) => null));
        that(Reflection::stringifyParameters($func))->is('string $a, int &$b = 0, $c = \\SORT_ASC, ...$z');

        $func = (new ReflectionFunction(fn(string $a, int &$b = 0, $c = null, ...$z) => null));
        that(Reflection::stringifyParameters($func))->is('string $a, int &$b = 0, $c = null, ...$z');
    }

    function test_rewriteDocComment()
    {
        that(Reflection::rewriteDocComment(new ReflectionClass($this), 'Dummy', []))->is(null);
    }
}
