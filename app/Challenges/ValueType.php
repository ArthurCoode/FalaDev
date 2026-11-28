<?php

namespace App\Challenges;

/**
 * The types a challenge function takes and returns. Kept small on purpose:
 * every language can write these as literals and print them as JSON.
 */
enum ValueType: string
{
    case Int = 'int';
    case Bool = 'bool';
    case String = 'string';
    case IntList = 'int[]';
    case StringList = 'string[]';
    case IntGrid = 'int[][]';

    /** Java and C# ints are 32-bit. */
    private const INT_MIN = -2_147_483_648;

    private const INT_MAX = 2_147_483_647;

    public function accepts(mixed $value): bool
    {
        return match ($this) {
            self::Int => self::isInt($value),
            self::Bool => is_bool($value),
            self::String => is_string($value),
            self::IntList => self::isListOf($value, self::isInt(...)),
            self::StringList => self::isListOf($value, is_string(...)),
            self::IntGrid => self::isListOf($value, fn (mixed $row) => self::isListOf($row, self::isInt(...))),
        };
    }

    /**
     * The element type of a list type.
     */
    public function element(): ?self
    {
        return match ($this) {
            self::IntList => self::Int,
            self::StringList => self::String,
            self::IntGrid => self::IntList,
            default => null,
        };
    }

    private static function isInt(mixed $value): bool
    {
        return is_int($value) && $value >= self::INT_MIN && $value <= self::INT_MAX;
    }

    /**
     * @param  callable(mixed): bool  $check
     */
    private static function isListOf(mixed $value, callable $check): bool
    {
        if (! is_array($value) || ! array_is_list($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (! $check($item)) {
                return false;
            }
        }

        return true;
    }
}
