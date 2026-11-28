<?php

namespace App\Challenges\Languages;

use App\Challenges\Signature;
use App\Challenges\ValueType;

class Php extends Toolchain
{
    public function solutionFile(): string
    {
        return 'solution.php';
    }

    public function starter(Signature $signature): string
    {
        return "<?php\n\n".$this->signatureLine($signature)."\n{\n    // Your code here\n    return ".$this->empty($signature->returns).";\n}\n";
    }

    public function signatureLine(Signature $signature): string
    {
        $params = array_map(fn (array $param) => $this->type($param['type']).' $'.$param['name'], $signature->params);

        return 'function '.$signature->name.'('.implode(', ', $params).'): '.$this->type($signature->returns);
    }

    public function program(Signature $signature, string $code, array $calls, string $marker): array
    {
        $name = $signature->name;
        $file = $this->solutionFile();
        $mark = var_export($marker, true);
        $cases = implode("\n", array_map(
            fn (array $args) => "    fn () => {$name}({$this->arguments($signature, $args)}),",
            $calls,
        ));

        // Without the opening tag PHP would print the code instead of running
        // it. Added on the same line, so the line numbers stay the same.
        if (! str_starts_with(ltrim($code), '<?php')) {
            $code = '<?php '.$code;
        }

        $main = <<<PHP
        <?php

        const FALADEV_MARK = {$mark};

        function faladev_out(string \$line): void
        {
            echo "\\n", FALADEV_MARK, \$line, "\\n";
        }

        function faladev_message(Throwable \$error): string
        {
            \$line = basename(\$error->getFile()) === '{$file}' ? ' (line '.\$error->getLine().')' : '';

            return get_class(\$error).': '.\$error->getMessage().\$line;
        }

        function faladev_show(mixed \$value): string
        {
            \$json = json_encode(\$value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

            return \$json === false ? var_export(\$value, true) : \$json;
        }

        try {
            require __DIR__.'/{$file}';
        } catch (Throwable \$error) {
            fwrite(STDERR, faladev_message(\$error));
            exit(1);
        }

        if (! function_exists('{$name}')) {
            fwrite(STDERR, 'The function "{$name}" was not found. Keep its name.');
            exit(1);
        }

        \$cases = [
        {$cases}
        ];

        foreach (\$cases as \$index => \$run) {
            try {
                faladev_out(\$index.' ok '.faladev_show(\$run()));
            } catch (Throwable \$error) {
                faladev_out(\$index.' err '.json_encode(faladev_message(\$error), JSON_UNESCAPED_UNICODE));
            }
        }

        PHP;

        return [$file => $code, 'main.php' => $main];
    }

    protected function literal(ValueType $type, mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_string($value) => var_export($value, true),
            is_array($value) => $this->items($type, $value),
            default => (string) $value,
        };
    }

    private function type(ValueType $type): string
    {
        return match ($type) {
            ValueType::Int => 'int',
            ValueType::Bool => 'bool',
            ValueType::String => 'string',
            default => 'array',
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
