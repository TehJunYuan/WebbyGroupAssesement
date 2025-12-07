<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BookCoverImageSize implements ValidationRule
{
    const STANDARD_WIDTH = 600;
    const STANDARD_HEIGHT = 900;
    const ASPECT_RATIO = 2 / 3;
    const TOLERANCE = 0.1;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return;
        }

        $path = $value->getRealPath();
        $imageInfo = getimagesize($path);

        if (!$imageInfo) {
            $fail('The :attribute must be a valid image file.');
            return;
        }

        [$width, $height] = $imageInfo;
        $aspectRatio = $width / $height;
        $expectedAspectRatio = self::ASPECT_RATIO;

        $minAspectRatio = $expectedAspectRatio * (1 - self::TOLERANCE);
        $maxAspectRatio = $expectedAspectRatio * (1 + self::TOLERANCE);

        if ($aspectRatio < $minAspectRatio || $aspectRatio > $maxAspectRatio) {
            $fail('The :attribute must have an aspect ratio of approximately 2:3 (width:height). Current ratio is ' . round($aspectRatio, 2) . ':1');
        }

        if ($width < 300 || $height < 450) {
            $fail('The :attribute must be at least 300x450 pixels. Current size is ' . $width . 'x' . $height);
        }
    }
}

