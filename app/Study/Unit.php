<?php

namespace App\Study;

/**
 * A group of lessons on one subject. A unit's lessons are done in order.
 */
final readonly class Unit
{
    /**
     * @param  list<Lesson>  $lessons
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $description,
        public array $lessons,
    ) {}
}
