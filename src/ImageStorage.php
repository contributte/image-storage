<?php declare(strict_types = 1);

namespace Contributte\ImageStorage;

use Contributte\ImageStorage\Exception\ImageExtensionException;
use Contributte\ImageStorage\Exception\ImageResizeException;
use Contributte\ImageStorage\Exception\ImageStorageException;
use DirectoryIterator;
use Nette\Http\FileUpload;
use Nette\SmartObject;
use Nette\Utils\FileSystem;
use Nette\Utils\Image as NetteImage;
use Nette\Utils\Strings;
use Nette\Utils\UnknownImageFileException;

class ImageStorage
{

	use SmartObject;

	private string $data_path;

	private string $data_dir;

	private string $orig_path;

	/** @var callable(string): string */
	private $algorithm_file;

	/** @var callable(string): string */
	private $algorithm_content;

	private int $quality;

	private string $default_transform;

	private string $noimage_identifier;

	private bool $friendly_url;

	private int $mask = 0775;

	/** @var int[] */
	private array $_image_flags = [
		'fit' => 0,
		'fill' => 4,
		'exact' => 8,
		'stretch' => 2,
		'shrink_only' => 1,
	];

	/**
	 * @param callable(string): string $algorithm_file
	 * @param callable(string): string $algorithm_content
	 */
	public function __construct(
		string $data_path,
		string $data_dir,
		string $orig_path,
		callable $algorithm_file,
		callable $algorithm_content,
		int $quality,
		string $default_transform,
		string $noimage_identifier,
		bool $friendly_url
	)
	{
		$this->data_path = $data_path;
		$this->data_dir = $data_dir;
		$this->orig_path = $orig_path;
		$this->algorithm_file = $algorithm_file;
		$this->algorithm_content = $algorithm_content;
		$this->quality = $quality;
		$this->default_transform = $default_transform;
		$this->noimage_identifier = $noimage_identifier;
		$this->friendly_url = $friendly_url;
	}

	public function delete(mixed $arg, bool $onlyChangedImages = false): void
	{
		$script = is_object($arg) && $arg instanceof Image
			? ImageNameScript::fromIdentifier($arg->identifier)
			: ImageNameScript::fromName($arg);

		$pattern = preg_replace('/__file__/', $script->name, ImageNameScript::PATTERN);
		$dir = implode('/', [$this->data_path, $script->namespace, $script->prefix]);
		$origFile = $script->name . '.' . $script->extension;

		if ($this->orig_path === $this->data_path) {
			if (!file_exists($dir)) {
				return;
			}

			foreach (new DirectoryIterator($dir) as $file_info) {
				if (
					!preg_match($pattern, $file_info->getFilename())
					|| !(!$onlyChangedImages || $origFile !== $file_info->getFilename()
					)
				) {
					continue;
				}

				unlink($file_info->getPathname());
			}
		} else {
			if (!$onlyChangedImages) {
				unlink(implode('/', [$this->orig_path, $script->namespace, $script->prefix, $origFile]));
			}

			FileSystem::delete($dir);
		}
	}

	public function saveUpload(FileUpload $upload, string $namespace, ?string $checksum = null): Image
	{
		if (!$checksum) {
			$checksum = call_user_func_array($this->algorithm_file, [$upload->getTemporaryFile()]);
		}

		[$path, $identifier] = $this->getSavePath(
			self::fixName($upload->getUntrustedName()),
			$namespace,
			$checksum
		);

		$upload->move($path);

		return new Image($this->friendly_url, $this->data_dir, $this->data_path, $identifier, [
			'sha' => $checksum,
			'name' => self::fixName($upload->getUntrustedName()),
		]);
	}

	public function saveContent(mixed $content, string $name, string $namespace, ?string $checksum = null): Image
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

		return new Image($this->friendly_url, $this->data_dir, $this->data_path, $identifier, [
			'sha' => $checksum,
			'name' => self::fixName($name),
		]);
	}

	public function fromIdentifier(mixed $args): Image
	{
		if (!is_array($args)) {
			$args = [$args];
		}

		$identifier = $args[0];
		$quality = $args[3] ?? $this->quality;
		$flag = $args[2] ?? $this->default_transform;

		$orig_file = implode('/', [$this->orig_path, $identifier]);
		$data_file = implode('/', [$this->data_path, $identifier]);
		$isNoImage = false;

		if (count($args) === 1) {
			if (!file_exists($orig_file) || !$identifier) {
				return $this->getNoImage(true);
			}

			if (!file_exists($data_file)) {
				@mkdir(dirname($data_file), $this->mask, true);
				@copy($orig_file, $data_file);
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

		if (!$identifier) {
			$isNoImage = false;
			[$script, $file] = $this->getNoImage(false);
		} else {
			$script = ImageNameScript::fromIdentifier($identifier);

			$file = $orig_file;

			if (!file_exists($file)) {
				$isNoImage = true;
				[$script, $file] = $this->getNoImage(false);
			}
		}

		$script->setSize($size);
		$script->setCrop($crop);
		$script->setFlag($flag);
		$script->setQuality($quality);
		$script->setExtension($args[4] ?? $script->extension);

		$identifier = $script->getIdentifier();
		$data_file = implode('/', [$this->data_path, $identifier]);

		if (!file_exists($data_file)) {
			if (!file_exists($file)) {
				return new Image(false, '#', '#', 'Can not find image');
			}

			try {
				$_image = NetteImage::fromFile($file);
			} catch (UnknownImageFileException $e) {
				return new Image(false, '#', '#', 'Unknown type of file');
			}

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

			@mkdir(dirname($data_file), $this->mask, true);
			$_image->sharpen()->save(
				$data_file,
				$quality
			);
		}

		return new Image($this->friendly_url, $this->data_dir, $this->data_path, $identifier, ['script' => $script]);
	}

	/**
	 * @return Image|mixed[]
	 * @phpstan-return Image|array{ImageNameScript, string}
	 * @throws ImageStorageException
	 */
	public function getNoImage(bool $return_image = false): Image|array
	{
		$script = ImageNameScript::fromIdentifier($this->noimage_identifier);
		$file = implode('/', [$this->data_path, $script->original]);

		if (!file_exists($file)) {
			$identifier = $this->noimage_identifier;
			$new_path = sprintf('%s/%s', $this->data_path, $identifier);

			if (!file_exists($new_path)) {
				$dirName = dirname($new_path);

				if (!file_exists($dirName)) {
					mkdir($dirName, 0777, true);
				}

				if (!file_exists($dirName) || !is_writable($dirName)) {
					throw new ImageStorageException('Could not create default no_image.png. ' . $dirName . ' does not exist or is not writable.');
				}

				$data = base64_decode(require __DIR__ . '/NoImageSource.php', true);
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

	public function setFriendlyUrl(bool $friendly_url = true): void
	{
		$this->friendly_url = $friendly_url;
	}

	private static function fixName(string $name): string
	{
		return Strings::webalize($name, '._');
	}

	/**
	 * @return string[]
	 * @throws ImageExtensionException
	 */
	private function getSavePath(string $name, string $namespace, string $checksum): array
	{
		$prefix = substr($checksum, 0, 2);
		$dir = implode('/', [$this->orig_path, $namespace, $prefix]);

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

}
