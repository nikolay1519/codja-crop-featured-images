<div id="cjCfi_page" class="wrap">
	<h1><?php _e('Crop image for post ID:', 'cj-cfi'); ?> <?php echo $post_id; ?></h1>

	<a href="<?php echo $edit_post_link; ?>" class="button"><?php _e('← Go back to post'); ?></a>

	<div class="cj_cfi_sizes">
		<?php foreach ($sizes as $name => $size) { ?>
		<div id="cj_cfi_size_box_<?php echo $name; ?>" class="cj_cfi_size_box clear"
		     data-size-id="<?php echo $name; ?>"
		     data-post-id="<?php echo $post_id; ?>"
		     data-size-width="<?php echo $size['width']; ?>"
		     data-size-height="<?php echo $size['height']; ?>"
		     data-attachment-id="<?php echo $initial_states[$name]['attachment_id']; ?>"
		     data-default-attachment-id="<?php echo $attachment_id; ?>"
		     data-default-image="<?php echo $original_image_src; ?>">
			<div class="cj_cfi_size_box__head clear">
				<?php _e('Size:', 'cj-cfi'); ?>
				<span class="cj_cfi_size_box__size_name"><?php if (isset($size['title'])) echo $size['title']; ?></span>
				<span class="cj_cfi_size_box__size_id">(<?php echo $name; ?>)</span>
				<span class="cj_cfi_size_box__size_params"><?php echo $size['width'] . '*' . $size['height']; ?></span>

				<div class="cj_cfi_size_box__buttons">
					<span class="cj_cfi_button cj_cfi_button__view_crop button"><?php _e('View Crop', 'cj-cfi'); ?></span>
					<span class="cj_cfi_button cj_cfi_button__change_image button"><?php _e('Change Image', 'cj-cfi'); ?></span>
					<span class="cj_cfi_button cj_cfi_button__save button" data-nonce="<?php echo wp_create_nonce('save_'.$name.'_'.$post_id); ?>"><?php _e('Save', 'cj-cfi'); ?></span>
					<span class="cj_cfi_button cj_cfi_button__reset button" data-nonce="<?php echo wp_create_nonce('reset_'.$name.'_'.$post_id); ?>"><?php _e('Reset', 'cj-cfi'); ?></span>
				</div>
			</div>
			<div class="cj_cfi_size_box__body">
				<div>
				<img class="cj_cfi_image_for_crop" src="<?php echo $initial_states[$name]['image']; ?>" />
				</div>
			</div>
			<div class="cj_cfi_size_box__footer">
				<div class="cj_cfi_size_box__footer__head"><?php _e('Old crops for that image for other posts (use it to save space)', 'cj-cfi'); ?></div>
				<div class="cj_cfi_size__crops clear">
					<?php
						if ($crops_of_attachment[$name] != false) {
							foreach ($crops_of_attachment[$name] as $crop_id => $crop_data) {
								require( CJ_CFI_DIR . 'templates/crop.php' );
							}
						}
					?>
				</div>
			</div>
		</div>
		<?php } ?>
	</div>

	<div id="viewCropImageDialog" title="View Crop"></div>

	<div class="cj_cfi_loader"><span></span></div>
</div>