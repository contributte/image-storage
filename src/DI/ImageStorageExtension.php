<?php declare(strict_types = 1);

namespace Contributte\ImageStorage\DI;

use Contributte\ImageStorage\ImageStorage;
use Contributte\ImageStorage\Latte\LatteExtension;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;

/**
 * @method stdClass getConfig()
 */
class ImageStorageExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'data_path' => Expect::string()->required(),
			'data_dir' => Expect::string()->required(),
			'orig_path' => Expect::string()->default(null),
			'algorithm_file' => Expect::string('sha1_file'),
			'algorithm_content' => Expect::string('sha1'),
			'quality' => Expect::structure([
				'jpeg' => Expect::int(85),
				'png' => Expect::int(6),
				'webp' => Expect::int(80),
				'avif' => Expect::int(30),
				'gif' => Expect::int()->nullable(),
			])->castTo('array'),
			'default_transform' => Expect::string('fit'),
			'noimage_identifier' => Expect::string('noimage/03/no-image.png'),
			'friendly_url' => Expect::bool(false),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();
		$config->orig_path ??= $config->data_path;

		$builder->addDefinition($this->prefix('storage'))
			->setType(ImageStorage::class)
			->setFactory(ImageStorage::class)
			->setArguments((array) $config);
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$latteFactory = $builder->getDefinition('latte.latteFactory');
		assert($latteFactory instanceof FactoryDefinition);

		$latteFactory->getResultDefinition()->addSetup('addExtension', [new LatteExtension()]);
	}

}
