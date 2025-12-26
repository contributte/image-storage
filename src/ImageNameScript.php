<?php declare(strict_types = 1);

namespace Contributte\ImageStorage;

use Nette\SmartObject;

class ImageNameScript
{

	use SmartObject;

	public const PATTERN = '/__file__(\.(\d+)x(\d+)(crop(\d+)x(\d+)x(\d+)x(\d+))?\.(\w+))?(\.q(\d+))?\.([^\.]+)$/';

	/** @var string **/
	public string $identifier;

	/** @var string **/
	public string $original;

	public string $namespace;

	public string $prefix;

	public string $name;

	/** @var int[] **/
	public array $size = [];

	public string $flag;

	public int $quality;

	public string $extension;

	/** @var int[] */
	public array $crop = [];

	public function __construct(string $identifier)
	{
		$this->identifier = $identifier;
	}

	public static function fromIdentifier(string $identifier): ImageNameScript
	{
		return self::fromName($identifier);
	}

	public static function fromName(string $name): ImageNameScript
	{
		$pattern = preg_replace('/__file__/', '(.*)\/([^\/]+)\/(.*?)', self::PATTERN);
		preg_match($pattern, $name, $matches);

		$script = new self($matches[0]);

		$script->original = $matches[1] . '/' . $matches[2] . '/' . $matches[3] . '.' . $matches[15];
		$script->namespace = $matches[1];
		$script->prefix = $matches[2];
		$script->name = $matches[3];
		$script->size = [(int) $matches[5], (int) $matches[6]];
		$script->flag = $matches[12];
		$script->quality = intval($matches[14]);
		$script->extension = $matches[15];

		if ($matches[8] && $matches[9] && $matches[10] && $matches[11]) {
			$script->crop = [(int) $matches[8], (int) $matches[9], (int) $matches[10], (int) $matches[11]];
		}

		return $script;
	}

	/**
	 * @param int[] $size
	 */
	public function setSize(array $size): void
	{
		$this->size = $size;
	}

	/**
	 * @param int[] $crop
	 */
	public function setCrop(array $crop): void
	{
		$this->crop = $crop;
	}

	public function setFlag(string $flag): void
	{
		$this->flag = $flag;
	}

	public function setQuality(int $quality): void
	{
		$this->quality = $quality;
	}

	public function setExtension(string $extension): void
	{
		$this->extension = $extension;
	}

	public function getIdentifier(): string
	{
		$identifier = implode('/', [$this->namespace, $this->prefix, $this->name]);

		if ($this->size) {
			$identifier .= '.' . $this->size[0] . 'x' . $this->size[1];

			if (count($this->crop)) {
				$identifier .= sprintf('crop%sx%sx%sx%s', $this->crop[0], $this->crop[1], $this->crop[2], $this->crop[3]);
			}

			$identifier .= '.' . $this->flag;

			if ($this->quality) {
				$identifier .= '.q' . $this->quality;
			}
		}

		return $identifier . '.' . $this->extension;
	}

	public function hasCrop(): bool
	{
		return count($this->crop) > 0;
	}

	public function toQuery(): string
	{
		if ($this->size && $this->size[0] && $this->size[1]) {
			$params_dir = $this->size[0] . 'x' . $this->size[1];

			if (count($this->crop)) {
				$params_dir .= sprintf('crop%sx%sx%sx%s', $this->crop[0], $this->crop[1], $this->crop[2], $this->crop[3]);
			}

			$params_dir .= '.' . $this->flag;

			if ($this->quality) {
				$params_dir .= '.q' . $this->quality;
			}

			return implode('/', [
				$this->namespace,
				$this->prefix,
				$params_dir,
				sprintf('%s.%s?_image_storage', $this->name, $this->extension),
			]);
		}

		return $this->original;
	}

}
