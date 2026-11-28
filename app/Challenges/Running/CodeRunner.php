<?php

namespace App\Challenges\Running;

/**
 * Runs a test program somewhere isolated and reports what it printed.
 */
interface CodeRunner
{
    public function execute(Program $program): Execution;
}
