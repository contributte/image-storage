<?php declare(strict_types = 1);

namespace Contributte\ImageStorage\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;

class ImageStorageExtension extends CompilerExtension
{

	/** @var mixed[] */
	private $defaults = [
		'data_path'          => '%wwwDir%/../public/data',
		'data_dir'           => 'data',
		'algorithm_file'     => 'sha1_file',
		'algorithm_content'  => 'sha1',
		'quality'            => 85,
		'default_transform'  => 'fit',
		'noimage_identifier' => 'noimage/03/no-image.png',
		'friendly_url'       => false,
	];

	public function loadConfiguration(): void
	{
		$config = $this->_getConfig();

		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('storage'))
			->setClass('Ublaboo\ImageStorage\ImageStorage')
			->setArguments([
				$config['data_path'],
				$config['data_dir'],
				$config['algorithm_file'],
				$config['algorithm_content'],
				$config['quality'],
				$config['default_transform'],
				$config['noimage_identifier'],
				$config['friendly_url'],
			]);
	}


	public function beforeCompile(): void
	{
		$config = $this->_getConfig();

		$builder = $this->getContainerBuilder();

		$builder->getDefinition('nette.latteFactory')
			->addSetup('Contributte\ImageStorage\Macros\Macros::install(?->getCompiler())', ['@self']);
	}


	/**
	 * @return mixed
	 */
	private function _getConfig()
	{
		$config = $this->validateConfig($this->defaults, $this->config);

		$config['data_path'] = Helpers::expand($config['data_path'], $this->getContainerBuilder()->parameters);

		return $config;
	}

}
