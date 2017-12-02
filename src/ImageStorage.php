<?php

/**
 * @copyright   Copyright (c) 2016 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\ImageStorage;

use Nette;

class ImageStorage
{

	use Nette\SmartObject;

	/**
	 * Absolute data dir path in public directory (.../public/data by default)
	 * @var string
	 */
	private $data_path;

	/**
	 * Relative data dir in public directory (data by default)
	 * @var string
	 */
	private $data_dir;

	/**
	 * How to compute the checksum of image file
	 * sha1_file by default
	 * @var string
	 */
	private $algorithm_file;

	/**
	 * How to compute the checksum of image content
	 * sha1 by default
	 * @var string
	 */
	private $algorithm_content;

	/**
	 * Quality of saved thumbnails
	 * @var int
	 */
	private $quality;

	/**
	 * Default transform method
	 * 'fit' by default
	 * @var string
	 */
	private $default_transform;

	/**
	 * Noimage image identifier
	 * @var string
	 */
	private $noimage_identifier;

	/**
	 * Create friendly url?
	 * @var bool
	 */
	private $friendly_url;

	/**
	 * @var int
	 */
	private $mask = 0775;

	/**
	 * @var array
	 */
	private $_image_flags = [
		'fit' => 0,
		'fill' => 4,
		'exact' => 8,
		'stretch' => 2,
		'shrink_only' => 1
	];


	public function __construct(
		$data_path,
		$data_dir,
		$algorithm_file,
		$algorithm_content,
		$quality,
		$default_transform,
		$noimage_identifier,
		$friendly_url
	) {
		$this->data_path = $data_path;
		$this->data_dir = $data_dir;
		$this->algorithm_file = $algorithm_file;
		$this->algorithm_content = $algorithm_content;
		$this->quality = $quality;
		$this->default_transform = $default_transform;
		$this->noimage_identifier = $noimage_identifier;
		$this->friendly_url = $friendly_url;
	}


	/**************************************************************************
	 *                              DELETE IMAGE                              *
	 **************************************************************************/


	/**
	 * Delete stored image and all thumbnails/resized images, etc
	 * @param  mixed $arg
	 * @return void
	 */
	public function delete($arg)
	{
		if (is_object($arg) && $arg instanceof Image) {
			$script = ImageNameScript::fromIdentifier($arg->identifier);
		} else {
			$script = ImageNameScript::fromName($arg);
		}

		$pattern = preg_replace('/__file__/', $script->name, ImageNameScript::PATTERN);
		$dir = implode('/', [$this->data_path, $script->namespace, $script->prefix]);

		if (!file_exists($dir)) {
			return;
		}

		foreach (new \DirectoryIterator($dir) as $file_info) {
			if (preg_match($pattern, $file_info->getFilename())) {
				unlink($file_info->getPathname());
			}
		}
	}


	/**************************************************************************
	 *                               SAVE IMAGE                               *
	 **************************************************************************/


	/**
	 * Take a FileUpload, save it and return new Image
	 * @param  Nette\Http\FileUpload $upload
	 * @param  string                $namespace
	 * @param  string                $checksum
	 * @return Image
	 */
	public function saveUpload(Nette\Http\FileUpload $upload, $namespace, $checksum = NULL)
	{
		if (!$checksum) {
			$checksum = call_user_func_array($this->algorithm_file, [$upload->getTemporaryFile()]);
		}

		list($path, $identifier) = $this->getSavePath(
			self::fixName($upload->getName()),
			$namespace,
			$checksum
		);

		$upload->move($path);

		$image = new Image($this->friendly_url, $this->data_dir, $this->data_path, $identifier, [
			'sha' => $checksum,
			'name' => self::fixName($upload->getName())
		]);

		return $image;
	}

	private static function fixName($name)
	{
		return Nette\Utils\Strings::webalize($name, '._');
	}


	public function saveContent($content, $name, $namespace, $checksum = NULL)
	{
		if (!$checksum) {
			$checksum = call_user_func_array($this->algorithm_content, [$content]);
		}

		list($path, $identifier) = $this->getSavePath(
			self::fixName($name),
			$namespace,
			$checksum
		);

		file_put_contents($path, $content, LOCK_EX);

		$image = new Image($this->friendly_url, $this->data_dir, $this->data_path, $identifier, [
			'sha' => $checksum,
			'name' => self::fixName($name)
		]);

		return $image;
	}


	/**************************************************************************
	 *                               GET  IMAGE                               *
	 **************************************************************************/


	public function fromIdentifier($args)
	{
		if (!is_array($args)) {
			$args = [$args];
		}

		/**
		 * Define image identifier
		 */
		$identifier = $args[0];

		/**
		 * For don`t crop if no image
		 */
		$isNoImage = false;

		/**
		 * If we need original photo, do not resize anything
		 */
		if (sizeof($args) === 1) {
			if (!file_exists(implode('/', [$this->data_path, $identifier])) || !$identifier) {
				return $this->getNoImage(TRUE);
			}
			return new Image($this->friendly_url, $this->data_dir, $this->data_path, $identifier);
		}

		/**
		 * Define new image size (w, h)
		 */
		preg_match('/(\d+)?x(\d+)?(crop(\d+)x(\d+)x(\d+)x(\d+))?/', $args[1], $matches);
		$size = [(int) $matches[1], (int) $matches[2]];
		$crop = [];

		if (!$size[0] || !$size[1]) {
			throw new ImageResizeException("Error resizing image. You have to provide both width and height.");
		}

		if (sizeof($matches) === 8) {
			$crop = [(int) $matches[4], (int) $matches[5], (int) $matches[6], (int) $matches[7]];
		}

		/**
		 * Define transform method / flag
		 */
		$flag = isset($args[2]) ? $args[2] : $this->default_transform;
		$quality = isset($args[3]) ? $args[3] : $this->quality;

		/**
		 * Verify that given identifier is not empty
		 */
		if (!$identifier) {
			$is_no_image = FALSE;
			list($script, $file) = $this->getNoImage(FALSE);
		} else {
			/**
			 * Create ImageNameScript and set particular sizes, flags, etc
			 */

			$script = ImageNameScript::fromIdentifier($identifier);

			/**
			 * Verify existency of image
			 */
			$file = implode('/', [$this->data_path, $script->original]);
			if (!file_exists($file)) {
				$is_no_image = TRUE;
				list($script, $file) = $this->getNoImage(FALSE);
			}
		}

		$script->setSize($size);
		$script->setCrop($crop);
		$script->setFlag($flag);
		$script->setQuality($quality);

		$identifier = $script->getIdentifier();

		if (!file_exists(implode('/', [$this->data_path, $identifier]))) {
			/**
			 * $file is now a path to noimage file (if any)
			 */

			if (!file_exists($file)) {
				/**
				 * Raise and exception?
				 */
				return new Image(NULL, '#', '#', 'Can not find image');
			}

			$_image = Nette\Utils\Image::fromFile($file);

			if ($script->hasCrop() && !$isNoImage) {
				call_user_func_array([$_image, 'crop'], $script->crop);
			}

			if (FALSE !== strpos($flag, '+')) {
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


	/**************************************************************************
	 *                            SUPPORT  SCRIPTS                            *
	 **************************************************************************/


	/**
	 * Return ImageNameScript and file for no-image image
	 * @return array
	 */
	public function getNoImage($return_image = FALSE)
	{
		$script = ImageNameScript::fromIdentifier($this->noimage_identifier);
		$file = implode('/', [$this->data_path, $script->original]);

		if (!file_exists($file)) {
			$identifier = '_storage_no_image/8f/no_image.png';
			$new_path = "{$this->data_path}/{$identifier}";

			if (!file_exists($new_path)) {
				$data = base64_decode(require __DIR__ . '/NoImageSource.php');
				$_image = Nette\Utils\Image::fromString($data);
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
	 * Return builded save path
	 * @param  string $name
	 * @param  string $namespace
	 * @param  string $checksum
	 * @return array
	 * @throws ImageExtensionException
	 */
	private function getSavePath($name, $namespace, $checksum)
	{
		/**
		 * Define path to parent directory of saved image
		 */
		$prefix = substr($checksum, 0, 2);
		$dir = implode('/', [$this->data_path, $namespace, $prefix]);

		@mkdir($dir, $this->mask, TRUE); // Directory may exist

		/**
		 * Define name and extension of file
		 */
		preg_match('/(.*)(\.[^\.]*)/', $name, $matches);

		if (!$matches[2]) {
			throw new ImageExtensionException("Error defining image extension ($name)");
		}

		$name = $matches[1];
		$extension = $matches[2];

		while (file_exists($path = $dir . '/' . $name . $extension)) {
			$name = (!isset($i) && ($i = 2)) ? $name . '.' . $i : substr($name, 0, -(2+floor(log($i-1, 10)))) . '.' . $i;
			$i++;
		}

		$identifier = implode('/', [$namespace, $prefix, $name . $extension]);

		return [$path, $identifier];
	}


	/**
	 * Create friendly URLs?
	 * @param boolean $friendly_url
	 */
	public function setFriendlyUrl($friendly_url = TRUE)
	{
		$this->friendly_url = $friendly_url;
	}

}


/**
 * The exception that is thrown when image file has no extension.
 */
class ImageExtensionException extends \Exception
{
}

/**
 * The exception that is thrown when resized image has no new width or height or both provided.
 */
class ImageResizeException extends \Exception
{
}
