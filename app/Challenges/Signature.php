<?php

namespace App\Challenges;

use InvalidArgumentException;

/**
 * The function a challenge asks for, independent of the language: a
 * camelCase name, typed parameters and a return type. Each language
 * writes it in its own style (two_sum in Python, TwoSum in C#).
 */
final readonly class Signature
{
    /**
     * @param  list<array{name: string, type: ValueType}>  $params
     */
    public function __construct(
        public string $name,
        public array $params,
        public ValueType $returns,
    ) {}

    /**
     * @param  array{name?: mixed, params?: mixed, returns?: mixed}  $data
     */
    public static function fromArray(array $data, string $where): self
    {
        $name = (string) ($data['name'] ?? '');

        if (! preg_match('/^[a-z][A-Za-z0-9]*$/', $name) || in_array($name, ['main', 'solution'], true)) {
            throw new InvalidArgumentException("{$where} needs a camelCase function name (not main or solution).");
        }

        $params = [];

        foreach (($data['params'] ?? []) as $param => $type) {
            if (! is_string($param) || ! preg_match('/^[a-z][a-z0-9]*$/', $param)) {
                throw new InvalidArgumentException("{$where}: parameter names are single lowercase words, so every language spells them the same.");
            }

            $params[] = ['name' => $param, 'type' => self::type($type, $where)];
        }

        if ($params === []) {
            throw new InvalidArgumentException("{$where}: the function needs at least one parameter.");
        }

        return new self($name, $params, self::type($data['returns'] ?? null, $where));
    }

    private static function type(mixed $type, string $where): ValueType
    {
        return ValueType::tryFrom((string) $type)
            ?? throw new InvalidArgumentException("{$where} uses an unknown type [".((string) $type).'].');
    }
}
