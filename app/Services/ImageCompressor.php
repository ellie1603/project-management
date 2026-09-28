<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;

/**
 * Re-encodes site photos as small JPEGs on the server, so storage stays small
 * even when the browser could not shrink the photo first.
 */
class ImageCompressor
{
    public const MAX_EDGE = 1600;

    public const QUALITY = 75;

    /**
     * @return string|null JPEG bytes, or null when the file is not a GD-readable image.
     */
    public function toJpeg(UploadedFile $file): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $image = match ($file->getMimeType()) {
            'image/jpeg' => @imagecreatefromjpeg($file->getRealPath()),
            'image/png' => @imagecreatefrompng($file->getRealPath()),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file->getRealPath()) : false,
            default => false,
        };

        if (! $image instanceof GdImage) {
            return null;
        }

        $image = $this->applyExifOrientation($image, $file);
        $image = $this->scaleDown($image);

        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($canvas, null, self::QUALITY);
        $jpeg = (string) ob_get_clean();

        $original = (int) $file->getSize();

        return $original > 0 && strlen($jpeg) >= $original && $file->getMimeType() === 'image/jpeg' ? null : $jpeg;
    }

    private function scaleDown(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, self::MAX_EDGE / max($width, $height));

        if ($scale >= 1) {
            return $image;
        }

        return imagescale($image, (int) round($width * $scale), (int) round($height * $scale), IMG_BICUBIC) ?: $image;
    }

    private function applyExifOrientation(GdImage $image, UploadedFile $file): GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1);

        return match ($orientation) {
            3 => imagerotate($image, 180, 0) ?: $image,
            6 => imagerotate($image, -90, 0) ?: $image,
            8 => imagerotate($image, 90, 0) ?: $image,
            default => $image,
        };
    }
}
