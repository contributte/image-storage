<?php declare(strict_types = 1);

namespace Contributte\ImageStorage;

use Contributte\ImageStorage\Exception\ImageExtensionException;
use Contributte\ImageStorage\Exception\ImageResizeException;
use Contributte\ImageStorage\Exception\ImageStorageException;
use DirectoryIterator;
use Nette\Http\FileUpload;
use Nette\SmartObject;
use Nette\Utils\Image as NetteImage;
use Nette\Utils\Strings;

class ImageStorage
{

	use SmartObject;

	/** @var string */
	private $data_path;

	/** @var string */
	private $data_dir;

	/** @var string */
	private $algorithm_file;

	/** @var string */
	private $algorithm_content;

	/** @var int */
	private $quality;

	/** @var string */
	private $default_transform;

	/** @var string */
	private $noimage_identifier;

	/** @var bool */
	private $friendly_url;

	/** @var int */
	private $mask = 0775;

	/** @var int[] */
	private $_image_flags = [
		'fit' => 0,
		'fill' => 4,
		'exact' => 8,
		'stretch' => 2,
		'shrink_only' => 1,
	];

	public function __construct(
		string $data_path,
		string $data_dir,
		string $algorithm_file,
		string $algorithm_content,
		int $quality,
		string $default_transform,
		string $noimage_identifier,
		bool $friendly_url
	)
	{
		$this->data_path = $data_path;
		$this->data_dir = $data_dir;
		$this->algorithm_file = $algorithm_file;
		$this->algorithm_content = $algorithm_content;
		$this->quality = $quality;
		$this->default_transform = $default_transform;
		$this->noimage_identifier = $noimage_identifier;
		$this->friendly_url = $friendly_url;
	}

	/**
	 * @param mixed $arg
	 */
	public function delete($arg): void
	{
		$script = is_object($arg) && $arg instanceof Image
			? ImageNameScript::fromIdentifier($arg->identifier)
			: ImageNameScript::fromName($arg);

		$pattern = preg_replace('/__file__/', $script->name, ImageNameScript::PATTERN);
		$dir = implode('/', [$this->data_path, $script->namespace, $script->prefix]);

		if (!file_exists($dir)) {
			return;
		}

		foreach (new DirectoryIterator($dir) as $file_info) {
			if (preg_match($pattern, $file_info->getFilename())) {
				unlink($file_info->getPathname());
			}
		}
	}

	public function saveUpload(FileUpload $upload, string $namespace, ?string $checksum = null): Image
	{
		if (!$checksum) {
			$checksum = call_user_func_array($this->algorithm_file, [$upload->getTemporaryFile()]);
		}

		[$path, $identifier] = $this->getSavePath(
			self::fixName($upload->getName()),
			$namespace,
			$checksum
		);

		$upload->move($path);

		$image = new Image($this->friendly_url, $this->data_dir, $this->data_path, $identifier, [
			'sha' => $checksum,
			'name' => self::fixName($upload->getName()),
		]);

		return $image;
	}

	private static function fixName(string $name): string
	{
		return Strings::webalize($name, '._');
	}


	/**
	 * @param mixed $content
	 */
	public function saveContent($content, string $name, string $namespace, ?string $checksum = null): Image
	{
		if (!$checksum) {
			$checksum = call_user_func_array($this->algorithm_content, [$content]);
		}

		[$path, $identifier] = $this->getSavePath(
			self::fixName($name),
			$namespace,
			$checksum
		);

		file_put_contents($path, $content, LOCK_EX);

		$image = new Image($this->friendly_url, $this->data_dir, $this->data_path, $identifier, [
			'sha' => $checksum,
			'name' => self::fixName($name),
		]);

		return $image;
	}

	/**
	 * @param mixed $args
	 */
	public function fromIdentifier($args): Image
	{
		if (!is_array($args)) {
			$args = [$args];
		}

		$identifier = $args[0];

		$isNoImage = false;

		if (count($args) === 1) {
			if (!file_exists(implode('/', [$this->data_path, $identifier])) || !$identifier) {
				return $this->getNoImage(true);
			}

			return new Image($this->friendly_url, $this->data_dir, $this->data_path, $identifier);
		}

		preg_match('/(\d+)?x(\d+)?(crop(\d+)x(\d+)x(\d+)x(\d+))?/', $args[1], $matches);
		$size = [(int) $matches[1], (int) $matches[2]];
		$crop = [];

		if (!$size[0] || !$size[1]) {
			throw new ImageResizeException('Error resizing image. You have to provide both width and height.');
		}

		if (count($matches) === 8) {
			$crop = [(int) $matches[4], (int) $matches[5], (int) $matches[6], (int) $matches[7]];
		}

		$flag = $args[2] ?? $this->default_transform;
		$quality = $args[3] ?? $this->quality;

		if (!$identifier) {
			$is_no_image = false;
			[$script, $file] = $this->getNoImage(false);
		} else {
			$script = ImageNameScript::fromIdentifier($identifier);

			$file = implode('/', [$this->data_path, $script->original]);

			if (!file_exists($file)) {
				$is_no_image = true;
				[$script, $file] = $this->getNoImage(false);
			}
		}

		$script->setSize($size);
		$script->setCrop($crop);
		$script->setFlag($flag);
		$script->setQuality($quality);

		$identifier = $script->getIdentifier();

		if (!file_exists(implode('/', [$this->data_path, $identifier]))) {
			if (!file_exists($file)) {
				return new Image(false, '#', '#', 'Can not find image');
			}

			$_image = NetteImage::fromFile($file);

			if ($script->hasCrop() && !$isNoImage) {
				call_user_func_array([$_image, 'crop'], $script->crop);
			}

			if (strpos($flag, '+') !== false) {
				$bits = 0;

				foreach (explode('+', $flag) as $f) {
					$bits = $this->_image_flags[$f] | $bits;
				}

				$flag = $bits;
			} else {
				$flag = $this->_image_flags[$flag];
			}

			$_image->resize($size[0], $size[1], $flag);

			$_image->sharpen()->save(
				implode('/', [$this->data_path, $identifier]),
				$quality
			);
		}

		return new Image($this->friendly_url, $this->data_dir, $this->data_path, $identifier, ['script' => $script]);
	}

	/**
	 * @throws ImageStorageException
	 */
	public function getNoImage(bool $return_image = false): Image
	{
		$script = ImageNameScript::fromIdentifier($this->noimage_identifier);
		$file = implode('/', [$this->data_path, $script->original]);

		if (!file_exists($file)) {
			$identifier = '_storage_no_image/8f/no_image.png';
			$new_path = sprintf('%s/%s', $this->data_path, $identifier);

			if (!file_exists($new_path)) {
				$dirName = dirname($identifier);

				if (!file_exists($dirName)) {
					mkdir($dirName, 0777, true);
				}

				if (!file_exists($dirName) || !is_writable($new_path)) {
					throw new ImageStorageException('Could not create default no_image.png. ' . $dirName . ' does not exist or is not writable.');
				}

				$data = base64_decode(require __DIR__ . '/NoImageSource.php');
				$_image = NetteImage::fromString($data);
				$_image->save($new_path, $script->quality ?: $this->quality);
			}

			if ($return_image) {
				return new Image($this->friendly_url, $this->data_dir, $this->data_path, $identifier);
			}

			$script = ImageNameScript::fromIdentifier($identifier);

			return [$script, $new_path];
		}

		if ($return_image) {
			return new Image($this->friendly_url, $this->data_dir, $this->data_path, $this->noimage_identifier);
		}

		return [$script, $file];
	}


	/**
	 * @return string[]
	 * @throws ImageExtensionException
	 */
	private function getSavePath(string $name, string $namespace, string $checksum): array
	{
		$prefix = substr($checksum, 0, 2);
		$dir = implode('/', [$this->data_path, $namespace, $prefix]);

		@mkdir($dir, $this->mask, true); // Directory may exist

		preg_match('/(.*)(\.[^\.]*)/', $name, $matches);

		if (!$matches[2]) {
			throw new ImageExtensionException(sprintf('Error defining image extension (%s)', $name));
		}

		$name = $matches[1];
		$extension = $matches[2];

		while (file_exists($path = $dir . '/' . $name . $extension)) {
			$name = (!isset($i) && ($i = 2)) ? $name . '.' . $i : substr($name, 0, -(2 + (int) floor(log($i - 1, 10)))) . '.' . $i;
			$i++;
		}

		$identifier = implode('/', [$namespace, $prefix, $name . $extension]);

		return [$path, $identifier];
	}

	public function setFriendlyUrl(bool $friendly_url = true): void
	{
		$this->friendly_url = $friendly_url;
	}

}
