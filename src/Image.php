<?php

/**
 * @copyright   Copyright (c) 2016 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\ImageStorage;

use Nette;

class Image
{

	use Nette\SmartObject;

	/**
	 * Public data directory
	 * @var string
	 */
	public $data_dir;

	/**
	 * Public data directory path
	 * @var string
	 */
	public $data_path;

	/**
	 * Identifier in form:
	 * 	namespace/sha1_file[0..1]/img_name.suffix
	 * 
	 * @var string
	 */
	public $identifier;

	/**
	 * sha1_file checksum
	 * @var string
	 */
	public $sha;

	/**
	 * Original file name
	 * @var string
	 */
	public $name;

	/**
	 * @var ImageNameScript
	 */
	private $script;

	/**
	 * @var bool
	 */
	private $friendly_url = FALSE;


	public function __construct($friendly_url, $data_dir, $data_path, $identifier, $props = [])
	{
		$this->data_dir = $data_dir;
		$this->data_path = $data_path;
		$this->identifier = $identifier;
		$this->friendly_url = (bool) $friendly_url;

		foreach ($props as $prop => $value) {
			if (property_exists($this, $prop)) {
				$this->$prop = $value;
			}
		}
	}


	public function getPath()
	{
		return implode('/', [dirname($this->data_path), $this->createLink()]);
	}


	public function __toString()
	{
		return $this->identifier;
	}


	public function getQuery()
	{
		return $this->script->toQuery();
	}


	public function createLink()
	{
		/**
		 * /20x20crop10x10x10x10.exact....../img.jpg
		 */
		if ($this->friendly_url) {
			return implode('/', [$this->data_dir, $this->getScript()->toQuery()]);
		}

		/**
		 * /img.20x20crop10x10x10x10.exact.......jpg
		 */
		return implode('/', [$this->data_dir, $this->identifier]);
	}


	public function getScript()
	{
		return $this->script ?: ImageNameScript::fromIdentifier($this->identifier);
	}

}
