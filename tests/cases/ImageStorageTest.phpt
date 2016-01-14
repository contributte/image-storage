<?php

namespace Ublaboo\Mailing\Tests\Cases;

use Tester\TestCase,
	Tester\Assert,
	Mockery,
	Nette,
	Ublaboo\ImageStorage\ImageStorage;

require __DIR__ . '/../bootstrap.php'; 

final class ImageStorageTest extends TestCase
{

	private $storage;
	private $storage2;

	private $image_content = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAIAAAAC64paAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4AENCRcy1x+u1gAAAB1pVFh0Q29tbWVudAAAAAAAQ3JlYXRlZCB3aXRoIEdJTVBkLmUHAAACFElEQVQ4y52T226jMBCGx/EYDMYhBBSkKu//Vr1pJapWSkisOgEfYC9GpWkarVY7Vwjp8+g/DJvnGf53Vg//zvP8L4/ib2aaJiIZY6vVijHGGPsbTEwIIcYYQlhgROScIyK98gCe5zmE4Jwbx3EcR+dcjBEAOOdJkqRpmqZpkiSIeMcjkcMwXK9Xa+3n56e11nsPAEIIpVRRFEqpaZqklHc8xhjHcbTWGmOOx2Pf9+fz+XK5AECe52VZVlW13W6naSLxnPOFR1prrT0ej29vb13Xvb+/W2sBQCnVtu3T0xNJQMQ78ei9H4bhfD5/fHy8vLw8Pz/fwqfTKYRwK574782k9nQ6dV33+vradZ0xBgDW67VzLsuyqqqqqrper0opipOWI2VzuVz6vjfGGGMOh0Pf9wDgvS+KwhjT933bthTkNE2PS0KBOeeGYQAA+g4h/K1h1CRElFIqpbTWBGitlVKU0OOSIGKSJFrrpmnquj4cDs65PM8J3m63dV03TaO1pp78cBsRsyxTSm02m/1+H0IoimJxe7fb7ff7zWajlMqy7NZqAEAhhJSyLEuqZJqmdV0vcNM0u92ubduyLKWUQogfMEklGxljeZ7XdU2GSSm11uv1uizLPM8f1JMKsJwBVXI5DFKUZZmUMkkSzvmtYYyuL8bovffeUzYLTHYKIYQQd+Q3DADT18QYl3vmnK++5nfOfwAzhFZqBAJIcgAAAABJRU5ErkJggg==';


	public function setUp()
	{
		$this->storage = new ImageStorage(
			__DIR__ . '/../data',
			'data',
			'sha1_file',
			'sha1',
			2,
			'fit',
			'n/aa/s.jpg',
			FALSE
		);

		/*self::recursiveRemove(__DIR__ . '/../data/images');*/
	}


	/*public function tearDown()
	{
		self::recursiveRemove(__DIR__ . '/../data/images');
	}*/


	public static function recursiveRemove($path) {

        $iterator = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
    }


	public function testDelete()
	{
		$files = __DIR__ . '/../data/files';
		@mkdir($files . '/a', 0777, TRUE);

		$file_array = [
			"{$files}/a/kitty.100x100.fit.q85.jpg",
			"{$files}/a/kitty.100x200.fit.q85.jpg",
			"{$files}/a/kitty.100x200.exact.q85.jpg",
			"{$files}/a/kitty.100x200.shrink_only.q85.jpg",
			"{$files}/a/kitty.100x200.fill.q1.jpg",
			"{$files}/a/kitty.100x200.stretch.q85.jpg",
			"{$files}/a/kitty.100x200.fill.q10.jpg",
			"{$files}/a/kitty.200x200crop100x150x100x100.fit.q85.jpg",
			"{$files}/a/kitty.100x200.fill.q100.jpg",
			"{$files}/a/kitty.20x20.fit.q85.jpg",
			"{$files}/a/kitty.100x200.fill.q85.jpg",
			"{$files}/a/kitty.jpg"
		];

		foreach ($file_array as $name) {
			touch($name);
		}

		$this->storage->delete("files/a/kitty.jpg");

		foreach ($file_array as $name) {
			Assert::falsey(file_exists($name));
		}
	}


	/*public function testUpload()
	{
		$files = __DIR__ . '/../data/files';
		$tmp_image_path = $files . '/tmp.jpg';
		file_put_contents($tmp_image_path, base64_encode($this->image_content));

		$upload = new Nette\Http\FileUpload([
			'name' => 'img.jpg',
			'type' => 'image/jpg',
			'size' => '20',
			'tmp_name' => $tmp_image_path,
			'error' => 0
		]);

		$this->storage->saveUpload($upload, 'images');

		Assert::truthy(file_exists($files . '/../images/c3/img.jpg'));
	}


	public function testContent()
	{
		$files = __DIR__ . '/../data/files';

		$this->storage->saveContent(base64_encode($this->image_content), 'img2.jpg', 'images');
		Assert::truthy(file_exists($files . '/../images/c3/img2.jpg'));

		$this->storage->saveContent(base64_encode($this->image_content), 'img2.jpg', 'images');
		Assert::truthy(file_exists($files . '/../images/c3/img2.2.jpg'));
	}*/

}


$test_case = new ImageStorageTest;
$test_case->run();
