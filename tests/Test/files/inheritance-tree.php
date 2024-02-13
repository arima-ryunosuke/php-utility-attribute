<?php

#[Attribute(Attribute::TARGET_ALL)]
class AllAttribute
{
    public function __construct($arg) { }
}

#[Attribute(Attribute::TARGET_ALL)]
class AncestorAttribute
{
}

#[AncestorAttribute]
#[AllAttribute(1)]
class AncestorClass
{
    #[AllAttribute(1)]
    private const const = 1;

    #[AllAttribute(1)]
    private $property = 1;

    #[AllAttribute(1)]
    private function method($parameter = 1): AncestorClass
    {
    }
}

#[AllAttribute(2)]
class ParentClass extends AncestorClass
{
    #[AllAttribute(2)]
    private const const = 2;

    #[AllAttribute(2)]
    private $property = 2;

    #[AllAttribute(2)]
    private function method(
        #[AllAttribute(2)]
        $parameter = 2
    ): ParentClass {
    }
}

class Dummy extends ParentClass
{

}

#[AllAttribute(3)]
class ChildClass extends Dummy
{
    private const const = 3;

    private $property = 3;

    private function method($parameter = 3): ChildClass { }

    private const child = 4;

    private $child = 4;

    public function child($parameter = 4): ChildClass { }
}
