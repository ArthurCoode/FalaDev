<?php

namespace App\Challenges;

use App\Enums\ChallengeLanguage;
use InvalidArgumentException;

/**
 * A code challenge: an interview-style problem in one language, with its
 * statement in English. The user's code runs on the server, in an isolated
 * container (see App\Challenges\Running\ChallengeJudge).
 */
final readonly class Challenge
{
    public const DIFFICULTIES = ['easy', 'medium', 'hard'];

    /**
     * @param  list<string>  $statement  paragraphs; glossary terms marked as {{term}}
     * @param  list<array{input: string, output: string, explanation?: string}>  $examples
     * @param  list<string>  $constraints
     * @param  array<string, string>  $glossary  English term => Portuguese explanation
     * @param  list<string>  $hints
     * @param  list<array{args: list<mixed>, expected: mixed}>  $tests
     * @param  array{en: string, pt: string}  $explanation
     */
    public function __construct(
        public string $slug,
        public ChallengeLanguage $language,
        public string $title,
        public string $difficulty,
        public string $summary,
        public Signature $signature,
        public array $statement,
        public array $examples,
        public array $constraints,
        public array $glossary,
        public array $hints,
        public array $tests,
        public array $explanation,
        public string $solution,
    ) {}

    /**
     * @param  array<string, mixed>  $data  one entry of resources/challenges/{language}.php
     */
    public static function fromArray(array $data, ChallengeLanguage $language): self
    {
        $slug = (string) ($data['slug'] ?? '');
        $where = "Challenge [{$slug}]";

        if (! preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new InvalidArgumentException('A challenge needs a slug made of a-z, 0-9 and dashes.');
        }

        foreach (['title', 'summary', 'solution'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new InvalidArgumentException("{$where} needs a {$field}.");
            }
        }

        if (! in_array($data['difficulty'] ?? null, self::DIFFICULTIES, true)) {
            throw new InvalidArgumentException("{$where} has an unknown difficulty.");
        }

        $signature = Signature::fromArray((array) ($data['signature'] ?? []), $where);
        $statement = array_values(array_map('strval', $data['statement'] ?? []));
        $glossary = $data['glossary'] ?? [];

        if ($statement === []) {
            throw new InvalidArgumentException("{$where} needs a statement.");
        }

        preg_match_all('/\{\{(.+?)\}\}/', implode("\n", $statement), $marked);

        foreach ($marked[1] as $term) {
            if (! isset($glossary[$term])) {
                throw new InvalidArgumentException("{$where}: the term \"{$term}\" is marked but missing from the glossary.");
            }
        }

        // Only the statement shows glossary terms; anywhere else {{ }} would
        // show up as is.
        $elsewhere = json_encode([$data['examples'] ?? [], $data['constraints'] ?? [], $data['hints'] ?? []]);

        if (str_contains((string) $elsewhere, '{{')) {
            throw new InvalidArgumentException("{$where}: glossary terms ({{term}}) only work in the statement.");
        }

        $tests = array_values($data['tests'] ?? []);

        if (count($tests) < 3) {
            throw new InvalidArgumentException("{$where} needs at least three tests.");
        }

        foreach ($tests as $number => $test) {
            self::checkTest($test, $signature, "{$where}, test ".($number + 1));
        }

        if (trim((string) ($data['explanation']['en'] ?? '')) === '' || trim((string) ($data['explanation']['pt'] ?? '')) === '') {
            throw new InvalidArgumentException("{$where} needs an explanation in English and in Portuguese.");
        }

        return new self(
            slug: $slug,
            language: $language,
            title: (string) $data['title'],
            difficulty: (string) $data['difficulty'],
            summary: (string) $data['summary'],
            signature: $signature,
            statement: $statement,
            examples: array_values($data['examples'] ?? []),
            constraints: array_values(array_map('strval', $data['constraints'] ?? [])),
            glossary: array_map('strval', $glossary),
            hints: array_values(array_map('strval', $data['hints'] ?? [])),
            tests: $tests,
            explanation: ['en' => (string) $data['explanation']['en'], 'pt' => (string) $data['explanation']['pt']],
            solution: (string) $data['solution'],
        );
    }

    public function starter(): string
    {
        return $this->language->toolchain()->starter($this->signature);
    }

    /**
     * Everything the challenge page needs. The reference solution stays on
     * the server.
     *
     * @return array<string, mixed>
     */
    public function toPage(): array
    {
        $toolchain = $this->language->toolchain();

        return [
            'slug' => $this->slug,
            'language' => $this->language->value,
            'languageLabel' => $this->language->label(),
            'title' => $this->title,
            'difficulty' => $this->difficulty,
            'summary' => $this->summary,
            'signature' => $toolchain->signatureLine($this->signature),
            'statement' => $this->statement,
            'examples' => $this->examples,
            'constraints' => $this->constraints,
            // A list, not an object: JSON keeps the order and terms may have spaces.
            'glossary' => array_map(
                fn (string $term, string $meaning) => ['term' => $term, 'meaning' => $meaning],
                array_keys($this->glossary),
                $this->glossary,
            ),
            'hints' => $this->hints,
            'starter' => $this->starter(),
            'tests' => array_map(fn (array $test) => [
                'call' => $toolchain->describeCall($this->signature, $test['args']),
                'expected' => $toolchain::display($test['expected']),
            ], $this->tests),
            'explanation' => $this->explanation,
        ];
    }

    private static function checkTest(mixed $test, Signature $signature, string $where): void
    {
        if (! is_array($test) || ! is_array($test['args'] ?? null) || ! array_is_list($test['args']) || ! array_key_exists('expected', $test)) {
            throw new InvalidArgumentException("{$where} needs a list of args and an expected value.");
        }

        if (count($test['args']) !== count($signature->params)) {
            throw new InvalidArgumentException("{$where} passes a different number of args than the function takes.");
        }

        foreach ($signature->params as $index => $param) {
            if (! $param['type']->accepts($test['args'][$index])) {
                throw new InvalidArgumentException("{$where}: the argument {$param['name']} is not a {$param['type']->value}.");
            }
        }

        if (! $signature->returns->accepts($test['expected'])) {
            throw new InvalidArgumentException("{$where}: the expected value is not a {$signature->returns->value}.");
        }
    }
}
