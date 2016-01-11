[Demo](http://ublaboo.paveljanda.com/image-storage)

# image-storage
Image storage for Nette framework

Add this to .htaccess:

```
  # ImageStorage conversion with directory suffix
	RewriteCond %{QUERY_STRING} _image_storage
	RewriteRule ^(\w+)/(\w+)/(\w+)/([^/]+)/([^.]+)\.(.+) $1/$2/$3/$5.$4.$6 [L]
```
