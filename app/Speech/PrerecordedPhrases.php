<?php

namespace App\Speech;

/**
 * Interviewer lines recorded once (php artisan faladev:record-phrases) and
 * served as static MP3 files, so playing them never calls the TTS provider.
 *
 * The file name is a hash of the text and of every voice setting, so editing
 * a line or changing the voice simply points to a file that does not exist
 * yet: pages then show the text only until the line is recorded again.
 */
class PrerecordedPhrases
{
    /**
     * @param  string  $directory  Absolute directory where the files live.
     * @param  string  $publicPath  Same directory, relative to public/ (for URLs).
     * @param  array<int, string|null>  $voiceSettings  Settings that change the audio.
     */
    public function __construct(
        private readonly string $directory,
        private readonly string $publicPath,
        private readonly array $voiceSettings,
    ) {}

    public function fileName(string $text): string
    {
        return substr(sha1((string) json_encode([...$this->voiceSettings, $text])), 0, 16).'.mp3';
    }

    public function has(string $text): bool
    {
        return is_file($this->path($text));
    }

    /**
     * Public URL of the recording, or null when the line was not recorded.
     */
    public function url(string $text): ?string
    {
        return $this->has($text)
            ? asset($this->publicPath.'/'.$this->fileName($text))
            : null;
    }

    public function store(string $text, string $mp3): void
    {
        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }

        file_put_contents($this->path($text), $mp3);
    }

    private function path(string $text): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.$this->fileName($text);
    }
}
