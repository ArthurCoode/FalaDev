<?php

namespace App\Enums;

/**
 * What a finished study session was: a track lesson, a review of the
 * phrases due that day or a solved code challenge.
 */
enum StudySessionKind: string
{
    case Lesson = 'lesson';
    case Review = 'review';
    case Challenge = 'challenge';
}
