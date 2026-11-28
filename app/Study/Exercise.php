<?php

namespace App\Study;

use App\Enums\ExerciseType;
use InvalidArgumentException;

/**
 * One exercise of a lesson. Grading happens here, on the server; the page
 * grades too for instant feedback, with the same rules (see toPage()).
 */
final readonly class Exercise
{
    /**
     * @param  list<string>  $options  choice and gap; the first one is correct
     * @param  list<list<string>>  $solutions  order: accepted word sequences, normalized
     * @param  list<string>  $distractors  order: extra words that do not belong
     */
    public function __construct(
        public string $key,
        public ExerciseType $type,
        public string $prompt,
        public string $explain,
        public ?string $sentence = null,
        public array $options = [],
        public array $solutions = [],
        public array $distractors = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data  one exercise of the curriculum file
     */
    public static function fromArray(string $lessonSlug, array $data): self
    {
        $id = (string) ($data['id'] ?? '');
        $type = ExerciseType::tryFrom((string) ($data['type'] ?? ''));
        $where = "Exercise [{$lessonSlug}.{$id}]";

        if (! preg_match('/^[a-z0-9-]+$/', $id)) {
            throw new InvalidArgumentException("{$where} needs an id made of a-z, 0-9 and dashes.");
        }

        if ($type === null) {
            throw new InvalidArgumentException("{$where} has an unknown type.");
        }

        foreach (['prompt', 'explain'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new InvalidArgumentException("{$where} needs a {$field}.");
            }
        }

        $sentence = isset($data['sentence']) ? (string) $data['sentence'] : null;
        $options = array_values(array_map('strval', $data['options'] ?? []));
        $solutions = [];
        $distractors = array_values(array_map('strval', $data['distractors'] ?? []));

        if ($type === ExerciseType::Choice || $type === ExerciseType::Gap) {
            if (count($options) < 2 || count(array_unique($options)) !== count($options)) {
                throw new InvalidArgumentException("{$where} needs at least two different options.");
            }
        }

        if ($type === ExerciseType::Gap && substr_count((string) $sentence, '___') !== 1) {
            throw new InvalidArgumentException("{$where} needs a sentence with one \"___\".");
        }

        if ($type === ExerciseType::Order) {
            if ($sentence === null || count(self::words($sentence)) < 3) {
                throw new InvalidArgumentException("{$where} needs a sentence of at least three words.");
            }

            foreach ([$sentence, ...($data['accept'] ?? [])] as $accepted) {
                $words = self::normalize(self::words((string) $accepted));

                if (self::sorted($words) !== self::sorted(self::normalize(self::words($sentence)))) {
                    throw new InvalidArgumentException("{$where}: an accepted sentence must use the same words.");
                }

                $solutions[] = $words;
            }
        }

        return new self(
            key: "{$lessonSlug}.{$id}",
            type: $type,
            prompt: (string) $data['prompt'],
            explain: (string) $data['explain'],
            sentence: $sentence,
            options: $options,
            solutions: $solutions,
            distractors: $distractors,
        );
    }

    /**
     * Whether an answer is right. Choice and gap answers are the chosen
     * option's text; order answers are the chosen words, in order.
     */
    public function isCorrect(mixed $answer): bool
    {
        if ($this->type === ExerciseType::Order) {
            if (! is_array($answer) || ! array_is_list($answer)) {
                return false;
            }

            $words = self::normalize(array_map(fn ($word) => is_string($word) ? $word : '', $answer));

            return in_array($words, $this->solutions, true);
        }

        return is_string($answer) && $answer === $this->options[0];
    }

    /**
     * What the page needs to show and grade the exercise. Options and words
     * are shuffled on every visit.
     *
     * @return array<string, mixed>
     */
    public function toPage(): array
    {
        $page = [
            'key' => $this->key,
            'type' => $this->type->value,
            'prompt' => $this->prompt,
            'explain' => $this->explain,
        ];

        if ($this->type === ExerciseType::Order) {
            $tokens = [...self::words((string) $this->sentence), ...$this->distractors];
            shuffle($tokens);

            return [...$page, 'sentence' => $this->sentence, 'tokens' => $tokens, 'solutions' => $this->solutions];
        }

        $options = $this->options;
        shuffle($options);

        return [...$page, 'sentence' => $this->sentence, 'options' => $options, 'answer' => $this->options[0]];
    }

    /**
     * The words of a sentence, without the punctuation around them:
     * "Can I take a minute?" becomes ["Can", "I", "take", "a", "minute"].
     *
     * @return list<string>
     */
    public static function words(string $sentence): array
    {
        $words = preg_split('/\s+/u', trim($sentence), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            array_map(fn (string $word) => trim($word, '.,?!;:"“”'), $words),
            fn (string $word) => $word !== '',
        ));
    }

    /**
     * Lower case and straight apostrophes, so "I’m" matches "i'm".
     *
     * @param  array<int, string>  $words
     * @return list<string>
     */
    private static function normalize(array $words): array
    {
        return array_values(array_map(
            fn (string $word) => mb_strtolower(str_replace('’', "'", trim($word))),
            $words,
        ));
    }

    /**
     * @param  list<string>  $words
     * @return list<string>
     */
    private static function sorted(array $words): array
    {
        sort($words);

        return $words;
    }
}
