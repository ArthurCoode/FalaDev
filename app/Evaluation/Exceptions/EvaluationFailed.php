<?php

namespace App\Evaluation\Exceptions;

use RuntimeException;

/**
 * The report could not be produced. The interview and its transcript stay
 * saved and the user can retry (cursor/skills/FalaDev.md, section 5C).
 */
class EvaluationFailed extends RuntimeException {}
