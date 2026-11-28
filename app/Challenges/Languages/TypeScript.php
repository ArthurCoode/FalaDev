<?php

namespace App\Challenges\Languages;

use App\Challenges\Signature;
use App\Challenges\ValueType;

/**
 * Runs on Node.js, which strips the type annotations (no type checking):
 * the tests check the behavior, the types document the contract.
 */
class TypeScript extends JavaScript
{
    public function solutionFile(): string
    {
        return 'solution.ts';
    }

    public function starter(Signature $signature): string
    {
        return $this->signatureLine($signature)." {\n  // Your code here\n  return ".$this->empty($signature->returns).";\n}\n";
    }

    public function signatureLine(Signature $signature): string
    {
        $params = array_map(fn (array $param) => $param['name'].': '.$this->type($param['type']), $signature->params);

        return 'function '.$signature->name.'('.implode(', ', $params).'): '.$this->type($signature->returns);
    }

    public function program(Signature $signature, string $code, array $calls, string $marker): array
    {
        return [
            $this->solutionFile() => $code,
            'main.js' => $this->harness(
                $signature,
                $calls,
                $marker,
                "\n    source = require('node:module').stripTypeScriptTypes(source, { mode: 'strip' });",
            ),
        ];
    }

    private function type(ValueType $type): string
    {
        return match ($type) {
            ValueType::Int => 'number',
            ValueType::Bool => 'boolean',
            ValueType::String => 'string',
            ValueType::IntList => 'number[]',
            ValueType::StringList => 'string[]',
            ValueType::IntGrid => 'number[][]',
        };
    }

    private function empty(ValueType $type): string
    {
        return match ($type) {
            ValueType::Int => '0',
            ValueType::Bool => 'false',
            ValueType::String => "''",
            default => '[]',
        };
    }
}
