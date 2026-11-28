<?php

namespace App\Challenges\Languages;

use App\Challenges\Signature;
use App\Challenges\ValueType;

/**
 * How one language writes a challenge: the starter code, the signature shown
 * above the editor and the test program that calls the user's function.
 *
 * The test program prints one line per test, starting with a random marker:
 * "<marker><index> ok <the returned value as JSON>" or
 * "<marker><index> err <the error message as a JSON string>".
 * Everything else the program prints is the user's own output. The expected
 * values never go into the program: the server compares them.
 */
abstract class Toolchain
{
    /** The file that holds the user's code, as named in error messages. */
    abstract public function solutionFile(): string;

    abstract public function starter(Signature $signature): string;

    /** The declaration shown above the editor. */
    abstract public function signatureLine(Signature $signature): string;

    /**
     * The files of the test program, by name.
     *
     * @param  list<list<mixed>>  $calls  the arguments of each test
     * @return array<string, string>
     */
    abstract public function program(Signature $signature, string $code, array $calls, string $marker): array;

    /** The value written as a literal of this language. */
    abstract protected function literal(ValueType $type, mixed $value): string;

    /** The function's name in this language's style. */
    public function functionName(Signature $signature): string
    {
        return $signature->name;
    }

    /**
     * A test call as shown to the user: "twoSum([2, 7], 9)".
     *
     * @param  list<mixed>  $args
     */
    public function describeCall(Signature $signature, array $args): string
    {
        return $this->functionName($signature).'('.implode(', ', array_map(self::display(...), $args)).')';
    }

    /**
     * JSON with a space after each comma, for reading: [1, 2, 3].
     */
    public static function display(mixed $value): string
    {
        if (is_array($value) && array_is_list($value)) {
            return '['.implode(', ', array_map(self::display(...), $value)).']';
        }

        return self::json($value);
    }

    protected static function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * The literals of one test's arguments, comma separated.
     *
     * @param  list<mixed>  $args
     */
    protected function arguments(Signature $signature, array $args): string
    {
        return implode(', ', array_map(
            fn (array $param, mixed $value) => $this->literal($param['type'], $value),
            $signature->params,
            $args,
        ));
    }

    /**
     * A list literal from its items: "[1, 2]", "new int[]{1, 2}".
     *
     * @param  array<mixed>  $items
     */
    protected function items(ValueType $type, array $items, string $open = '[', string $close = ']'): string
    {
        $element = $type->element() ?? $type;

        return $open.implode(', ', array_map(fn (mixed $item) => $this->literal($element, $item), $items)).$close;
    }

    protected static function snake(string $name): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }
}
