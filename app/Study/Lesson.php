<?php

namespace App\Study;

/**
 * A short lesson of the study track: a handful of exercises on one theme.
 */
final readonly class Lesson
{
    /**
     * @param  list<Exercise>  $exercises
     */
    public function __construct(
        public string $slug,
        public string $unitSlug,
        public string $title,
        public string $description,
        public array $exercises,
        /** Slug of the lesson that must be done first; null for a unit's first lesson. */
        public ?string $previousSlug,
    ) {}

    public function exercise(string $key): ?Exercise
    {
        foreach ($this->exercises as $exercise) {
            if ($exercise->key === $key) {
                return $exercise;
            }
        }

        return null;
    }
}
