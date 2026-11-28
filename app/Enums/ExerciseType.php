<?php

namespace App\Enums;

/**
 * Kinds of exercise in the study track (resources/study/curriculum.php).
 */
enum ExerciseType: string
{
    /** Pick the right option. */
    case Choice = 'choice';

    /** Pick the word that fills the "___" in a sentence. */
    case Gap = 'gap';

    /** Build a sentence by putting words in order. */
    case Order = 'order';
}
