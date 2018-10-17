<?php

namespace Ublaboo\Mailing\Tests\Cases;

use Tester\Assert;
use Tester\TestCase;
use Ublaboo\ImageStorage\Image;

require __DIR__ . '/../bootstrap.php';

final class ImageTest extends TestCase
{

	public function testGetPath()
	{
		$image = new Image(false, '', '/data', 'namespace/47/img.jpg');
		Assert::equal('/data/namespace/47/img.jpg', $image->getPath());
	}

	public function testGetPathNested()
	{
		$image = new Image(false, '', '/data/images', 'namespace/47/img.jpg');
		Assert::equal('/data/images/namespace/47/img.jpg', $image->getPath());
	}

	public function testCreateLink()
	{
		$image = new Image(false, 'data', '', 'namespace/47/img.jpg');
		Assert::equal('data/namespace/47/img.jpg', $image->createLink());
	}

	public function testCreateLinkNested()
	{
		$image = new Image(false, 'data/images', '', 'namespace/47/img.jpg');
		Assert::equal('data/images/namespace/47/img.jpg', $image->createLink());
	}
}

$test_case = new ImageTest();
$test_case->run();
