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
		$s = ImageNameScript::fromName('/data/images/ed/kitty.100x200.fill.q100.jpg');

		Assert::same($s->original, '/data/images/ed/kitty.jpg');
		Assert::same($s->prefix, 'data');
		Assert::same($s->name, 'images/ed/kitty');
		Assert::same($s->flag, 'fill');
		Assert::same($s->quality, '100');
		Assert::same($s->size, [100, 200]);
		Assert::same($s->extension, 'jpg');
		Assert::same($s->crop, []);

		$s = ImageNameScript::fromName('/data/images/ed/kitty.200x200crop100x150x100x100.fit.q85.jpg');
		Assert::same($s->crop, [100, 150, 100, 100]);
	}


	public function testFromIdentifier(): void
	{
		$s = ImageNameScript::fromIdentifier('images/ed/kitty.jpg');

		$s->setQuality(2);
		$s->setSize([2, 2]);
		$s->setFlag('exact');

		Assert::same($s->getIdentifier(), 'images/ed/kitty.2x2.exact.q2.jpg');
		Assert::same($s->toQuery(), 'images/ed/2x2.exact.q2/kitty.jpg?_image_storage');
	}

}


$test_case = new ImageNameScriptTest();
$test_case->run();
