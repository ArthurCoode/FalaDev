<?php

namespace App\Study;

use Carbon\CarbonImmutable;

/**
 * The current study day, in config('faladev.study.timezone'): a lesson
 * done at 23:30 in São Paulo counts for that day, not for the next one in UTC.
 */
final class StudyClock
{
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now()
            ->setTimezone((string) config('faladev.study.timezone'))
            ->startOfDay();
    }
}
