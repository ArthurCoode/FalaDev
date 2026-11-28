<?php

namespace App\Challenges\Running;

/**
 * Used when running code is turned off (FALADEV_CODE_RUNNER=disabled): every
 * run answers "unavailable".
 */
class DisabledCodeRunner implements CodeRunner
{
    public function execute(Program $program): Execution
    {
        return Execution::unavailable('Running code is turned off.');
    }
}
