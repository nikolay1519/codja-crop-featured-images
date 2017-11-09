<?php
	$classes = array();

	if (isset($crop_data['current'])) {
		$classes[] = 'current';
	}
?>
<div class="cj_cfi_size__crop <?php echo implode(' ', $classes); ?>">
	<img src="<?=$crop_data['cropped']; ?>"
	     data-attachment-id="<?php echo $crop_data['attachment_id']; ?>"
	     data-crop-id="<?php echo $crop_id; ?>"
	     data-original-image="<?php echo $crop_data['original']; ?>"
	     data-cropped-image="<?php echo $crop_data['cropped']; ?>"
	     data-cropper-data="<?php echo esc_js($crop_data['data']); ?>" />
</div>