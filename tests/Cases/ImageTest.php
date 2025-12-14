<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\ImageStorage\Image;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

final class ImageTest extends TestCase
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

	public function testCreateLinkWithBasePath(): void
	{
		$image = new Image(false, 'data', '', 'namespace/47/img.jpg', '/my-app');
		Assert::equal('/my-app/data/namespace/47/img.jpg', $image->createLink());
	}

	public function testCreateLinkWithBasePathNested(): void
	{
		$image = new Image(false, 'data/images', '', 'namespace/47/img.jpg', '/my-app');
		Assert::equal('/my-app/data/images/namespace/47/img.jpg', $image->createLink());
	}

	public function testCreateLinkWithEmptyBasePath(): void
	{
		$image = new Image(false, 'data', '', 'namespace/47/img.jpg', '');
		Assert::equal('data/namespace/47/img.jpg', $image->createLink());
	}

}

(new ImageTest())->run();
