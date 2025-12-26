<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\ImageStorage\ImageStorage;
use Contributte\ImageStorage\Latte\LatteExtension;
use Latte\Engine;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

final class LatteIntegrationTest extends TestCase
{

	private string $tempDir;

	public function setUp(): void
	{
		$this->tempDir = __DIR__ . '/__temp__';
		@mkdir($this->tempDir, 0777, true);
	}

	public function tearDown(): void
	{
		// Clean up temp files
		$files = glob($this->tempDir . '/*');
		if ($files !== false) {
			foreach ($files as $file) {
				@unlink($file);
			}
		}
	}

	public function testLatteImgTagIncludesBasePath(): void
	{
		$httpRequest = $this->createHttpRequest('/my-app/');
		$storage = $this->createImageStorage($httpRequest);

		// Create test image
		$this->createTestImage('test/ab/photo.jpg');

		try {
			$latte = $this->createLatteEngine();

			$output = $latte->renderToString(
				__DIR__ . '/__templates__/image-test.latte',
				[
					'imageStorage' => $storage,
					'basePath' => '/my-app',
					'baseUrl' => 'http://example.com/my-app',
					'identifier' => 'test/ab/photo.jpg',
				]
			);

			// Verify img tag contains basePath
			Assert::contains('/my-app/data/test/ab/photo.jpg', $output);
			Assert::contains('<img src="/my-app/data/test/ab/photo.jpg">', $output);
		} finally {
			$this->cleanupTestImage('test/ab/photo.jpg');
		}
	}

	public function testLatteImgTagWithRootBasePath(): void
	{
		$httpRequest = $this->createHttpRequest('/');
		$storage = $this->createImageStorage($httpRequest);

		$this->createTestImage('test/cd/image.jpg');

		try {
			$latte = $this->createLatteEngine();

			$output = $latte->renderToString(
				__DIR__ . '/__templates__/image-test.latte',
				[
					'imageStorage' => $storage,
					'basePath' => '',
					'baseUrl' => 'http://example.com',
					'identifier' => 'test/cd/image.jpg',
				]
			);

			// With root basePath, link should start with data_dir directly
			Assert::contains('data/test/cd/image.jpg', $output);
			Assert::contains('<img src="data/test/cd/image.jpg">', $output);
		} finally {
			$this->cleanupTestImage('test/cd/image.jpg');
		}
	}

	public function testLatteImgLinkIncludesBasePath(): void
	{
		$httpRequest = $this->createHttpRequest('/subdir/app/');
		$storage = $this->createImageStorage($httpRequest);

		$this->createTestImage('gallery/ef/pic.jpg');

		try {
			$latte = $this->createLatteEngine();

			$output = $latte->renderToString(
				__DIR__ . '/__templates__/image-test.latte',
				[
					'imageStorage' => $storage,
					'basePath' => '/subdir/app',
					'baseUrl' => 'http://example.com/subdir/app',
					'identifier' => 'gallery/ef/pic.jpg',
				]
			);

			// imgLink should output the full path with basePath
			Assert::contains('/subdir/app/data/gallery/ef/pic.jpg', $output);
		} finally {
			$this->cleanupTestImage('gallery/ef/pic.jpg');
		}
	}

	public function testLatteNImgAttributeIncludesBasePath(): void
	{
		$httpRequest = $this->createHttpRequest('/my-app/');
		$storage = $this->createImageStorage($httpRequest);

		$this->createTestImage('users/gh/avatar.jpg');

		try {
			$latte = $this->createLatteEngine();

			$output = $latte->renderToString(
				__DIR__ . '/__templates__/image-test.latte',
				[
					'imageStorage' => $storage,
					'basePath' => '/my-app',
					'baseUrl' => 'http://example.com/my-app',
					'identifier' => 'users/gh/avatar.jpg',
				]
			);

			// n:img attribute should include basePath
			Assert::contains('src="/my-app/data/users/gh/avatar.jpg"', $output);
			Assert::contains('alt="test"', $output);
		} finally {
			$this->cleanupTestImage('users/gh/avatar.jpg');
		}
	}

	private function createLatteEngine(): Engine
	{
		$latte = new Engine();
		$latte->setTempDirectory($this->tempDir);
		$latte->addExtension(new LatteExtension());

		return $latte;
	}

	private function createImageStorage(?Request $httpRequest): ImageStorage
	{
		return new ImageStorage(
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
	}

	private function createHttpRequest(string $basePath): Request
	{
		$url = new UrlScript('http://example.com' . $basePath . 'index.php');
		$url = $url->withPath($basePath . 'index.php');

		return new Request($url);
	}

	private function createTestImage(string $identifier): void
	{
		$path = __DIR__ . '/__files__/' . $identifier;
		$dir = dirname($path);
		@mkdir($dir, 0777, true);
		file_put_contents($path, 'fake image content');
	}

	private function cleanupTestImage(string $identifier): void
	{
		$basePath = __DIR__ . '/__files__/';
		$filePath = $basePath . $identifier;
		$dataFilePath = $basePath . $identifier;

		@unlink($filePath);
		@unlink($dataFilePath);

		// Clean up directories
		$parts = explode('/', $identifier);
		array_pop($parts); // Remove filename

		while (count($parts) > 0) {
			$dir = $basePath . implode('/', $parts);
			@rmdir($dir);
			array_pop($parts);
		}
	}

}

(new LatteIntegrationTest())->run();
