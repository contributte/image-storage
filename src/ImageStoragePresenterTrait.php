<?php declare(strict_types = 1);

namespace Contributte\ImageStorage;

use Nette\Application\UI\Template;

//phpcs:disable SlevomatCodingStandard.Classes.SuperfluousTraitNaming.SuperfluousSuffix

trait ImageStoragePresenterTrait
{

	public ImageStorage $imageStorage;

	public function injectImageStorage(ImageStorage $imageStorage): void
	{
		$this->imageStorage = $imageStorage;
	}

	public function createTemplate(?string $class = null): Template
	{
		$template = parent::createTemplate();

		$template->imageStorage = $this->imageStorage;

		return $template;
	}

}

// phpcs:enable
