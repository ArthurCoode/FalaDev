<?php

namespace App\Challenges\Languages;

use App\Challenges\Signature;
use App\Challenges\ValueType;

class JavaScript extends Toolchain
{
    public function solutionFile(): string
    {
        return 'solution.js';
    }

    public function starter(Signature $signature): string
    {
        return $this->signatureLine($signature)." {\n  // Your code here\n}\n";
    }

    public function signatureLine(Signature $signature): string
    {
        return 'function '.$signature->name.'('.implode(', ', array_column($signature->params, 'name')).')';
    }

    public function program(Signature $signature, string $code, array $calls, string $marker): array
    {
        return [
            $this->solutionFile() => $code,
            'main.js' => $this->harness($signature, $calls, $marker, ''),
        ];
    }

    protected function literal(ValueType $type, mixed $value): string
    {
        return is_array($value) ? $this->items($type, $value) : self::json($value);
    }

    /**
     * The Node.js test program. $prepare turns the source into JavaScript
     * (TypeScript strips its types there).
     *
     * @param  list<list<mixed>>  $calls
     */
    protected function harness(Signature $signature, array $calls, string $marker, string $prepare): string
    {
        $name = $signature->name;
        $file = $this->solutionFile();
        $mark = self::json($marker);
        $cases = implode("\n", array_map(
            fn (array $args) => "    () => solution({$this->arguments($signature, $args)}),",
            $calls,
        ));

        return <<<JS
        'use strict';
        const fs = require('node:fs');
        const vm = require('node:vm');
        const MARK = {$mark};
        const out = (line) => process.stdout.write('\\n' + MARK + line + '\\n');
        const where = (error) => {
            const match = String(error && error.stack).match(/{$file}:(\\d+)/);
            return match ? ' (line ' + match[1] + ')' : '';
        };
        const message = (error) =>
            error instanceof Error ? error.name + ': ' + error.message + where(error) : 'Thrown: ' + String(error);
        const show = (value) => {
            if (value === undefined) return 'undefined';
            try {
                const json = JSON.stringify(value);
                return json === undefined ? String(value) : json;
            } catch {
                return String(value);
            }
        };

        let solution;
        try {
            let source = fs.readFileSync(__dirname + '/{$file}', 'utf8');{$prepare}
            solution = vm.runInThisContext(source + '\\n;typeof {$name} === "function" ? {$name} : undefined;', {
                filename: '{$file}',
            });
        } catch (error) {
            const stack = String(error && error.stack ? error.stack : error);
            process.stderr.write(error instanceof SyntaxError || (error && error.name === 'SyntaxError') ? stack.split('\\n    at ')[0] : message(error));
            process.exit(1);
        }
        if (typeof solution !== 'function') {
            process.stderr.write('The function "{$name}" was not found. Keep its name.');
            process.exit(1);
        }

        const cases = [
        {$cases}
        ];
        cases.forEach((run, index) => {
            try {
                out(index + ' ok ' + show(run()));
            } catch (error) {
                out(index + ' err ' + JSON.stringify(message(error)));
            }
        });

        JS;
    }
}
