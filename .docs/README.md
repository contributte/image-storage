# Image Storage

Image storage for Nette framework

---

## Content

- [Usage - how to register & configure](#usage)
- [Images - how to work with them](#images)
  - [Storing image](#storing-image)
  - [Transforming image](#transforming-image)
  - [Deleting image](#deleting-image)
  - [Friendly URL](#friendly-url)

## Usage

Register extension:
```yml
extensions:
	imageStorage: Contributte\ImageStorage\DI\ImageStorageExtension
```

Configure extension:
```yml
imageStorage:
	data_path:          %wwwDir%/../public/data # Filesystem location
	data_dir:           data                    # Relative path
	algorithm_file:     sha1_file               # Algorithm to take image prefix directory from
	algorithm_content:  sha1                    # ...
	quality:            85                      # Default wuality when cropping
	default_transform:  fit                     # Default crop transformation
	noimage_identifier: noimage/03/no-image.png # No-image image
	friendly_url:       false                   # Create friendly URLs?
```

## Images

## Storing image

You are saving files within particular namespaces (eg 'avatars'). 
For better filesystem optimization, target files are saved in `<namespace>/<hash>/<file.ext>`,
where the hash is made from first 2 characters of sha1 hash (or some other configured hashing algorithm) of target file.
Therefore there won't be thousands of files in one directory, 
but files will be distributed under that hash-named directories.


```php

<?php declare(strict_types = 1);

namespace Your\App\Presenters;

use Contributte\ImageStorage\ImageStoragePresenterTrait;;
use Nette\Application\UI\Presenter;

class ImageStoragePresenter extends Presenter
{

	// Add $imageStorage to templates (in order to use macros)
	use ImageStoragePresenterTrait;

	public function createComponentUpload()
	{
		$form->addUpload('upload', '');
	}

	public function uploadSucceeded($form, $values)
	{
		// You can save image from upload
		$image = $this->imageStorage->saveUpload($values->upload, 'images');
		dump($image);

		// Or directly image content
		$image2 = $this->imageStorage->saveContent(
			file_get_contents($values->upload->getTemporaryFile()),
			'foobar.png',
			'images'
		);
	}

}

```

## Transforming image - resizing, cropping

You simply pass a **size** parameter to either Latte macro or Latte n:macro or directly ImageStorage.
Or **crop** measures or **quality** or **transformation flag**.
Or some of these **combined**. You can also combine the transformation flags with `+` sign.

In model:
```php
<?php declare(strict_types = 1);

// Original
$img = $this->imageStorage->fromIdentifier('images/ed/kitty.jpg');
dump($img->getPath()); // System path to image file

// Resized etc
$img = $this->imageStorage->fromIdentifier(['images/ed/kitty.jpg', '20x20']);
```

In [Latte](https://latte.nette.org/) template:
```latte
{var $identifier = 'images/ed/kitty.jpg'}
{img $identifier}
```
## Deleting image

Once you want to delete an image, 
you should delete all other transformed images made from the original one.

From string identifier:
```php
<?php declare(strict_types = 1);

$img = 'images/ed/kitty.jpg';
$this->imageStorage->delete($img);
```

From object:
```php
<?php declare(strict_types = 1);

$img_object = $imageStorage->fromIdentifier('images/ed/kitty.jpg');
$this->imageStorage->delete($img_object);
```

## Friendly URL

The transformed image name does not look to much friendly (eg `/avatars/kitty.200x200.fit.jpg`). 
You can change the method of creating links to images in configuration file so the link will look `/avatars/200x200.fit/kitty.jpg` which is much more friendly when downloading the image.

If you don't want to make links to image in this format: 
```html
<img src="/data/images/ed/kitty.100x200.exact.q85.jpg">
```

But like this: 
```html
<img src="/data/images/ed/100x200.exact.q85/kitty.jpg?_image_storage">
```

1) Add a configuration to imageStorage extension in your config.neon:
    ```yml
    imageStorage:
        friendly_url: TRUE
    ``` 

2) Alter your `.htaccess` file:
    ```htaccess
    # Images Storage conversion with directory suffix
    RewriteCond %{QUERY_STRING} _image_storage
    RewriteRule ^(\w+)/(\w+)/(\w+)/([^/]+)/(.+)\.(.+) $1/$2/$3/$5.$4.$6 [L]
    ```