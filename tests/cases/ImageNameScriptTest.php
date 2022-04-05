<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\ImageStorage\ImageNameScript;
use Ninjify\Nunjuck\TestCase\BaseTestCase;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

final class ImageNameScriptTest extends BaseTestCase
{

	public function testFromName(): void
	{
		$s = ImageNameScript::fromName('images/49/kitty.100x200.fill.q100.jpg');

		Assert::same($s->original, 'images/49/kitty.jpg');
		Assert::same($s->namespace, 'images');
		Assert::same($s->prefix, '49');
		Assert::same($s->name, 'kitty');
		Assert::same($s->flag, 'fill');
		Assert::same($s->quality, '100');
		Assert::same($s->size, [100, 200]);
		Assert::same($s->extension, 'jpg');
		Assert::same($s->crop, []);

		$s = ImageNameScript::fromName('images/10/20/49/kitty.100x200.fill.q100.jpg');

		Assert::same($s->original, 'images/10/20/49/kitty.jpg');
		Assert::same($s->namespace, 'images/10/20');
		Assert::same($s->prefix, '49');
		Assert::same($s->name, 'kitty');
		Assert::same($s->flag, 'fill');
		Assert::same($s->quality, '100');
		Assert::same($s->size, [100, 200]);
		Assert::same($s->extension, 'jpg');
		Assert::same($s->crop, []);

		$s = ImageNameScript::fromName('/data/images/49/kitty.200x200crop100x150x100x100.fit.q85.jpg');
		Assert::same($s->crop, [100, 150, 100, 100]);
	}


	public function testFromIdentifier(): void
	{
		$s = ImageNameScript::fromIdentifier('images/49/kitty.jpg');

		$s->setQuality(2);
		$s->setSize([2, 2]);
		$s->setFlag('exact');

		Assert::same($s->getIdentifier(), 'images/49/kitty.2x2.exact.q2.jpg');
		Assert::same($s->toQuery(), 'images/49/2x2.exact.q2/kitty.jpg?_image_storage');
	}

}


$test_case = new ImageNameScriptTest();
$test_case->run();
