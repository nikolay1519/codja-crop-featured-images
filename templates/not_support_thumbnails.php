<div id="cjCfi_page" class="wrap">
	<h1><?php _e('Crop Featured Images', 'cj-cfi'); ?></h1>

	<p><?php _e("Selected post not support thumbnail. Please, select other post.", 'cj-cfi'); ?></p>

	<form action="" type="GET">
		<input type="hidden" name="page" value="cj-cfi" />
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e('Post ID for crop his featured image', 'cj-cfi'); ?></th>
				<td><input type="text" name="post_id" value="" /></td>
			</tr>
		</table>
		<p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Go', 'cj-cfi'); ?>"></p>
	</form>
</div>