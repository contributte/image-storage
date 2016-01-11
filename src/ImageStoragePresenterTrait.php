<?php

/**
 * @copyright   Copyright (c) 2016 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\ImageStorage;

use Ublaboo;

trait ImageStoragePresenterTrait
{
	
	/**
	 * @var Ublaboo\ImageStorage\ImageStorage
	 */
	public $imageStorage;


	public function injectImageStorage(Ublaboo\ImageStorage\ImageStorage $imageStorage) {
		$this->imageStorage = $imageStorage;
	}
	
	public function createTemplate() {
		$template = parent::createTemplate();
		
		$template->imageStorage = $this->imageStorage;
		
		return $template;
	}
}
