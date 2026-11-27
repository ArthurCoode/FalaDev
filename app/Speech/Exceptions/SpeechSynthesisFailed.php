<?php

namespace App\Speech\Exceptions;

use RuntimeException;

/**
 * The TTS provider could not produce audio. Callers must let the user read
 * the text on screen and retry (cursor/skills/FalaDev.md, section 8).
 */
class SpeechSynthesisFailed extends RuntimeException {}
