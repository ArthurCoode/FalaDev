<?php

namespace App\Enums;

use App\Challenges\Languages\CSharp;
use App\Challenges\Languages\Go;
use App\Challenges\Languages\Java;
use App\Challenges\Languages\JavaScript;
use App\Challenges\Languages\Php;
use App\Challenges\Languages\Python;
use App\Challenges\Languages\Toolchain;
use App\Challenges\Languages\TypeScript;

/**
 * The languages of the code challenges. Each one has its own six problems
 * (resources/challenges/{value}.php) and runs in its own Docker image
 * (docker/runners). The order here is the order on the page.
 */
enum ChallengeLanguage: string
{
    case JavaScript = 'javascript';
    case TypeScript = 'typescript';
    case Python = 'python';
    case Java = 'java';
    case CSharp = 'csharp';
    case Go = 'go';
    case Php = 'php';

    public function label(): string
    {
        return match ($this) {
            self::JavaScript => 'JavaScript',
            self::TypeScript => 'TypeScript',
            self::Python => 'Python',
            self::Java => 'Java',
            self::CSharp => 'C#',
            self::Go => 'Go',
            self::Php => 'PHP',
        };
    }

    /**
     * The folder in docker/runners whose image runs this language.
     */
    public function runner(): string
    {
        return match ($this) {
            self::JavaScript, self::TypeScript => 'node',
            self::CSharp => 'csharp',
            default => $this->value,
        };
    }

    public function toolchain(): Toolchain
    {
        return match ($this) {
            self::JavaScript => new JavaScript,
            self::TypeScript => new TypeScript,
            self::Python => new Python,
            self::Java => new Java,
            self::CSharp => new CSharp,
            self::Go => new Go,
            self::Php => new Php,
        };
    }
}
