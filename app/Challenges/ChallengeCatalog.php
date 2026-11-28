<?php

namespace App\Challenges;

use App\Enums\ChallengeLanguage;
use InvalidArgumentException;

/**
 * The code challenges, one file per language (resources/challenges/
 * {language}.php), in order, checked when loaded.
 */
final class ChallengeCatalog
{
    /** Every language has exactly this many challenges. */
    public const PER_LANGUAGE = 6;

    /**
     * @param  array<string, Challenge>  $challenges
     */
    private function __construct(private readonly array $challenges) {}

    public static function load(string $directory): self
    {
        $data = [];

        foreach (ChallengeLanguage::cases() as $language) {
            $data[$language->value] = require $directory.DIRECTORY_SEPARATOR.$language->value.'.php';
        }

        return self::fromArray($data);
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $data  entries by language
     */
    public static function fromArray(array $data, ?int $perLanguage = self::PER_LANGUAGE): self
    {
        $challenges = [];

        foreach ($data as $value => $entries) {
            $language = ChallengeLanguage::tryFrom((string) $value)
                ?? throw new InvalidArgumentException("Unknown challenge language [{$value}].");

            if ($perLanguage !== null && count($entries) !== $perLanguage) {
                throw new InvalidArgumentException("{$language->label()} needs exactly {$perLanguage} challenges.");
            }

            foreach ($entries as $entry) {
                $challenge = Challenge::fromArray($entry, $language);

                if (isset($challenges[$challenge->slug])) {
                    throw new InvalidArgumentException("Challenge slug [{$challenge->slug}] is used twice.");
                }

                $challenges[$challenge->slug] = $challenge;
            }
        }

        return new self($challenges);
    }

    /**
     * @return list<Challenge>
     */
    public function all(): array
    {
        return array_values($this->challenges);
    }

    /**
     * @return list<Challenge>
     */
    public function forLanguage(ChallengeLanguage $language): array
    {
        return array_values(array_filter(
            $this->challenges,
            fn (Challenge $challenge) => $challenge->language === $language,
        ));
    }

    public function find(string $slug): ?Challenge
    {
        return $this->challenges[$slug] ?? null;
    }

    /**
     * The next challenge in the same language, if any.
     */
    public function after(Challenge $challenge): ?Challenge
    {
        $same = $this->forLanguage($challenge->language);

        foreach ($same as $index => $candidate) {
            if ($candidate->slug === $challenge->slug) {
                return $same[$index + 1] ?? null;
            }
        }

        return null;
    }
}
