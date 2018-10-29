<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\ImageStorage\ImageStorage;
use Ninjify\Nunjuck\TestCase\BaseTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

final class ImageStorageTest extends BaseTestCase
{

	/** @var ImageStorage */
	private $storage;

	public function setUp(): void
	{
		$this->storage = new ImageStorage(
			__DIR__ . '/../data',
			'data',
			'sha1_file',
			'sha1',
			2,
			'fit',
			'n/aa/s.jpg',
			false
		);
	}


	public static function recursiveRemove(string $path): void
	{
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
		$files = __DIR__ . '/../data/files';
		@mkdir($files . '/a', 0777, true);

		$file_array = [
			sprintf('%s/a/kitty.100x100.fit.q85.jpg', $files),
			sprintf('%s/a/kitty.100x200.fit.q85.jpg', $files),
			sprintf('%s/a/kitty.100x200.exact.q85.jpg', $files),
			sprintf('%s/a/kitty.100x200.shrink_only.q85.jpg', $files),
			sprintf('%s/a/kitty.100x200.fill.q1.jpg', $files),
			sprintf('%s/a/kitty.100x200.stretch.q85.jpg', $files),
			sprintf('%s/a/kitty.100x200.fill.q10.jpg', $files),
			sprintf('%s/a/kitty.200x200crop100x150x100x100.fit.q85.jpg', $files),
			sprintf('%s/a/kitty.100x200.fill.q100.jpg', $files),
			sprintf('%s/a/kitty.20x20.fit.q85.jpg', $files),
			sprintf('%s/a/kitty.100x200.fill.q85.jpg', $files),
			sprintf('%s/a/kitty.jpg', $files),
		];

		foreach ($file_array as $name) {
			touch($name);
		}

		$this->storage->delete('files/a/kitty.jpg');

		foreach ($file_array as $name) {
			Assert::falsey(file_exists($name));
		}
	}

}


$test_case = new ImageStorageTest();
$test_case->run();
