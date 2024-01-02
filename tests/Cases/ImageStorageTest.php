<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\ImageStorage\ImageStorage;
use Exception;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

final class ImageStorageTest extends TestCase
{

	private ImageStorage $storage;

	public function setUp(): void
	{
		$this->storage = new ImageStorage(
			__DIR__ . '/__files__',
			'data',
			__DIR__ . '/__files__',
			'sha1_file',
			'sha1',
			2,
			'fit',
			'n/aa/s.jpg',
			false
		);
	}

	public function tearDown(): void
	{
		$path = __DIR__ . '/__files__/images';

		if (!file_exists($path)) {
			return;
		}

		$iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
		$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($files as $file) {
			if ($file->isDir()) {
				rmdir($file->getRealPath());
			} else {
				unlink($file->getRealPath());
			}
		}
	}

	public function testDelete(): void
	{
		$files = __DIR__ . '/__files__/files';
		@mkdir($files . '/49', 0777, true);

		$file_array = [
			sprintf('%s/49/kitty.100x100.fit.q85.jpg', $files),
			sprintf('%s/49/kitty.100x200.fit.q85.jpg', $files),
			sprintf('%s/49/kitty.100x200.exact.q85.jpg', $files),
			sprintf('%s/49/kitty.100x200.shrink_only.q85.jpg', $files),
			sprintf('%s/49/kitty.100x200.fill.q1.jpg', $files),
			sprintf('%s/49/kitty.100x200.stretch.q85.jpg', $files),
			sprintf('%s/49/kitty.100x200.fill.q10.jpg', $files),
			sprintf('%s/49/kitty.200x200crop100x150x100x100.fit.q85.jpg', $files),
			sprintf('%s/49/kitty.100x200.fill.q100.jpg', $files),
			sprintf('%s/49/kitty.20x20.fit.q85.jpg', $files),
			sprintf('%s/49/kitty.100x200.fill.q85.jpg', $files),
			sprintf('%s/49/kitty.jpg', $files),
		];

		foreach ($file_array as $name) {
			touch($name);
		}

		$this->storage->delete('files/49/kitty.jpg');

		foreach ($file_array as $name) {
			Assert::falsey(file_exists($name));
		}

		foreach ($file_array as $name) {
			touch($name);
		}

		$this->storage->delete('files/49/kitty.jpg', true);

		$originalImage = array_pop($file_array);
		foreach ($file_array as $name) {
			Assert::falsey(file_exists($name));
		}

		Assert::truthy(file_exists($originalImage));
	}

	public function testUpload(): void
	{
		$files = __DIR__ . '/__files__/files';
		$tempImagePath = $files . '/tmp.jpg';

		$imageContent = Image::fromBlank(1, 1)->toString();

		$this->saveTempImage($tempImagePath, $imageContent);

		$upload = new FileUpload([
			'name' => 'upload.jpg',
			'type' => 'image/jpg',
			'size' => '20',
			'tmp_name' => $tempImagePath,
			'error' => 0,
		]);

		$this->storage->saveUpload($upload, 'images');

		$prefix = $this->getPrefixFromContent($imageContent);

		$savedImage = sprintf(
			'%s/../images/%s/upload.jpg',
			$files,
			$prefix
		);
		Assert::truthy(file_exists($savedImage));
	}

	public function testSaveContent(): void
	{
		$imageFileName = 'content.jpg';
		$files = __DIR__ . '/__files__/files';

		$imageContent = Image::fromBlank(1, 1)->toString();

		$prefix = $this->getPrefixFromContent($imageContent);

		$this->storage->saveContent($imageContent, $imageFileName, 'images');
		$savedImage = sprintf(
			'%s/../images/%s/content.jpg',
			$files,
			$prefix
		);
		Assert::truthy(file_exists($savedImage));

		$this->storage->saveContent($imageContent, $imageFileName, 'images');
		$savedImageCopy = sprintf(
			'%s/../images/%s/content.2.jpg',
			$files,
			$prefix
		);
		Assert::truthy(file_exists($savedImageCopy));
	}

	private function getPrefixFromContent(string $imageContent): string
	{
		return substr(sha1($imageContent), 0, 2);
	}

	/**
	 * @throws Exception
	 */
	private function saveTempImage(string $path, string $content): void
	{
		$result = file_put_contents($path, $content);

		if ($result === false) {
			throw new Exception('Unable to save temporary test image!');
		}
	}

}

(new ImageStorageTest())->run();
