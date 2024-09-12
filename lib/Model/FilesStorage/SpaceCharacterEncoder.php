<?php

namespace FilesStorage;

use Matecat\SimpleS3\Components\Encoders\SafeNameEncoderInterface;

class SpaceCharacterEncoder implements SafeNameEncoderInterface
{
    /**
     * @param string $string
     *
     * @return string
     */
    public function decode($string): string
    {
        $decoded = [];

        foreach (explode(DIRECTORY_SEPARATOR, $string) as $word) {
            $word = str_replace('+', ' ', $word);
            $decoded[] = $word;
        }

        return implode(DIRECTORY_SEPARATOR, $decoded);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function encode($string): string
    {
        $encoded = [];

        foreach (explode(DIRECTORY_SEPARATOR, $string) as $word) {
            $word = str_replace(' ', '+', $word);
            $encoded[] = $word;
        }

        return implode(DIRECTORY_SEPARATOR, $encoded);
    }
}