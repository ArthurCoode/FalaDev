<?php

namespace App\Challenges\Languages;

use App\Challenges\Signature;
use App\Challenges\ValueType;

class Python extends Toolchain
{
    public function solutionFile(): string
    {
        return 'solution.py';
    }

    public function functionName(Signature $signature): string
    {
        return self::snake($signature->name);
    }

    public function starter(Signature $signature): string
    {
        return $this->signatureLine($signature).":\n    # Your code here\n    pass\n";
    }

    public function signatureLine(Signature $signature): string
    {
        $params = array_map(fn (array $param) => $param['name'].': '.$this->type($param['type']), $signature->params);

        return 'def '.$this->functionName($signature).'('.implode(', ', $params).') -> '.$this->type($signature->returns);
    }

    public function program(Signature $signature, string $code, array $calls, string $marker): array
    {
        $name = $this->functionName($signature);
        $file = $this->solutionFile();
        $mark = self::json($marker);
        $cases = implode("\n", array_map(
            fn (array $args) => "    lambda: solution({$this->arguments($signature, $args)}),",
            $calls,
        ));

        $main = <<<PY
        import json
        import sys
        import traceback

        sys.setrecursionlimit(5000)
        MARK = {$mark}


        def out(line):
            sys.stdout.write("\\n" + MARK + line + "\\n")
            sys.stdout.flush()


        def where(error):
            lines = [frame.lineno for frame in traceback.extract_tb(error.__traceback__) if frame.filename == "{$file}"]
            return " (line %d)" % lines[-1] if lines else ""


        def message(error):
            return type(error).__name__ + ": " + str(error) + where(error)


        def show(value):
            try:
                return json.dumps(value, ensure_ascii=False)
            except (TypeError, ValueError):
                return repr(value)


        namespace = {"__name__": "solution"}
        try:
            with open("{$file}", encoding="utf-8") as source:
                exec(compile(source.read(), "{$file}", "exec"), namespace)
        except SyntaxError as error:
            sys.stderr.write("".join(traceback.format_exception_only(type(error), error)))
            sys.exit(1)
        except Exception as error:
            sys.stderr.write(message(error))
            sys.exit(1)

        solution = namespace.get("{$name}")
        if not callable(solution):
            sys.stderr.write('The function "{$name}" was not found. Keep its name.')
            sys.exit(1)

        cases = [
        {$cases}
        ]
        for index, run in enumerate(cases):
            try:
                out("%d ok %s" % (index, show(run())))
            except Exception as error:
                out("%d err %s" % (index, json.dumps(message(error), ensure_ascii=False)))

        PY;

        return [$file => $code, 'main.py' => $main];
    }

    protected function literal(ValueType $type, mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'True' : 'False',
            is_array($value) => $this->items($type, $value),
            default => self::json($value),
        };
    }

    private function type(ValueType $type): string
    {
        return match ($type) {
            ValueType::Int => 'int',
            ValueType::Bool => 'bool',
            ValueType::String => 'str',
            ValueType::IntList => 'list[int]',
            ValueType::StringList => 'list[str]',
            ValueType::IntGrid => 'list[list[int]]',
        };
    }
}
