<?php

namespace App\Faker;

class PlaceHoldImageFakerProvider
{
    public function imageUrl($width = 640, $height = 480, $category = null, $randomize = true, $word = null, $gray = false, string $format = 'png')
    {
        $background_color = str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        $foreground_color = str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);

        $image = "https://placehold.co/{$width}x{$height}/{$background_color}/{$foreground_color}";

        if ($word) {
            $image .= "?text={$word}";
        }

        return $image;
    }
}