<?php
namespace ryunosuke\Test\UseCase;

use ArrayObject;
use ryunosuke\utility\attribute\Attribute\Friend;
use ryunosuke\utility\attribute\ClassTrait\FriendTrait;
use ryunosuke\utility\attribute\Utility\File;

class FriendTest extends \ryunosuke\Test\AbstractTestCase
{
    function test()
    {
        ConcreteFriend::annotate(); // for coverage

        $concrete = new ConcreteFriend();

        that(isset($concrete->all))->is(true);
        that(isset($concrete->ryunosuke))->is(true);

        that($concrete->all)->is('all');
        that($concrete->ryunosuke)->is('ryunosuke');

        that($concrete->all())->is('all');
        that($concrete->ryunosuke())->is('ryunosuke');
        that(ConcreteFriend::staticAll())->is('staticAll');

        that(isset($concrete->privateField))->is(true);
        that(isset($concrete->protectedField))->is(true);

        that($concrete->privateField)->is('private');
        that($concrete->protectedField)->is('protected');

        that($concrete->privateMethod())->is('privateMethod');
        that($concrete->protectedMethod())->is('protectedMethod');

        // for coverage
        $concrete = new ConcreteFriend();

        @that(isset($concrete->external))->is(true);
        @that(isset($concrete->none))->is(true);

        @that($concrete->external)->is('external');
        @that($concrete->none)->is('none');

        @that($concrete->external())->is('external');
        @that($concrete->none())->is('none');

        @that(isset($concrete->undefined))->is(false);
        @that($concrete)->undefined->wasThrown('Undefined property');
        @that($concrete)->undefined()->wasThrown('undefined method');
        @that($concrete)::undefined()->wasThrown('undefined method');
    }

    function test_promotion()
    {
        if (version_compare(PHP_VERSION, 8.0) < 0) {
            $this->markTestSkipped();
        }
        $object = require_once __DIR__ . '/../files/promotion.php';
        $object->annotate();
        that($object->privateField)->is(1);
    }

    function test_annotate()
    {
        $classfile = File::factory(__DIR__ . '/../files/friend.php');

        require_once $classfile->getFileName();
        try {
            \NoTarget::annotate();
            \NoAnnotation::annotate();
            \AlreadyAnnotation::annotate();
            \IndentNoAnnotation::annotate();
            \IndentAlreadyAnnotation::annotate();

            that((string) $classfile)->contains(<<<'DOC'
            /**
             * @auto-document-Friend:begin
             * @property $private
             * @auto-document-Friend:end
             */
            class NoAnnotation
            DOC,);
            that((string) $classfile)->contains(<<<'DOC'
            /**
             * merged
             * @auto-document-Friend:begin
             * @property $private
             * @auto-document-Friend:end
             */
            class AlreadyAnnotation
            DOC,);
            that((string) $classfile)->contains(<<<'DOC'
                /**
                 * @auto-document-Friend:begin
                 * @property $private
                 * @auto-document-Friend:end
                 */
                class IndentNoAnnotation
            DOC,);
            that((string) $classfile)->contains(<<<'DOC'
                /**
                 * merged
                 * @auto-document-Friend:begin
                 * @property $private
                 * @auto-document-Friend:end
                 */
                class IndentAlreadyAnnotation
            DOC,);
        }
        finally {
            $classfile->rollback();
        }
    }
}

/**
 * @auto-document-Friend:begin
 * @property string $protectedField
 * @property string $privateField
 * @method void protectedMethod()
 * @method void privateMethod()
 * @auto-document-Friend:end
 */
class AbstractFriend
{
    #[Friend]
    private string   $privateField   = 'private';
    #[Friend]
    protected string $protectedField = 'protected';

    #[Friend]
    private function privateMethod()
    {
        return __FUNCTION__;
    }

    #[Friend]
    protected function protectedMethod()
    {
        return __FUNCTION__;
    }
}

/**
 * @auto-document-Friend:begin
 * @const ALL
 * @const RYUNOSUKE
 * @property string $all
 * @property string $ryunosuke
 * @property string $external
 * @method ?\ArrayObject full(&$a, $b = 123, $c = SORT_ASC, string ...$z)
 * @method string all()
 * @method string ryunosuke()
 * @method string external()
 * @method static string staticAll()
 * @auto-document-Friend:end
 */
class ConcreteFriend extends AbstractFriend
{
    use FriendTrait;

    #[Friend(['*'])]
    private const ALL = 'all';

    #[Friend(['ryunosuke*'])]
    private const RYUNOSUKE = 'RYUNOSUKE';

    #[Friend(['*'])]
    private string $all = 'all';

    #[Friend(['ryunosuke*'])]
    private string $ryunosuke = 'ryunosuke';

    #[Friend(['external'])]
    private string $external = 'external';

    private string $none = 'none';

    #[Friend(['*'])]
    private function full(&$a, $b = 123, $c = SORT_ASC, string ...$z): ?ArrayObject
    {
        return null;
    }

    #[Friend(['*'])]
    private function all(): string
    {
        return __FUNCTION__;
    }

    #[Friend(['ryunosuke*'])]
    private function ryunosuke(): string
    {
        return __FUNCTION__;
    }

    #[Friend(['external'])]
    private function external(): string
    {
        return __FUNCTION__;
    }

    private function none(): string
    {
        return __FUNCTION__;
    }

    #[Friend(['*'])]
    private static function staticAll(): string
    {
        return __FUNCTION__;
    }
}
