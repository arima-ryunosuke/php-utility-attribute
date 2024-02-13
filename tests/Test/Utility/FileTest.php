<?php
namespace ryunosuke\Test\Utility;

use ReflectionProperty;
use ryunosuke\utility\attribute\Utility\File;

class FileTest extends \ryunosuke\Test\AbstractTestCase
{
    function test_factory()
    {
        that(File::class)::factory(__FILE__)->isInstanceOf(File::class);

        foreach (range(1, 9) as $n) {
            $filename = tempnam(sys_get_temp_dir(), $n);
            File::factory($filename, 3);
        }

        $files = that(File::class)->var('files');
        foreach (array_slice($files, 0, 3) as $file) {
            $refprop = new ReflectionProperty($file, 'contents');
            $refprop->setAccessible(true);
            that($refprop->isInitialized($file))->isFalse();
        }
        foreach (array_slice($files, -3) as $file) {
            $refprop = new ReflectionProperty($file, 'contents');
            $refprop->setAccessible(true);
            that($refprop->isInitialized($file))->isTrue();
        }

        that(File::class)::factory('not found')->wasThrown("does not exist");
    }

    function test_all()
    {
        /** @var File $file */
        $filename = tempnam(sys_get_temp_dir(), 'line');
        file_put_contents($filename, <<<LINES
        1
        22
        333
        4444
        55555
        666666
        7777777
        88888888
        999999999
        
        LINES,);
        $file = that(File::class)->new($filename)->return();

        // append
        $file->rewrite("a\nb\nc", 10, 15);
        $file[2]     = "X\nY";
        $file[]      = "Z";
        $newcontents = <<<TXT
        1
        22
        X
        Y
        4a
        b
        c6
        7777777
        88888888
        999999999
        
        Z
        TXT;

        that((string) $file)->is($newcontents);

        that(implode("\n", iterator_to_array($file)))->is($newcontents);

        that(count($file))->is(substr_count($newcontents, "\n") + 1);

        that(isset($file[-1]))->isFalse();
        that($file[0])->is("1");
        that($file[1])->is("22");
        that($file[2])->is("X");
        that($file[3])->is("Y");
        that($file[4])->is("4a");
        that($file[5])->is("b");
        that($file[6])->is("c6");
        that($file[7])->is("7777777");
        that($file[8])->is("88888888");
        that($file[9])->is("999999999");
        that($file[10])->is("");
        that($file[11])->is("Z");
        that(isset($file[12]))->isFalse();

        that($file->getLine(0))->isNull();
        that($file->getLine(1))->is("1");
        that($file->getLine(2))->is("22");
        that($file->getLine(3))->is("X");
        that($file->getLine(4))->is("4a");
        that($file->getLine(5))->is("b");
        that($file->getLine(6))->is("c6");
        that($file->getLine(7))->is("7777777");
        that($file->getLine(8))->is("88888888");
        that($file->getLine(9))->is("999999999");
        that($file->getLine(10))->is("");
        that($file->getLine(11))->isNull();

        that($file->getLineIndex(0))->isNull();
        that($file->getLineIndex(1))->is(0);
        that($file->getLineIndex(2))->is(1);
        that($file->getLineIndex(3))->is(2);
        that($file->getLineIndex(4))->is(4);
        that($file->getLineIndex(5))->is(5);
        that($file->getLineIndex(6))->is(6);
        that($file->getLineIndex(7))->is(7);
        that($file->getLineIndex(8))->is(8);
        that($file->getLineIndex(9))->is(9);
        that($file->getLineIndex(10))->is(10);
        that($file->getLineIndex(11))->isNull();

        that($file->getLinePosition(0))->isNull();
        that($file->getLinePosition(1))->is(0);
        that($file->getLinePosition(2))->is(2);
        that($file->getLinePosition(3))->is(5);
        that($file->getLinePosition(4))->is(9);
        that($file->getLinePosition(5))->is(12);
        that($file->getLinePosition(6))->is(14);
        that($file->getLinePosition(7))->is(17);
        that($file->getLinePosition(8))->is(25);
        that($file->getLinePosition(9))->is(34);
        that($file->getLinePosition(10))->is(44);
        that($file->getLinePosition(11))->isNull();

        // delete
        $file->rewrite("", 5, 25);
        unset($file[2]);
        $newcontents = <<<TXT
        1
        22
        
        999999999
        
        Z
        TXT;

        that((string) $file)->is($newcontents);

        that(implode("\n", iterator_to_array($file)))->is($newcontents);

        that(count($file))->is(substr_count($newcontents, "\n") + 1);

        that(isset($file[-1]))->isFalse();
        that($file[0])->is("1");
        that($file[1])->is("22");
        that($file[2])->is("");
        that($file[3])->is("999999999");
        that($file[4])->is("");
        that($file[5])->is("Z");
        that(isset($file[6]))->isFalse();

        that($file->getLine(0))->isNull();
        that($file->getLine(1))->is("1");
        that($file->getLine(2))->is("22");
        that($file->getLine(3))->isNull();
        that($file->getLine(4))->isNull();
        that($file->getLine(5))->isNull();
        that($file->getLine(6))->isNull();
        that($file->getLine(7))->isNull();
        that($file->getLine(8))->is("");
        that($file->getLine(9))->is("999999999");
        that($file->getLine(10))->is("");
        that($file->getLine(11))->isNull();

        that($file->getLineIndex(0))->isNull();
        that($file->getLineIndex(1))->is(0);
        that($file->getLineIndex(2))->is(1);
        that($file->getLineIndex(3))->isNull();
        that($file->getLineIndex(4))->isNull();
        that($file->getLineIndex(5))->isNull();
        that($file->getLineIndex(6))->isNull();
        that($file->getLineIndex(7))->isNull();
        that($file->getLineIndex(8))->is(2);
        that($file->getLineIndex(9))->is(3);
        that($file->getLineIndex(10))->is(4);
        that($file->getLineIndex(11))->isNull();

        that($file->getLinePosition(0))->isNull();
        that($file->getLinePosition(1))->is(0);
        that($file->getLinePosition(2))->is(2);
        that($file->getLinePosition(3))->isNull();
        that($file->getLinePosition(4))->isNull();
        that($file->getLinePosition(5))->isNull();
        that($file->getLinePosition(6))->isNull();
        that($file->getLinePosition(7))->isNull();
        that($file->getLinePosition(8))->is(5);
        that($file->getLinePosition(9))->is(6);
        that($file->getLinePosition(10))->is(16);
        that($file->getLinePosition(11))->isNull();

        unset($file);
    }
}
