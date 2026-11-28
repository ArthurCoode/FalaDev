<?php

namespace App\Challenges\Running;

/**
 * A minimal ustar archive writer. The program goes to the container on
 * stdin as a tar archive, so nothing is written to the host's disk and no
 * folder is shared with the container.
 */
final class Tar
{
    /**
     * @param  array<string, string>  $files  name => contents (flat names only)
     */
    public static function pack(array $files): string
    {
        $archive = '';

        foreach ($files as $name => $contents) {
            $archive .= self::header($name, strlen($contents));
            $archive .= $contents.str_repeat("\0", (512 - strlen($contents) % 512) % 512);
        }

        return $archive.str_repeat("\0", 1024);
    }

    private static function header(string $name, int $size): string
    {
        $header = str_pad($name, 100, "\0")
            .str_pad('0000644', 7, '0', STR_PAD_LEFT)."\0"
            .'0000000'."\0"
            .'0000000'."\0"
            .str_pad(decoct($size), 11, '0', STR_PAD_LEFT)."\0"
            .str_pad(decoct(1_700_000_000), 11, '0', STR_PAD_LEFT)."\0"
            .str_repeat(' ', 8)
            .'0'
            .str_repeat("\0", 100)
            ."ustar\0".'00'
            .str_pad('faladev', 32, "\0")
            .str_pad('faladev', 32, "\0")
            .str_repeat("\0", 8 + 8 + 155 + 12);

        $checksum = array_sum(array_map('ord', str_split($header)));

        return substr_replace($header, str_pad(decoct($checksum), 6, '0', STR_PAD_LEFT)."\0 ", 148, 8);
    }
}
