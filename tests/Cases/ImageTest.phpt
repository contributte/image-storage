<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\ImageStorage\Image;
use Contributte\Tester\Toolkit;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

Toolkit::test(static function (): void {
	$image = new Image(false, '', '/data', '/namespace/47/img.jpg');
	Assert::equal('/data/namespace/47/img.jpg', $image->getPath());
});

Toolkit::test(static function (): void {
	$image = new Image(false, '', '/data/images', 'namespace/47/img.jpg');
	Assert::equal('/data/images/namespace/47/img.jpg', $image->getPath());
});

Toolkit::test(static function (): void {
	$image = new Image(false, 'data', '', 'namespace/47/img.jpg');
	Assert::equal('data/namespace/47/img.jpg', $image->createLink());
});

Toolkit::test(static function (): void {
	$image = new Image(false, 'data/images', '', 'namespace/47/img.jpg');
	Assert::equal('data/images/namespace/47/img.jpg', $image->createLink());
});
