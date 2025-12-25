<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\ImageStorage\Image;
use Contributte\ImageStorage\ImageStorage;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

final class BasePathIntegrationTest extends TestCase
{

	public function testGetBasePathFromHttpRequest(): void
	{
		$httpRequest = $this->createHttpRequest('/my-app/');

		$storage = new ImageStorage(
			__DIR__ . '/__files__',
			'data',
			__DIR__ . '/__files__',
			'sha1_file',
			'sha1',
			85,
			'fit',
			'noimage/03/no-image.png',
			false,
			$httpRequest
		);

		Assert::equal('/my-app', $storage->getBasePath());
	}

	public function testGetBasePathWithRootPath(): void
	{
		$httpRequest = $this->createHttpRequest('/');

		$storage = new ImageStorage(
			__DIR__ . '/__files__',
			'data',
			__DIR__ . '/__files__',
			'sha1_file',
			'sha1',
			85,
			'fit',
			'noimage/03/no-image.png',
			false,
			$httpRequest
		);

		Assert::equal('', $storage->getBasePath());
	}

	public function testGetBasePathWithNestedPath(): void
	{
		$httpRequest = $this->createHttpRequest('/foo/bar/baz/');

		$storage = new ImageStorage(
			__DIR__ . '/__files__',
			'data',
			__DIR__ . '/__files__',
			'sha1_file',
			'sha1',
			85,
			'fit',
			'noimage/03/no-image.png',
			false,
			$httpRequest
		);

		Assert::equal('/foo/bar/baz', $storage->getBasePath());
	}

	public function testGetBasePathWithoutHttpRequest(): void
	{
		$storage = new ImageStorage(
			__DIR__ . '/__files__',
			'data',
			__DIR__ . '/__files__',
			'sha1_file',
			'sha1',
			85,
			'fit',
			'noimage/03/no-image.png',
			false,
			null
		);

		Assert::equal('', $storage->getBasePath());
	}

	public function testImageCreateLinkIncludesBasePath(): void
	{
		$image = new Image(false, 'data', '/path', 'namespace/47/img.jpg', '/my-app');
		Assert::equal('/my-app/data/namespace/47/img.jpg', $image->createLink());
	}

	public function testImageCreateLinkWithEmptyBasePath(): void
	{
		$image = new Image(false, 'data', '/path', 'namespace/47/img.jpg', '');
		Assert::equal('data/namespace/47/img.jpg', $image->createLink());
	}

	public function testImageCreateLinkWithFriendlyUrl(): void
	{
		$image = new Image(true, 'data', '/path', 'namespace/47/img.jpg', '/my-app');
		$link = $image->createLink();

		Assert::true(str_starts_with($link, '/my-app/data/'));
	}

	public function testFromIdentifierReturnsImageWithBasePath(): void
	{
		$httpRequest = $this->createHttpRequest('/my-app/');

		// Create test image file
		$testDir = __DIR__ . '/__files__/test/ab';
		$testFile = $testDir . '/test.jpg';
		@mkdir($testDir, 0777, true);
		file_put_contents($testFile, 'fake image content');

		try {
			$storage = new ImageStorage(
				__DIR__ . '/__files__',
				'data',
				__DIR__ . '/__files__',
				'sha1_file',
				'sha1',
				85,
				'fit',
				'noimage/03/no-image.png',
				false,
				$httpRequest
			);

			$image = $storage->fromIdentifier('test/ab/test.jpg');

			// The createLink should include basePath
			$link = $image->createLink();
			Assert::true(str_starts_with($link, '/my-app/'), "Link should start with basePath, got: $link");
			Assert::equal('/my-app/data/test/ab/test.jpg', $link);
		} finally {
			// Cleanup
			@unlink($testFile);
			@unlink(__DIR__ . '/__files__/test/ab/test.jpg');
			@rmdir(__DIR__ . '/__files__/test/ab');
			@rmdir(__DIR__ . '/__files__/test');
		}
	}

	public function testFromIdentifierWithoutHttpRequest(): void
	{
		// Create test image file
		$testDir = __DIR__ . '/__files__/test2/cd';
		$testFile = $testDir . '/test2.jpg';
		@mkdir($testDir, 0777, true);
		file_put_contents($testFile, 'fake image content');

		try {
			$storage = new ImageStorage(
				__DIR__ . '/__files__',
				'data',
				__DIR__ . '/__files__',
				'sha1_file',
				'sha1',
				85,
				'fit',
				'noimage/03/no-image.png',
				false,
				null
			);

			$image = $storage->fromIdentifier('test2/cd/test2.jpg');
			$link = $image->createLink();

			// Without httpRequest, basePath should be empty, so link starts with data_dir
			Assert::true(str_starts_with($link, 'data/'), "Link should start with data_dir, got: $link");
			Assert::equal('data/test2/cd/test2.jpg', $link);
		} finally {
			// Cleanup
			@unlink($testFile);
			@unlink(__DIR__ . '/__files__/test2/cd/test2.jpg');
			@rmdir(__DIR__ . '/__files__/test2/cd');
			@rmdir(__DIR__ . '/__files__/test2');
		}
	}

	private function createHttpRequest(string $basePath): Request
	{
		$url = new UrlScript('http://example.com' . $basePath . 'index.php');
		$url = $url->withPath($basePath . 'index.php');

		return new Request($url);
	}

}

(new BasePathIntegrationTest())->run();
