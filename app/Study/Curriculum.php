<?php

namespace App\Study;

use InvalidArgumentException;

/**
 * The study track's content (resources/study/curriculum.php), checked when
 * loaded: a broken file fails loudly instead of showing a broken lesson.
 */
final class Curriculum
{
    /** @var array<string, Lesson> */
    private array $lessons = [];

    /** @var array<string, Exercise> */
    private array $exercises = [];

    /**
     * @param  list<Unit>  $units
     */
    private function __construct(public readonly array $units)
    {
        foreach ($units as $unit) {
            foreach ($unit->lessons as $lesson) {
                $this->lessons[$lesson->slug] = $lesson;

                foreach ($lesson->exercises as $exercise) {
                    $this->exercises[$exercise->key] = $exercise;
                }
            }
        }
    }

    public static function load(string $path): self
    {
        return self::fromArray(require $path);
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     */
    public static function fromArray(array $data): self
    {
        $units = [];
        $seen = [];

        foreach ($data as $unitData) {
            $unitSlug = self::slug($unitData['slug'] ?? null, 'Unit', $seen);
            $lessons = [];
            $previous = null;

            foreach ($unitData['lessons'] ?? [] as $lessonData) {
                $lessonSlug = self::slug($lessonData['slug'] ?? null, 'Lesson', $seen);
                $exercises = array_map(
                    fn (array $exercise) => Exercise::fromArray($lessonSlug, $exercise),
                    $lessonData['exercises'] ?? [],
                );
                $keys = array_map(fn (Exercise $exercise) => $exercise->key, $exercises);

                if (count($exercises) === 0) {
                    throw new InvalidArgumentException("Lesson [{$lessonSlug}] has no exercises.");
                }

                if (count(array_unique($keys)) !== count($keys)) {
                    throw new InvalidArgumentException("Lesson [{$lessonSlug}] repeats an exercise id.");
                }

                $lessons[] = new Lesson(
                    slug: $lessonSlug,
                    unitSlug: $unitSlug,
                    title: self::text($lessonData, 'title', $lessonSlug),
                    description: self::text($lessonData, 'description', $lessonSlug),
                    exercises: array_values($exercises),
                    previousSlug: $previous,
                );
                $previous = $lessonSlug;
            }

            if ($lessons === []) {
                throw new InvalidArgumentException("Unit [{$unitSlug}] has no lessons.");
            }

            $units[] = new Unit(
                slug: $unitSlug,
                title: self::text($unitData, 'title', $unitSlug),
                description: self::text($unitData, 'description', $unitSlug),
                lessons: $lessons,
            );
        }

        return new self($units);
    }

    public function lesson(string $slug): ?Lesson
    {
        return $this->lessons[$slug] ?? null;
    }

    public function exercise(string $key): ?Exercise
    {
        return $this->exercises[$key] ?? null;
    }

    /**
     * The lesson that comes after this one in its unit, if any.
     */
    public function nextLesson(Lesson $lesson): ?Lesson
    {
        foreach ($this->lessons as $candidate) {
            if ($candidate->previousSlug === $lesson->slug) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<string, Lesson>
     */
    public function lessons(): array
    {
        return $this->lessons;
    }

    /**
     * @param  array<string, true>  $seen  slugs already used, units and lessons alike
     */
    private static function slug(mixed $slug, string $what, array &$seen): string
    {
        if (! is_string($slug) || ! preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new InvalidArgumentException("{$what} needs a slug made of a-z, 0-9 and dashes.");
        }

        if (isset($seen[$slug])) {
            throw new InvalidArgumentException("Slug [{$slug}] is used twice.");
        }

        $seen[$slug] = true;

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function text(array $data, string $field, string $slug): string
    {
        $value = trim((string) ($data[$field] ?? ''));

        if ($value === '') {
            throw new InvalidArgumentException("[{$slug}] needs a {$field}.");
        }

        return $value;
    }
}
