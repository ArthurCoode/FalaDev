<?php

namespace App\Challenges\Languages;

use App\Challenges\Signature;
use App\Challenges\ValueType;

/**
 * The user writes a function in package main (solution.go); the test
 * program (main.go) is built with it. A nil slice prints as [], as Go
 * code treats it as empty.
 */
class Go extends Toolchain
{
    public function solutionFile(): string
    {
        return 'solution.go';
    }

    public function starter(Signature $signature): string
    {
        return "package main\n\n".$this->signatureLine($signature)." {\n\t// Your code here\n\treturn "
            .$this->empty($signature->returns)."\n}\n";
    }

    public function signatureLine(Signature $signature): string
    {
        $params = array_map(fn (array $param) => $param['name'].' '.$this->type($param['type']), $signature->params);

        return 'func '.$signature->name.'('.implode(', ', $params).') '.$this->type($signature->returns);
    }

    public function program(Signature $signature, string $code, array $calls, string $marker): array
    {
        $name = $signature->name;
        $mark = self::json($marker);
        $cases = implode("\n", array_map(
            fn (array $args) => "\t\tfunc() any { return {$name}({$this->arguments($signature, $args)}) },",
            $calls,
        ));

        $main = <<<GO
        package main

        import (
        	"fmt"
        	"os"
        	"strconv"
        	"strings"
        )

        const faladevMark = {$mark}

        func faladevOut(line string) {
        	os.Stdout.WriteString("\\n" + faladevMark + line + "\\n")
        }

        func faladevRun(index int, run func() any) {
        	defer func() {
        		if recovered := recover(); recovered != nil {
        			faladevOut(strconv.Itoa(index) + " err " + faladevQuote(fmt.Sprint("panic: ", recovered)))
        		}
        	}()
        	faladevOut(strconv.Itoa(index) + " ok " + faladevShow(run()))
        }

        func faladevShow(value any) string {
        	switch v := value.(type) {
        	case nil:
        		return "null"
        	case bool:
        		return strconv.FormatBool(v)
        	case int:
        		return strconv.Itoa(v)
        	case int64:
        		return strconv.FormatInt(v, 10)
        	case string:
        		return faladevQuote(v)
        	case []int:
        		items := make([]string, len(v))
        		for i, item := range v {
        			items[i] = strconv.Itoa(item)
        		}
        		return "[" + strings.Join(items, ",") + "]"
        	case []string:
        		items := make([]string, len(v))
        		for i, item := range v {
        			items[i] = faladevQuote(item)
        		}
        		return "[" + strings.Join(items, ",") + "]"
        	case []bool:
        		items := make([]string, len(v))
        		for i, item := range v {
        			items[i] = strconv.FormatBool(item)
        		}
        		return "[" + strings.Join(items, ",") + "]"
        	case [][]int:
        		items := make([]string, len(v))
        		for i, item := range v {
        			items[i] = faladevShow(item)
        		}
        		return "[" + strings.Join(items, ",") + "]"
        	default:
        		return faladevQuote(fmt.Sprint(v))
        	}
        }

        func faladevQuote(text string) string {
        	var json strings.Builder
        	json.WriteByte('"')
        	for _, c := range text {
        		switch {
        		case c == '"':
        			json.WriteString("\\\\\\"")
        		case c == '\\\\':
        			json.WriteString("\\\\\\\\")
        		case c == '\\n':
        			json.WriteString("\\\\n")
        		case c == '\\r':
        			json.WriteString("\\\\r")
        		case c == '\\t':
        			json.WriteString("\\\\t")
        		case c < 0x20:
        			json.WriteString(fmt.Sprintf("\\\\u%04x", c))
        		default:
        			json.WriteRune(c)
        		}
        	}
        	json.WriteByte('"')
        	return json.String()
        }

        func main() {
        	cases := []func() any{
        {$cases}
        	}
        	for index, run := range cases {
        		faladevRun(index, run)
        	}
        }

        GO;

        return [$this->solutionFile() => $code, 'main.go' => $main];
    }

    protected function literal(ValueType $type, mixed $value): string
    {
        return match ($type) {
            ValueType::Int => (string) $value,
            ValueType::Bool => $value ? 'true' : 'false',
            ValueType::String => self::json($value),
            ValueType::IntList => $this->items($type, (array) $value, '[]int{', '}'),
            ValueType::StringList => $this->items($type, (array) $value, '[]string{', '}'),
            ValueType::IntGrid => $this->items($type, (array) $value, '[][]int{', '}'),
        };
    }

    private function type(ValueType $type): string
    {
        return match ($type) {
            ValueType::Int => 'int',
            ValueType::Bool => 'bool',
            ValueType::String => 'string',
            ValueType::IntList => '[]int',
            ValueType::StringList => '[]string',
            ValueType::IntGrid => '[][]int',
        };
    }

    private function empty(ValueType $type): string
    {
        return match ($type) {
            ValueType::Int => '0',
            ValueType::Bool => 'false',
            ValueType::String => '""',
            default => 'nil',
        };
    }
}
