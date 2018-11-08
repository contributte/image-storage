<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\ImageStorage\Image;
use Ninjify\Nunjuck\TestCase\BaseTestCase;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

final class ImageTest extends BaseTestCase
{

	public function testGetPath(): void
	{
		$image = new Image(false, '', '/data', '/namespace/47/img.jpg');
		Assert::equal('/data/namespace/47/img.jpg', $image->getPath());
	}

	public function testGetPathNested(): void
	{
		$image = new Image(false, '', '/data/images', 'namespace/47/img.jpg');
		Assert::equal('/data/images/namespace/47/img.jpg', $image->getPath());
	}

	public function testCreateLink(): void
	{
		$image = new Image(false, 'data', '', 'namespace/47/img.jpg');
		Assert::equal('data/namespace/47/img.jpg', $image->createLink());
	}

	public function testCreateLinkNested(): void
	{
		$image = new Image(false, 'data/images', '', 'namespace/47/img.jpg');
		Assert::equal('data/images/namespace/47/img.jpg', $image->createLink());
	}

}

(new ImageTest())->run();
