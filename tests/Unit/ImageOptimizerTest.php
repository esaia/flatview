<?php

namespace Tests\Unit;

use IrepPlugin\FilamentIrep\Support\ImageOptimizer;
use Tests\TestCase;

class ImageOptimizerTest extends TestCase
{
    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_it_only_converts_raster_image_types(): void
    {
        $this->assertTrue(ImageOptimizer::isConvertible('image/jpeg'));
        $this->assertTrue(ImageOptimizer::isConvertible('image/png'));

        // Rasterizing these would lose vector quality, animation, or the file itself.
        $this->assertFalse(ImageOptimizer::isConvertible('image/svg+xml'));
        $this->assertFalse(ImageOptimizer::isConvertible('image/gif'));
        $this->assertFalse(ImageOptimizer::isConvertible('application/pdf'));
        $this->assertFalse(ImageOptimizer::isConvertible('video/mp4'));
        $this->assertFalse(ImageOptimizer::isConvertible(null));
    }

    public function test_it_encodes_to_webp_and_downscales_oversized_images(): void
    {
        $source = $this->makeJpeg(4000, 2000);

        $binary = ImageOptimizer::toWebp($source);

        $this->assertNotNull($binary);

        $out = $this->tempPath('webp');
        file_put_contents($out, $binary);

        [$width, $height, $type] = getimagesize($out);

        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertSame(ImageOptimizer::MAX_DIMENSION, $width);
        $this->assertSame(1280, $height, 'Aspect ratio should be preserved.');
        $this->assertLessThan(filesize($source), strlen($binary));
    }

    public function test_it_never_upscales_small_images(): void
    {
        $binary = ImageOptimizer::toWebp($this->makeJpeg(320, 240));

        $this->assertNotNull($binary);

        $out = $this->tempPath('webp');
        file_put_contents($out, $binary);

        $this->assertSame([320, 240], array_slice(getimagesize($out), 0, 2));
    }

    public function test_it_returns_null_for_unreadable_files(): void
    {
        $this->assertNull(ImageOptimizer::toWebp('/does/not/exist.jpg'));
    }

    private function makeJpeg(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);

        // Noise, so the encoder cannot compress it down to nothing.
        for ($i = 0; $i < 400; $i++) {
            imagefilledrectangle(
                $image,
                random_int(0, $width - 1), random_int(0, $height - 1),
                random_int(0, $width - 1), random_int(0, $height - 1),
                imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255)),
            );
        }

        $path = $this->tempPath('jpg');
        imagejpeg($image, $path, 95);
        imagedestroy($image);

        return $path;
    }

    private function tempPath(string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imgopt').'.'.$extension;

        return $this->tempFiles[] = $path;
    }
}
