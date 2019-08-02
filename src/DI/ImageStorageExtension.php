<?php declare(strict_types = 1);

namespace Contributte\ImageStorage\DI;

use Contributte\ImageStorage\ImageStorage;
use Nette;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

class ImageStorageExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'data_path' => Expect::string()->required(),
			'data_dir' => Expect::string()->required(),
			'algorithm_file' => Expect::string('sha1_file'),
			'algorithm_content' => Expect::string('sha1'),
			'quality' => Expect::int(85),
			'default_transform' => Expect::string('fit'),
			'noimage_identifier' => Expect::string('noimage/03/no-image.png'),
			'friendly_url' => Expect::bool(false),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = (array) $this->config;
		$builder->addDefinition($this->prefix('storage'))
			->setType(ImageStorage::class)
			->setFactory(ImageStorage::class)
			->setArguments($config);
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		/** @var Nette\DI\Definitions\FactoryDefinition $latteFactory */
		$latteFactory = $builder->getDefinition('latte.latteFactory');
		assert($latteFactory instanceof Nette\DI\Definitions\FactoryDefinition);
		$latteFactory->getResultDefinition()
			->addSetup('Contributte\ImageStorage\Macros\Macros::install(?->getCompiler())', ['@self']);
	}

}
