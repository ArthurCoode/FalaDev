<?php

namespace App\Challenges\Languages;

use App\Challenges\Signature;
use App\Challenges\ValueType;

/**
 * The user writes a Solution class (Solution.cs) with a PascalCase method;
 * the test program (Main.cs) is compiled with it.
 */
class CSharp extends Toolchain
{
    public function solutionFile(): string
    {
        return 'Solution.cs';
    }

    public function functionName(Signature $signature): string
    {
        return ucfirst($signature->name);
    }

    public function starter(Signature $signature): string
    {
        return "using System;\nusing System.Collections.Generic;\nusing System.Linq;\n\npublic class Solution\n{\n    "
            .$this->signatureLine($signature)."\n    {\n        // Your code here\n        return "
            .$this->empty($signature->returns).";\n    }\n}\n";
    }

    public function signatureLine(Signature $signature): string
    {
        $params = array_map(fn (array $param) => $this->type($param['type']).' '.$param['name'], $signature->params);

        return 'public '.$this->type($signature->returns).' '.$this->functionName($signature).'('.implode(', ', $params).')';
    }

    public function program(Signature $signature, string $code, array $calls, string $marker): array
    {
        $name = $this->functionName($signature);
        $mark = self::json($marker);
        $cases = implode("\n", array_map(
            fn (array $args) => "            () => solution.{$name}({$this->arguments($signature, $args)}),",
            $calls,
        ));

        $main = <<<CS
        using System;
        using System.Collections;
        using System.Globalization;
        using System.Text;

        static class FaladevMain
        {
            const string Mark = {$mark};

            static void Main()
            {
                var solution = new Solution();
                var cases = new Func<object>[]
                {
        {$cases}
                };
                for (var i = 0; i < cases.Length; i++)
                {
                    try
                    {
                        Out(i + " ok " + Show(cases[i]()));
                    }
                    catch (Exception error)
                    {
                        Out(i + " err " + Quote(error.GetType().Name + ": " + error.Message));
                    }
                }
                Console.Out.Flush();
            }

            static void Out(string line)
            {
                Console.Out.Write("\\n" + Mark + line + "\\n");
                Console.Out.Flush();
            }

            static string Show(object value)
            {
                switch (value)
                {
                    case null: return "null";
                    case bool flag: return flag ? "true" : "false";
                    case string text: return Quote(text);
                    case char c: return Quote(c.ToString());
                    case IFormattable number: return number.ToString(null, CultureInfo.InvariantCulture);
                    case IEnumerable items:
                        var json = new StringBuilder("[");
                        var first = true;
                        foreach (var item in items)
                        {
                            if (!first) json.Append(',');
                            json.Append(Show(item));
                            first = false;
                        }
                        return json.Append(']').ToString();
                    default: return Quote(value.ToString() ?? "");
                }
            }

            static string Quote(string text)
            {
                var json = new StringBuilder("\\"");
                foreach (var c in text)
                {
                    switch (c)
                    {
                        case '"': json.Append("\\\\\\""); break;
                        case '\\\\': json.Append("\\\\\\\\"); break;
                        case '\\n': json.Append("\\\\n"); break;
                        case '\\r': json.Append("\\\\r"); break;
                        case '\\t': json.Append("\\\\t"); break;
                        default:
                            if (c < 0x20) json.Append("\\\\u").Append(((int) c).ToString("x4"));
                            else json.Append(c);
                            break;
                    }
                }
                return json.Append('"').ToString();
            }
        }

        CS;

        return [$this->solutionFile() => $code, 'Main.cs' => $main];
    }

    protected function literal(ValueType $type, mixed $value): string
    {
        return match ($type) {
            ValueType::Int => (string) $value,
            ValueType::Bool => $value ? 'true' : 'false',
            ValueType::String => self::json($value),
            ValueType::IntList => $this->items($type, (array) $value, 'new int[]{', '}'),
            ValueType::StringList => $this->items($type, (array) $value, 'new string[]{', '}'),
            ValueType::IntGrid => $this->items($type, (array) $value, 'new int[][]{', '}'),
        };
    }

    private function type(ValueType $type): string
    {
        return match ($type) {
            ValueType::Int => 'int',
            ValueType::Bool => 'bool',
            ValueType::String => 'string',
            ValueType::IntList => 'int[]',
            ValueType::StringList => 'string[]',
            ValueType::IntGrid => 'int[][]',
        };
    }

    private function empty(ValueType $type): string
    {
        return match ($type) {
            ValueType::Int => '0',
            ValueType::Bool => 'false',
            ValueType::String => '""',
            ValueType::IntList => 'new int[0]',
            ValueType::StringList => 'new string[0]',
            ValueType::IntGrid => 'new int[0][]',
        };
    }
}
