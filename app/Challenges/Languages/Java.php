<?php

namespace App\Challenges\Languages;

use App\Challenges\Signature;
use App\Challenges\ValueType;

/**
 * The user writes a Solution class (Solution.java); the test program is a
 * Main class compiled with it.
 */
class Java extends Toolchain
{
    public function solutionFile(): string
    {
        return 'Solution.java';
    }

    public function starter(Signature $signature): string
    {
        return "import java.util.*;\n\nclass Solution {\n    ".$this->signatureLine($signature)." {\n        // Your code here\n        return "
            .$this->empty($signature->returns).";\n    }\n}\n";
    }

    public function signatureLine(Signature $signature): string
    {
        $params = array_map(fn (array $param) => $this->type($param['type']).' '.$param['name'], $signature->params);

        return 'public '.$this->type($signature->returns).' '.$signature->name.'('.implode(', ', $params).')';
    }

    public function program(Signature $signature, string $code, array $calls, string $marker): array
    {
        $name = $signature->name;
        $mark = self::json($marker);
        $cases = implode("\n", array_map(
            fn (array $args) => "            () -> solution.{$name}({$this->arguments($signature, $args)}),",
            $calls,
        ));

        $main = <<<JAVA
        import java.util.*;

        class Main {
            static final String MARK = {$mark};

            interface Case {
                Object run() throws Throwable;
            }

            public static void main(String[] args) {
                Solution solution = new Solution();
                Case[] cases = {
        {$cases}
                };
                for (int i = 0; i < cases.length; i++) {
                    try {
                        out(i + " ok " + show(cases[i].run()));
                    } catch (Throwable error) {
                        out(i + " err " + quote(message(error)));
                    }
                }
                System.out.flush();
            }

            static void out(String line) {
                System.out.print("\\n" + MARK + line + "\\n");
                System.out.flush();
            }

            static String message(Throwable error) {
                String text = error.getClass().getSimpleName() + (error.getMessage() == null ? "" : ": " + error.getMessage());
                for (StackTraceElement frame : error.getStackTrace()) {
                    if ("Solution.java".equals(frame.getFileName())) {
                        return text + " (line " + frame.getLineNumber() + ")";
                    }
                }
                return text;
            }

            static String show(Object value) {
                if (value == null) return "null";
                if (value instanceof String text) return quote(text);
                if (value instanceof Character c) return quote(String.valueOf(c));
                if (value instanceof Number || value instanceof Boolean) return String.valueOf(value);
                if (value instanceof int[] items) {
                    StringJoiner joined = new StringJoiner(",", "[", "]");
                    for (int item : items) joined.add(String.valueOf(item));
                    return joined.toString();
                }
                if (value instanceof long[] items) {
                    StringJoiner joined = new StringJoiner(",", "[", "]");
                    for (long item : items) joined.add(String.valueOf(item));
                    return joined.toString();
                }
                if (value instanceof boolean[] items) {
                    StringJoiner joined = new StringJoiner(",", "[", "]");
                    for (boolean item : items) joined.add(String.valueOf(item));
                    return joined.toString();
                }
                if (value instanceof char[] items) return quote(new String(items));
                if (value instanceof Object[] items) {
                    StringJoiner joined = new StringJoiner(",", "[", "]");
                    for (Object item : items) joined.add(show(item));
                    return joined.toString();
                }
                if (value instanceof Iterable<?> items) {
                    StringJoiner joined = new StringJoiner(",", "[", "]");
                    for (Object item : items) joined.add(show(item));
                    return joined.toString();
                }
                return quote(String.valueOf(value));
            }

            static String quote(String text) {
                StringBuilder json = new StringBuilder("\\"");
                for (char c : text.toCharArray()) {
                    switch (c) {
                        case '"' -> json.append("\\\\\\"");
                        case '\\\\' -> json.append("\\\\\\\\");
                        case '\\n' -> json.append("\\\\n");
                        case '\\r' -> json.append("\\\\r");
                        case '\\t' -> json.append("\\\\t");
                        default -> {
                            if (c < 0x20) json.append(String.format("\\\\u%04x", (int) c));
                            else json.append(c);
                        }
                    }
                }
                return json.append('"').toString();
            }
        }

        JAVA;

        return [$this->solutionFile() => $code, 'Main.java' => $main];
    }

    protected function literal(ValueType $type, mixed $value): string
    {
        return match ($type) {
            ValueType::Int => (string) $value,
            ValueType::Bool => $value ? 'true' : 'false',
            ValueType::String => self::json($value),
            ValueType::IntList => $this->items($type, (array) $value, 'new int[]{', '}'),
            ValueType::StringList => $this->items($type, (array) $value, 'new String[]{', '}'),
            ValueType::IntGrid => $this->grid((array) $value),
        };
    }

    /**
     * @param  array<mixed>  $rows
     */
    private function grid(array $rows): string
    {
        $inner = array_map(fn (mixed $row) => $this->items(ValueType::IntList, (array) $row, '{', '}'), $rows);

        return 'new int[][]{'.implode(', ', $inner).'}';
    }

    private function type(ValueType $type): string
    {
        return match ($type) {
            ValueType::Int => 'int',
            ValueType::Bool => 'boolean',
            ValueType::String => 'String',
            ValueType::IntList => 'int[]',
            ValueType::StringList => 'String[]',
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
            ValueType::StringList => 'new String[0]',
            ValueType::IntGrid => 'new int[0][]',
        };
    }
}
