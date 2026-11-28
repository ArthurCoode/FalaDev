<?php

namespace App\Enums;

/**
 * What a finished study session was: a track lesson or a review of the
 * phrases due that day.
 */
enum StudySessionKind: string
{
    case Lesson = 'lesson';
    case Review = 'review';
}
