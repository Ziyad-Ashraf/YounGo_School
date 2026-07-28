<form action="<?php echo site_url('admin/blog_category/add'); ?>" method="post">
	<div class="form-group">
		<label for="category_english_title"><?php echo get_phrase('title'); ?> (English)</label>
		<input class="form-control" type="text" id="category_english_title" name="english_title" required>
	</div>
	<div class="form-group">
		<label for="category_english_subtitle"><?php echo get_phrase('subtitle'); ?> (English) <small class="text-muted">(80 <?php echo get_phrase('character'); ?>)</small></label>
		<textarea class="form-control" rows="3" name="english_subtitle" id="category_english_subtitle" maxlength="80"></textarea>
	</div>
	<div class="form-group">
		<label for="category_english_display_slug">Display slug (English)</label>
		<input class="form-control" type="text" id="category_english_display_slug" name="english_display_slug">
		<small class="text-muted"><?php echo get_phrase('display_only._public_routes_keep_the_canonical_category_slug.'); ?></small>
	</div>
	<div class="form-group">
		<label for="category_arabic_title"><?php echo get_phrase('title'); ?> (Arabic)</label>
		<input class="form-control" type="text" id="category_arabic_title" name="arabic_title" dir="rtl">
	</div>
	<div class="form-group">
		<label for="category_arabic_subtitle"><?php echo get_phrase('subtitle'); ?> (Arabic)</label>
		<textarea class="form-control" rows="3" name="arabic_subtitle" id="category_arabic_subtitle" dir="rtl" maxlength="160"></textarea>
	</div>
	<div class="form-group">
		<label for="category_arabic_display_slug">Display slug (Arabic)</label>
		<input class="form-control" type="text" id="category_arabic_display_slug" name="arabic_display_slug" dir="rtl">
		<small class="text-muted"><?php echo get_phrase('display_only._public_routes_keep_the_canonical_category_slug.'); ?></small>
	</div>

	<div class="form-group">
		<button type="submit" class="btn btn-primary"><?php echo get_phrase('submit'); ?></button>
	</div>
</form>
