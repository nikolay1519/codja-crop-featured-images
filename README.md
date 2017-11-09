# CODJA Crop Featured Images
Plugin allow crop all sizes of featured image for each post.

For get the url of needed crop use function `cj_get_image_for_object( $post_id, $image_size );`

## Disable sizes in plugin
For exclude sizes from plugin you can use the filter `cj_cfi_disabled_sizes` in your funstions.php of theme and return sizes that you want to disable.
```php
add_filter('cj_cfi_disabled_sizes', function($sizes) {
	$sizes[] = 'content-vertical';

	return $sizes;
});
```
## Set titles for sizes
For set titles for sizes you can use the filter `cj_cfi_set_size_names` and return an associative array with titles for sizes
```php
add_filter('cj_cfi_set_size_names', function($sizes) {
	$sizes['single-top-featured-image'] = __('Top Header');
	$sizes['shop_catalog'] = __('Thumbnail for product in category');

	return $sizes;
});
```