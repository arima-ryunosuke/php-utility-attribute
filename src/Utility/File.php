<?php
namespace ryunosuke\utility\attribute\Utility;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

class File implements ArrayAccess, IteratorAggregate, Countable
{
    /** @var static[] */
    private static array $files = [];

    private string $filename;
    private string $contents;
    private array  $linemap = [];
    private bool   $changed = false;

    /** @return static */
    public static function factory(string $filename, ?int $purgeSize = null): self
    {
        $realpath = realpath($filename);
        if ($realpath === false) {
            throw new InvalidArgumentException("File $filename does not exist");
        }

        if ($purgeSize !== null) {
            foreach (array_slice(static::$files, 0, -$purgeSize) as $file) {
                $file->putContents();
            }
        }

        return static::$files[$realpath] ??= new static($realpath);
    }

    private function __construct(string $filename)
    {
        $this->filename = $filename;
        $this->linemap();
    }

    public function __destruct()
    {
        $this->putContents();
    }

    public function __toString(): string
    {
        return $this->getContents();
    }

    public function getFileName(): string
    {
        return $this->filename;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->linemap[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $current = $this->linemap[$offset + 0]['pos'];
        $next    = $this->linemap[$offset + 1]['pos'] ?? null;
        return substr($this->getContents(), $current, $next === null ? /*null for compatible 8.0*/ PHP_INT_MAX : $next - $current - 1);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->rewrite("\n$value", strlen($this->getContents()), null);
            return;
        }

        $current = $this->linemap[$offset + 0]['pos'];
        $next    = $this->linemap[$offset + 1]['pos'] ?? null;
        $this->rewrite($value, $current, $next === null ? null : $next - $current - 1);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->offsetSet($offset, "");
    }

    public function getIterator(): Traversable
    {
        foreach ($this->linemap as $n => $map) {
            yield $this->offsetGet($n);
        }
    }

    public function count(): int
    {
        return count($this->linemap);
    }

    public function getLine(int $oldLine): ?string
    {
        $newLine = $this->getLineIndex($oldLine);
        return $this->offsetExists($newLine) ? $this->offsetGet($newLine) : null;
    }

    public function getLineIndex(int $oldLine): ?int
    {
        foreach ($this->linemap as $n => $map) {
            if ($map['old'] === $oldLine) {
                return $n;
            }
        }
        return null;
    }

    public function getLinePosition(int $oldLine): ?int
    {
        foreach ($this->linemap as $map) {
            if ($map['old'] === $oldLine) {
                return $map['pos'];
            }
        }
        return null;
    }

    public function getContents(): string
    {
        return $this->contents ??= str_replace(["\r\n", "\r"], "\n", file_get_contents($this->filename));
    }

    public function putContents(): void
    {
        if (isset($this->contents) && $this->changed) {
            file_put_contents($this->filename, $this->contents);
        }
        unset($this->contents);
    }

    public function rewrite(string $replace, int $offset, ?int $length): string
    {
        $contents = $this->getContents();
        $replace  = str_replace(["\r\n", "\r"], "\n", $replace);

        $this->contents = substr_replace($contents, $replace, $offset, $length);
        if ($this->contents === $contents) {
            return $this->contents;
        }

        $target = null;
        foreach ($this->linemap as $n => $map) {
            if ($map['pos'] > $offset) {
                break;
            }
            $target = $n;
        }

        $oldlines = substr_count($contents, "\n", $offset, $length);
        $newlines = substr_count($replace, "\n");

        array_splice($this->linemap, $target, $oldlines, array_slice($this->linemap, $target, $newlines));

        $this->linemap();

        $this->changed = true;
        return $this->contents;
    }

    public function rollback(): void
    {
        $this->changed = false;
        unset($this->contents);
        $this->linemap();
    }

    private function linemap(): void
    {
        $pos = 0;
        foreach (preg_split('#\n#usm', $this->getContents()) as $n => $line) {
            if (isset($this->linemap[$n])) {
                $this->linemap[$n]['pos'] = $pos;
            }
            else {
                $this->linemap[$n] = ['old' => $n + 1, 'pos' => $pos];
            }
            $pos += strlen($line) + 1;
        }
    }
}
