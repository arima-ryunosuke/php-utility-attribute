<?php
namespace ryunosuke\Test;

use AllAttribute;
use AncestorAttribute;
use Attribute;
use ChildClass;
use Dummy;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ryunosuke\utility\attribute\Attribute\AbstractAttribute;
use ryunosuke\utility\attribute\ReflectionAttribute;
use SingleAttribute;

class ReflectionAttributeTest extends \ryunosuke\Test\AbstractTestCase
{
    function test_const()
    {
        # Ensure that built-in and custom values do not overlap
        $all = ReflectionAttribute::IS_INSTANCEOF | ReflectionAttribute::FOLLOW_INHERITANCE | ReflectionAttribute::SEE_ALSO_CLASS;
        that($all)->is(ReflectionAttribute::IS_INSTANCEOF + ReflectionAttribute::FOLLOW_INHERITANCE + ReflectionAttribute::SEE_ALSO_CLASS);
    }

    function test___debugInfo()
    {
        $attr      = ConcreteAttribute::of(ConcreteClass::class);
        $debugInfo = print_r($attr, true);

        that($debugInfo)->contains('ConcreteAttribute');
    }

    function test_polyfill()
    {
        $refclass = new ReflectionClass(Dummy::class);
        that(ReflectionAttribute::factory($refclass))->count(1);
        that(ReflectionAttribute::factory($refclass, \AbstractAttribute::class))->count(0);
        that(ReflectionAttribute::factory($refclass, \AbstractAttribute::class, ReflectionAttribute::IS_INSTANCEOF))->count(1);
    }

    function test_factory()
    {
        $refclass = new ReflectionClass(Dummy::class);
        that(ReflectionAttribute::factory($refclass))->count(1);
        that(ReflectionAttribute::factory($refclass, null, ReflectionAttribute::FOLLOW_INHERITANCE))->count(1);
        that(ReflectionAttribute::factory($refclass, null, ReflectionAttribute::FOLLOW_INHERITANCE)[0]->getArguments())->is([2]);

        $refmethod = $refclass->getMethod('single');
        $single    = ReflectionAttribute::factory($refmethod, null, ReflectionAttribute::SEE_ALSO_CLASS | ReflectionAttribute::MERGE_REPEATABLE);
        that($single)->count(2);
        that($single[0]->getName())->is(SingleAttribute::class);
        that($single[0]->getTarget())->is(Attribute::TARGET_METHOD);
        that($single[0]->getArguments())->is([1]);

        $refclass = new ReflectionClass(ChildClass::class);
        that(ReflectionAttribute::factory($refclass))->count(1);
        that(ReflectionAttribute::factory($refclass, null, ReflectionAttribute::FOLLOW_INHERITANCE))->count(1);
        that(ReflectionAttribute::factory($refclass, null, ReflectionAttribute::FOLLOW_INHERITANCE)[0]->getArguments())->is([3]);
        $merged = ReflectionAttribute::factory($refclass, AllAttribute::class, ReflectionAttribute::FOLLOW_INHERITANCE | ReflectionAttribute::MERGE_REPEATABLE);
        that($merged)->count(3);
        that($merged[0]->getName())->is(AllAttribute::class);
        that($merged[0]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[0]->getArguments())->is([3]);
        that($merged[1]->getName())->is(AllAttribute::class);
        that($merged[1]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[1]->getArguments())->is([2]);
        that($merged[2]->getName())->is(AllAttribute::class);
        that($merged[2]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[2]->getArguments())->is([1]);

        $refconst = $refclass->getReflectionConstant('const');
        that(ReflectionAttribute::factory($refconst))->count(0);
        $class = ReflectionAttribute::factory($refconst, null, ReflectionAttribute::SEE_ALSO_CLASS);
        that($class)->count(1);
        that($class[0]->getArguments())->is([3]);
        $inheritance = ReflectionAttribute::factory($refconst, null, ReflectionAttribute::FOLLOW_INHERITANCE);
        that($inheritance)->count(1);
        that($inheritance[0]->getArguments())->is([2]);
        $merged = ReflectionAttribute::factory($refconst, AllAttribute::class, ReflectionAttribute::SEE_ALSO_CLASS | ReflectionAttribute::FOLLOW_INHERITANCE | ReflectionAttribute::MERGE_REPEATABLE);
        that($merged)->count(5);
        that($merged[0]->getName())->is(AllAttribute::class);
        that($merged[0]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[0]->getArguments())->is([3]);
        that($merged[1]->getName())->is(AllAttribute::class);
        that($merged[1]->getTarget())->is(Attribute::TARGET_CLASS_CONSTANT);
        that($merged[1]->getArguments())->is([2]);
        that($merged[2]->getName())->is(AllAttribute::class);
        that($merged[2]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[2]->getArguments())->is([2]);
        that($merged[3]->getName())->is(AllAttribute::class);
        that($merged[3]->getTarget())->is(Attribute::TARGET_CLASS_CONSTANT);
        that($merged[3]->getArguments())->is([1]);
        that($merged[4]->getName())->is(AllAttribute::class);
        that($merged[4]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[4]->getArguments())->is([1]);

        $refproperty = $refclass->getProperty('property');
        that(ReflectionAttribute::factory($refproperty))->count(0);
        $class = ReflectionAttribute::factory($refproperty, null, ReflectionAttribute::SEE_ALSO_CLASS);
        that($class)->count(1);
        that($class[0]->getArguments())->is([3]);
        $inheritance = ReflectionAttribute::factory($refproperty, null, ReflectionAttribute::FOLLOW_INHERITANCE);
        that($inheritance)->count(1);
        that($inheritance[0]->getArguments())->is([2]);
        $merged = ReflectionAttribute::factory($refproperty, AllAttribute::class, ReflectionAttribute::SEE_ALSO_CLASS | ReflectionAttribute::FOLLOW_INHERITANCE | ReflectionAttribute::ALL);
        that($merged)->count(5);
        that($merged[0]->getName())->is(AllAttribute::class);
        that($merged[0]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[0]->getArguments())->is([3]);
        that($merged[1]->getName())->is(AllAttribute::class);
        that($merged[1]->getTarget())->is(Attribute::TARGET_PROPERTY);
        that($merged[1]->getArguments())->is([2]);
        that($merged[2]->getName())->is(AllAttribute::class);
        that($merged[2]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[2]->getArguments())->is([2]);
        that($merged[3]->getName())->is(AllAttribute::class);
        that($merged[3]->getTarget())->is(Attribute::TARGET_PROPERTY);
        that($merged[3]->getArguments())->is([1]);
        that($merged[4]->getName())->is(AllAttribute::class);
        that($merged[4]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[4]->getArguments())->is([1]);

        $refmethod = $refclass->getMethod('method');
        that(ReflectionAttribute::factory($refmethod))->count(0);
        $class = ReflectionAttribute::factory($refmethod, null, ReflectionAttribute::SEE_ALSO_CLASS);
        that($class)->count(1);
        that($class[0]->getArguments())->is([3]);
        $inheritance = ReflectionAttribute::factory($refmethod, null, ReflectionAttribute::FOLLOW_INHERITANCE);
        that($inheritance)->count(1);
        that($inheritance[0]->getArguments())->is([2]);
        $merged = ReflectionAttribute::factory($refmethod, AllAttribute::class, ReflectionAttribute::SEE_ALSO_CLASS | ReflectionAttribute::FOLLOW_INHERITANCE | ReflectionAttribute::MERGE_REPEATABLE);
        that($merged)->count(5);
        that($merged[0]->getName())->is(AllAttribute::class);
        that($merged[0]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[0]->getArguments())->is([3]);
        that($merged[1]->getName())->is(AllAttribute::class);
        that($merged[1]->getTarget())->is(Attribute::TARGET_METHOD);
        that($merged[1]->getArguments())->is([2]);
        that($merged[2]->getName())->is(AllAttribute::class);
        that($merged[2]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[2]->getArguments())->is([2]);
        that($merged[3]->getName())->is(AllAttribute::class);
        that($merged[3]->getTarget())->is(Attribute::TARGET_METHOD);
        that($merged[3]->getArguments())->is([1]);
        that($merged[4]->getName())->is(AllAttribute::class);
        that($merged[4]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[4]->getArguments())->is([1]);

        $refparameter = $refclass->getMethod('method')->getParameters()[0];
        that(ReflectionAttribute::factory($refparameter))->count(1);
        $class = ReflectionAttribute::factory($refparameter, null, ReflectionAttribute::SEE_ALSO_CLASS);
        that($class)->count(1);
        that($class[0]->getArguments())->is([3]);
        $inheritance = ReflectionAttribute::factory($refparameter, null, ReflectionAttribute::FOLLOW_INHERITANCE);
        that($inheritance)->count(1);
        that($inheritance[0]->getArguments())->is([3]);
        $merged = ReflectionAttribute::factory($refparameter, AllAttribute::class, ReflectionAttribute::SEE_ALSO_CLASS | ReflectionAttribute::FOLLOW_INHERITANCE | ReflectionAttribute::MERGE_REPEATABLE);
        that($merged)->count(5);
        that($merged[0]->getName())->is(AllAttribute::class);
        that($merged[0]->getTarget())->is(Attribute::TARGET_PARAMETER);
        that($merged[0]->getArguments())->is([3]);
        that($merged[1]->getName())->is(AllAttribute::class);
        that($merged[1]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[1]->getArguments())->is([3]);
        that($merged[2]->getName())->is(AllAttribute::class);
        that($merged[2]->getTarget())->is(Attribute::TARGET_PARAMETER);
        that($merged[2]->getArguments())->is([2]);
        that($merged[3]->getName())->is(AllAttribute::class);
        that($merged[3]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[3]->getArguments())->is([2]);
        that($merged[4]->getName())->is(AllAttribute::class);
        that($merged[4]->getTarget())->is(Attribute::TARGET_CLASS);
        that($merged[4]->getArguments())->is([1]);

        $refmethod = $refclass->getMethod('method');
        that(ReflectionAttribute::factory($refmethod, AncestorAttribute::class, 0))->count(0);
        that(ReflectionAttribute::factory($refmethod, AncestorAttribute::class, ReflectionAttribute::SEE_ALSO_CLASS))->count(0);
        that(ReflectionAttribute::factory($refmethod, AncestorAttribute::class, ReflectionAttribute::FOLLOW_INHERITANCE))->count(0);
        that(ReflectionAttribute::factory($refmethod, AncestorAttribute::class, ReflectionAttribute::SEE_ALSO_CLASS | ReflectionAttribute::FOLLOW_INHERITANCE))->count(1);
    }

    function test_getNamedArguments_valid()
    {
        $attr = ConcreteAttribute1::of(ConcreteClassValid::class);
        that($attr)->getNamedArguments()->is([]);

        $attr = ConcreteAttribute2::arrayOf(ConcreteClassValid::class)[0];
        that($attr)->getNamedArguments()->is([
            "index"   => 0,
            "name"    => "hoge",
            "options" => [],
        ]);
        that($attr)->getNamedArgument('name')->is('hoge');
        that($attr)->getNamedArgument('undefined')->wasThrown("undefined does not exist");

        $attr = VariadicAttribute::of(ConcreteClass::class);
        that($attr)->getNamedArguments()->is([
            "mode"  => 123,
            "files" => [
                0   => "a",
                1   => "b",
                2   => "c",
                "x" => "X",
            ],
        ]);
        that($attr)->getNamedArgument('files')->is([
            0   => "a",
            1   => "b",
            2   => "c",
            "x" => "X",
        ]);
        that($attr)->getNamedArgument('undefined')->wasThrown("undefined does not exist");
    }

    function test_getNamedArguments_invalid()
    {
        $attr = ConcreteAttribute1::of(ConcreteClassInvalid::class);
        that($attr)->getNamedArguments()->wasThrown("does not have constructor");

        $attr = ConcreteAttribute2::of(ConcreteClassInvalid::class);
        that($attr)->getNamedArguments()->wasThrown("does not have default value");
    }

    function test_reflection()
    {
        $attr = ConcreteAttribute::of(ConcreteClass::class);
        that($attr->getReflection())->isInstanceOf(ReflectionClass::class);

        $attr = ConcreteAttribute::of([ConcreteClass::class, 'CONST']);
        that($attr->getReflection())->isInstanceOf(ReflectionClassConstant::class);

        $attr = ConcreteAttribute::of([ConcreteClass::class, '$property']);
        that($attr->getReflection())->isInstanceOf(ReflectionProperty::class);

        $attr = ConcreteAttribute::of([ConcreteClass::class, 'method']);
        that($attr->getReflection())->isInstanceOf(ReflectionMethod::class);

        $attr = ConcreteAttribute::of([ConcreteClass::class, 'method', 0]);
        that($attr->getReflection())->isInstanceOf(ReflectionParameter::class);

        $refclass = new ReflectionClass(ConcreteClass::class);
        $attr     = ConcreteAttribute::of($refclass);
        that($attr->getReflection())->isSame($refclass);
    }

    function test_original()
    {
        $attr = ConcreteAttribute1::of(ConcreteClassValid::class);
        that($attr)->getName()->is(ConcreteAttribute1::class);
        that($attr)->getArguments()->is([]);
        that($attr)->isRepeated()->isFalse();
        that($attr)->getTarget()->is(Attribute::TARGET_CLASS);
        that($attr)->newInstance()->isInstanceOf(ConcreteAttribute1::class);

        $attr = ConcreteAttribute2::arrayOf(ConcreteClassValid::class)[0];
        that($attr)->getName()->is(ConcreteAttribute2::class);
        that($attr)->getArguments()->is([
            0      => 0,
            "name" => "hoge",
        ]);
        that($attr)->isRepeated()->isTrue();
        that($attr)->getTarget()->is(Attribute::TARGET_CLASS);
        that($attr)->newInstance()->isInstanceOf(ConcreteAttribute2::class);
    }
}

#[Attribute]
class ConcreteAttribute extends AbstractAttribute
{
}

#[Attribute]
class ConcreteAttribute1 extends AbstractAttribute
{
}

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ConcreteAttribute2 extends AbstractAttribute
{
    public function __construct(int $index, string $name, $options = []) { }
}

#[Attribute]
class VariadicAttribute extends AbstractAttribute
{
    public function __construct(int $mode, string ...$files) { }
}

#[UndefinedAttribute(0, dummy: 123)]
#[ConcreteAttribute]
#[VariadicAttribute(123, 'a', 'b', 'c', x: 'X')]
class ConcreteClass
{
    #[ConcreteAttribute]
    private const CONST = null;

    #[ConcreteAttribute]
    private $property;

    #[ConcreteAttribute]
    private function method(
        #[ConcreteAttribute]
        int $param
    ) {
    }
}

#[ConcreteAttribute1]
#[ConcreteAttribute2(0, name: 'hoge')]
#[ConcreteAttribute2(1, name: 'fuga')]
class ConcreteClassValid
{
}

#[ConcreteAttribute1('dummy')]
#[ConcreteAttribute2()]
class ConcreteClassInvalid
{
}
