<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\ImageStorage\ImageStorage;
use ReflectionMethod;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/**
 * Test format-specific quality settings.
 *
 * Each format has its own quality/compression setting:
 * - JPEG: 0-100 (higher = better quality)
 * - PNG: 0-9 (compression level: 0 = no compression, 9 = max compression)
 * - WEBP: 0-100 (higher = better quality)
 * - AVIF: 0-100 (higher = better quality)
 * - GIF: null (not applicable)
 */
final class QualityConversionTest extends TestCase
{

	private ReflectionMethod $getQualityMethod;

	private ImageStorage $storage;

	public function setUp(): void
	{
		$storage = new ImageStorage(
			__DIR__ . '/__files__',
			'data',
			__DIR__ . '/__files__',
			'sha1_file',
			'sha1',
			['jpeg' => 85, 'png' => 6, 'webp' => 80, 'avif' => 30, 'gif' => null],
			'fit',
			'n/aa/s.jpg',
			false
		);

		// Access private method for testing
		$this->getQualityMethod = new ReflectionMethod(ImageStorage::class, 'getQualityForFormat');
		$this->storage = $storage;
	}

	public function testJpegQuality(): void
	{
		// JPEG should return configured value (85)
		Assert::same(85, $this->getQuality('jpg'));
		Assert::same(85, $this->getQuality('jpeg'));
		Assert::same(85, $this->getQuality('JPG'));
		Assert::same(85, $this->getQuality('JPEG'));
	}

	public function testPngQuality(): void
	{
		// PNG should return configured compression level (6)
		Assert::same(6, $this->getQuality('png'));
		Assert::same(6, $this->getQuality('PNG'));
	}

	public function testWebpQuality(): void
	{
		// WEBP should return configured value (80)
		Assert::same(80, $this->getQuality('webp'));
		Assert::same(80, $this->getQuality('WEBP'));
	}

	public function testAvifQuality(): void
	{
		// AVIF should return configured value (30)
		Assert::same(30, $this->getQuality('avif'));
		Assert::same(30, $this->getQuality('AVIF'));
	}

	public function testGifQuality(): void
	{
		// GIF should return null (quality not applicable)
		Assert::null($this->getQuality('gif'));
		Assert::null($this->getQuality('GIF'));
	}

	public function testUnknownFormatFallsBackToJpeg(): void
	{
		// Unknown formats should fall back to JPEG quality
		Assert::same(85, $this->getQuality('bmp'));
		Assert::same(85, $this->getQuality('tiff'));
	}

	public function testCustomQualityConfiguration(): void
	{
		// Test with custom quality settings
		$storage = new ImageStorage(
			__DIR__ . '/__files__',
			'data',
			__DIR__ . '/__files__',
			'sha1_file',
			'sha1',
			['jpeg' => 95, 'png' => 2, 'webp' => 90, 'avif' => 50, 'gif' => null],
			'fit',
			'n/aa/s.jpg',
			false
		);

		$method = new ReflectionMethod(ImageStorage::class, 'getQualityForFormat');

		Assert::same(95, $method->invoke($storage, 'jpg'));
		Assert::same(2, $method->invoke($storage, 'png'));
		Assert::same(90, $method->invoke($storage, 'webp'));
		Assert::same(50, $method->invoke($storage, 'avif'));
	}

	private function getQuality(string $extension): ?int
	{
		return $this->getQualityMethod->invoke($this->storage, $extension);
	}

}

(new QualityConversionTest())->run();
