<?php

/**
 * @copyright   Copyright (c) 2016 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\ImageStorage\DI;

use Nette;

class ImageStorageExtension extends Nette\DI\CompilerExtension
{

	private $defaults = [
		'data_path'          => '%wwwDir%/../public/data',
		'data_dir'           => 'data',
		'algorithm_file'     => 'sha1_file',
		'algorithm_content'  => 'sha1',
		'quality'            => 85,
		'default_transform'  => 'fit',
		'noimage_identifier' => 'noimage/03/no-image.png',
		'friendly_url'       => FALSE
	];


	public function loadConfiguration()
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
				$config['friendly_url']
			]);
	}


	public function beforeCompile()
	{
		$config = $this->_getConfig();

		$builder = $this->getContainerBuilder();

		$builder->getDefinition('nette.latteFactory')
			->addSetup('Ublaboo\ImageStorage\Macros\Macros::install(?->getCompiler())', array('@self'));
	}


	private function _getConfig()
	{
		$config = $this->validateConfig($this->defaults, $this->config);

		$config['data_path'] = Nette\DI\Helpers::expand($config['data_path'], $this->getContainerBuilder()->parameters);

		return $config;
	}

}
