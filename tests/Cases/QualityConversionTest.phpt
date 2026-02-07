<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\ImageStorage\ImageStorage;
use Contributte\Tester\Toolkit;
use ReflectionMethod;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

Toolkit::test(static function (): void {
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

	$method = new ReflectionMethod(ImageStorage::class, 'getQualityForFormat');

	// JPEG should return configured value (85)
	Assert::same(85, $method->invoke($storage, 'jpg'));
	Assert::same(85, $method->invoke($storage, 'jpeg'));
	Assert::same(85, $method->invoke($storage, 'JPG'));
	Assert::same(85, $method->invoke($storage, 'JPEG'));
});

Toolkit::test(static function (): void {
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

	$method = new ReflectionMethod(ImageStorage::class, 'getQualityForFormat');

	// PNG should return configured compression level (6)
	Assert::same(6, $method->invoke($storage, 'png'));
	Assert::same(6, $method->invoke($storage, 'PNG'));

	// WEBP should return configured value (80)
	Assert::same(80, $method->invoke($storage, 'webp'));
	Assert::same(80, $method->invoke($storage, 'WEBP'));

	// AVIF should return configured value (30)
	Assert::same(30, $method->invoke($storage, 'avif'));
	Assert::same(30, $method->invoke($storage, 'AVIF'));

	// GIF should return null (quality not applicable)
	Assert::null($method->invoke($storage, 'gif'));
	Assert::null($method->invoke($storage, 'GIF'));

	// Unknown formats should fall back to JPEG quality
	Assert::same(85, $method->invoke($storage, 'bmp'));
	Assert::same(85, $method->invoke($storage, 'tiff'));
});

Toolkit::test(static function (): void {
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
});
