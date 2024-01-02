<?php declare(strict_types = 1);

namespace Contributte\ImageStorage;

use InvalidArgumentException;
use Nette\SmartObject;

class Image
{

	use SmartObject;

	public string $data_dir;

	public string $data_path;

	public string $identifier;

	public string $sha;

	public string $name;

	private ?ImageNameScript $script = null;

	private bool $friendly_url = false;

	/**
	 * @param bool[]|string[]|ImageNameScript[]|null[] $props
	 */
	public function __construct(bool $friendly_url, string $data_dir, string $data_path, string $identifier, array $props = [])
	{
		$this->data_dir = $data_dir;
		$this->data_path = $data_path;
		$this->identifier = $identifier;
		$this->friendly_url = $friendly_url;

		if (stripos($this->identifier, '/') === 0) {
			$this->identifier = substr($this->identifier, 1);
		}

		foreach ($props as $prop => $value) {
			if (!property_exists($this, $prop)) {
				continue;
			}

			$this->$prop = $value;
		}
	}

	public function getPath(): string
	{
		return implode('/', [$this->data_path, $this->identifier]);
	}

	public function getQuery(): string
	{
		if ($this->script === null) {
			throw new InvalidArgumentException(sprintf(
				'%s: Property $script is not set and called %s. Please set $script',
				static::class,
				__METHOD__
			));
		}

		return $this->script->toQuery();
	}

	public function createLink(): string
	{
		if ($this->friendly_url) {
			return implode('/', [$this->data_dir, $this->getScript()->toQuery()]);
		}

		return implode('/', [$this->data_dir, $this->identifier]);
	}

	public function getScript(): ImageNameScript
	{
		return $this->script ?: ImageNameScript::fromIdentifier($this->identifier);
	}

	public function __toString(): string
	{
		return $this->identifier;
	}

}
