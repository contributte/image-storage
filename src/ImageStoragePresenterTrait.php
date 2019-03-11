<?php declare(strict_types = 1);

namespace Contributte\ImageStorage;

use Nette\Application\UI\ITemplate;

//phpcs:disable SlevomatCodingStandard.Classes.SuperfluousTraitNaming.SuperfluousSuffix

trait ImageStoragePresenterTrait
{

	/** @var ImageStorage */
	public $imageStorage;

	public function injectImageStorage(ImageStorage $imageStorage): void
	{
		$this->imageStorage = $imageStorage;
	}

	public function createTemplate(): ITemplate
	{
		$template = parent::createTemplate();

		$template->imageStorage = $this->imageStorage;

		return $template;
	}

}

// phpcs:enable
