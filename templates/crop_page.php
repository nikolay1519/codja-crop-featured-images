<div id="cjCfi_page" class="wrap">
	<h1><?php _e('Crop image for post ID:', 'cj-cfi'); ?> <?php echo $post_id; ?></h1>

	<div class="cj_cfi_image_info">
		<div class="cj_cfi_image_info__row"><span><?php _e('Original size', 'cj-cfi'); ?></span>: <?php echo $original_metadata['width']; ?>*<?php echo $original_metadata['height']; ?></div>
		<div class="cj_cfi_image_info__row"><span><?php _e('Edit post', 'cj-cfi'); ?></span>: <a href="<?php echo $edit_post_link; ?>"><?php echo $edit_post_link; ?></a></div>
	</div>

	<div class="cj_cfi_sizes">
		<?php foreach ($sizes as $name => $size) { ?>
		<div id="cj_cfi_size_box_<?php echo $name; ?>" class="cj_cfi_size_box clear"
		     data-size-id="<?php echo $name; ?>"
		     data-post-id="<?php echo $post_id; ?>"
		     data-size-width="<?php echo $size['width']; ?>"
		     data-size-height="<?php echo $size['height']; ?>"
		     data-attachment-id="<?php echo $initial_states[$name]['attachment_id']; ?>">
			<div class="cj_cfi_size_box__head clear">
				<?php _e('Size:', 'cj-cfi'); ?>
				<span class="cj_cfi_size_box__size_name"><?php echo $name; ?></span>
				<span class="cj_cfi_size_box__size_params">(<?php echo $size['width'] . '*' . $size['height']; ?>)</span>

				<div class="cj_cfi_size_box__buttons">
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
				<div class="cj_cfi_size__crops clear">
					<?php
						foreach ($crops_of_attachment[$name] as $crop_id => $crop_data) {
							require( CJ_CFI_DIR . 'templates/crop.php' );
						}
					?>
				</div>
			</div>
		</div>
		<?php } ?>
	</div>
</div>