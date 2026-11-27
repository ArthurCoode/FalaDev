<?php

namespace App\Speech\Exceptions;

use RuntimeException;

/**
 * The STT provider could not transcribe the answer. The browser keeps the
 * recording so the user can retry or record again (FalaDev.md, section 8).
 */
class TranscriptionFailed extends RuntimeException {}
