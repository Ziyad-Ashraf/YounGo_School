<?php
$translations = isset($youngo_blog_category_translations) && is_array($youngo_blog_category_translations) ? $youngo_blog_category_translations : array();
$english_translation = isset($translations['english']) && is_array($translations['english']) ? $translations['english'] : array();
$arabic_translation = isset($translations['arabic']) && is_array($translations['arabic']) ? $translations['arabic'] : array();
$english_title = isset($english_translation['title']) && trim((string) $english_translation['title']) !== '' ? $english_translation['title'] : $blog_category['title'];
$english_subtitle = isset($english_translation['subtitle']) && trim((string) $english_translation['subtitle']) !== '' ? $english_translation['subtitle'] : $blog_category['subtitle'];
$english_display_slug = isset($english_translation['display_slug']) ? $english_translation['display_slug'] : '';
$arabic_title = isset($arabic_translation['title']) ? $arabic_translation['title'] : '';
$arabic_subtitle = isset($arabic_translation['subtitle']) ? $arabic_translation['subtitle'] : '';
$arabic_display_slug = isset($arabic_translation['display_slug']) ? $arabic_translation['display_slug'] : '';
?>
<form action="<?php echo site_url('admin/blog_category/update/'.$blog_category['blog_category_id']); ?>" method="post">
	<div class="form-group">
		<label for="category_english_title"><?php echo get_phrase('title'); ?> (English)</label>
		<input class="form-control" value="<?php echo html_escape($english_title); ?>" type="text" id="category_english_title" name="english_title" required>
	</div>
	<div class="form-group">
		<label for="category_english_subtitle"><?php echo get_phrase('subtitle'); ?> (English) <small class="text-muted">(80 <?php echo get_phrase('character'); ?>)</small></label>
		<textarea class="form-control" rows="3" name="english_subtitle" id="category_english_subtitle" maxlength="80"><?php echo html_escape($english_subtitle); ?></textarea>
	</div>
	<div class="form-group">
		<label for="category_english_display_slug">Display slug (English)</label>
		<input class="form-control" value="<?php echo html_escape($english_display_slug); ?>" type="text" id="category_english_display_slug" name="english_display_slug">
		<small class="text-muted">Display only. Public routes keep the canonical category slug: <?php echo html_escape($blog_category['slug']); ?></small>
	</div>
	<div class="form-group">
		<label for="category_arabic_title"><?php echo get_phrase('title'); ?> (Arabic)</label>
		<input class="form-control" value="<?php echo html_escape($arabic_title); ?>" type="text" id="category_arabic_title" name="arabic_title" dir="rtl">
	</div>
	<div class="form-group">
		<label for="category_arabic_subtitle"><?php echo get_phrase('subtitle'); ?> (Arabic)</label>
		<textarea class="form-control" rows="3" name="arabic_subtitle" id="category_arabic_subtitle" dir="rtl" maxlength="160"><?php echo html_escape($arabic_subtitle); ?></textarea>
	</div>
	<div class="form-group">
		<label for="category_arabic_display_slug">Display slug (Arabic)</label>
		<input class="form-control" value="<?php echo html_escape($arabic_display_slug); ?>" type="text" id="category_arabic_display_slug" name="arabic_display_slug" dir="rtl">
		<small class="text-muted"><?php echo get_phrase('display_only._public_routes_keep_the_canonical_category_slug.'); ?></small>
	</div>

	<div class="form-group">
		<button type="submit" class="btn btn-primary"><?php echo get_phrase('update'); ?></button>
	</div>
</form>
