<?php

/**
 * @copyright   Copyright (c) 2016 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\ImageStorage;

use Nette;

class Image extends Nette\Object
{

	/**
	 * Public data directory
	 * @var string
	 */
	public $data_dir;

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


	public function __construct($data_dir, $identifier, $props = [])
	{
		$this->data_dir = $data_dir;
		$this->identifier = $identifier;

		foreach ($props as $prop => $value) {
			if (property_exists($this, $prop)) {
				$this->$prop = $value;
			}
		}
	}


	public function getPath()
	{
		return $this->createLink();
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
		return implode('/', [$this->data_dir, $this->getScript()->toQuery()]);

		//return implode('/', [$this->data_dir, $this->identifier]);
	}


	public function getScript()
	{
		return $this->script ?: ImageNameScript::fromIdentifier($this->identifier);
	}

}
