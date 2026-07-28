<?php
$mode = isset($mode) && $mode === 'edit' ? 'edit' : 'create';
$plan = isset($plan) && is_array($plan) ? $plan : array();
$plan_translations = isset($plan_translations) && is_array($plan_translations) ? $plan_translations : array();
$schema_status = isset($schema_status) && is_array($schema_status) ? $schema_status : array();
$dependency_counts = isset($dependency_counts) && is_array($dependency_counts) ? $dependency_counts : array();
$currency_readiness = isset($currency_readiness) && is_array($currency_readiness) ? $currency_readiness : array();
$schema_ready = !empty($schema_status['phase_2l_applied']);
$expected_currency = !empty($currency_readiness['expected_currency']) ? $currency_readiness['expected_currency'] : 'EGP';
$system_currency = !empty($currency_readiness['system_currency']) ? $currency_readiness['system_currency'] : (function_exists('get_settings') ? get_settings('system_currency') : 'USD');
$system_currency_ready = !empty($currency_readiness['system_currency_ready']);
$is_edit = $mode === 'edit';
$is_archived = !empty($plan['archived_at']);
$dependency_total = 0;
foreach ($dependency_counts as $count) {
    if ($count !== null) {
        $dependency_total += (int) $count;
    }
}
$slug_locked = $is_edit && $dependency_total > 0;
$form_disabled = !$schema_ready || $is_archived || !$system_currency_ready;
$action_url = $is_edit ? site_url('admin/youngo/subscription-plans/' . (int) $plan['id'] . '/edit') : site_url('admin/youngo/subscription-plans/create');
$currency = $system_currency;
$plan_currency_ready = !$is_edit || (isset($plan['currency']) && trim((string) $plan['currency']) === $expected_currency);

if (!function_exists('youngo_subscription_translation_value')) {
    function youngo_subscription_translation_value($translations, $language, $field)
    {
        return isset($translations[$language][$field]) ? $translations[$language][$field] : '';
    }
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title">
                    <i class="mdi mdi-crown title_icon"></i> <?php echo html_escape($page_title); ?>
                    <a href="<?php echo $is_edit ? site_url('admin/youngo/subscription-plans/' . (int) $plan['id']) : site_url('admin/youngo/subscription-plans'); ?>" class="btn btn-outline-primary btn-rounded alignToTitle">Back</a>
                </h4>
            </div>
        </div>
    </div>
</div>

<?php if (!$schema_ready): ?>
<div class="alert alert-warning" role="alert">
    Phase 2L archive/audit schema is not applied. This form is visible for review, but saving is disabled until the migration is applied.
</div>
<?php endif; ?>

<?php if (!$system_currency_ready): ?>
<div class="alert alert-warning" role="alert">
    YounGo commercial currency is <?php echo html_escape($expected_currency); ?>. Current system currency is <?php echo html_escape($system_currency); ?>, so saving subscription plans is blocked. Change system currency to <?php echo html_escape($expected_currency); ?> through the approved settings flow before creating or editing plans.
</div>
<?php endif; ?>

<?php if ($is_edit && !$plan_currency_ready): ?>
<div class="alert alert-info" role="alert">
    This plan is currently stored in <?php echo html_escape(isset($plan['currency']) ? $plan['currency'] : ''); ?>. It can remain inactive and non-purchasable, but it is not commercially ready until saved as <?php echo html_escape($expected_currency); ?> after system currency is aligned.
</div>
<?php endif; ?>

<?php if ($is_archived): ?>
<div class="alert alert-secondary" role="alert">
    Archived plans cannot be edited. Restore the plan first if changes are required.
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo $is_edit ? get_phrase('edit_plan_definition') : get_phrase('create_plan_definition'); ?></h4>
                <p class="text-muted">
                    Current system currency: <strong><?php echo html_escape($system_currency); ?></strong>.
                    Expected YounGo commercial currency: <strong><?php echo html_escape($expected_currency); ?></strong>.
                </p>
                <form class="required-form" action="<?php echo $action_url; ?>" method="post">
                    <div class="form-group">
                        <label for="name"><?php echo get_phrase('plan_name'); ?><span class="required">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" maxlength="255" value="<?php echo html_escape(isset($plan['name']) ? $plan['name'] : ''); ?>" required <?php echo $form_disabled ? 'disabled' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label for="slug"><?php echo get_phrase('slug'); ?><span class="required">*</span></label>
                        <input type="text" class="form-control" id="slug" name="slug" maxlength="100" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?php echo html_escape(isset($plan['slug']) ? $plan['slug'] : ''); ?>" required <?php echo ($form_disabled || $slug_locked) ? 'disabled' : ''; ?>>
                        <?php if ($slug_locked): ?>
                            <small class="form-text text-muted">Slug is locked because this plan already has dependent records.</small>
                            <input type="hidden" name="slug" value="<?php echo html_escape($plan['slug']); ?>">
                        <?php else: ?>
                            <small class="form-text text-muted"><?php echo get_phrase('use_lowercase_letters,_numbers,_and_hyphens_only.'); ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="duration_days"><?php echo get_phrase('duration_in_days'); ?><span class="required">*</span></label>
                            <input type="number" class="form-control" id="duration_days" name="duration_days" min="1" step="1" value="<?php echo html_escape(isset($plan['duration_days']) ? $plan['duration_days'] : ''); ?>" required <?php echo $form_disabled ? 'disabled' : ''; ?>>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="price">Price (<?php echo html_escape($currency); ?>)<span class="required">*</span></label>
                            <input type="text" class="form-control" id="price" name="price" value="<?php echo html_escape(isset($plan['price']) ? number_format((float) $plan['price'], 2, '.', '') : ''); ?>" required <?php echo $form_disabled ? 'disabled' : ''; ?>>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sort_order"><?php echo get_phrase('sort_order'); ?></label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" step="1" value="<?php echo html_escape(isset($plan['sort_order']) ? $plan['sort_order'] : '0'); ?>" <?php echo $form_disabled ? 'disabled' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" <?php echo !empty($plan['is_active']) ? 'checked' : ''; ?> <?php echo $form_disabled ? 'disabled' : ''; ?>>
                            <label class="custom-control-label" for="is_active"><?php echo get_phrase('active'); ?></label>
                        </div>
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="is_purchasable" name="is_purchasable" value="1" <?php echo !empty($plan['is_purchasable']) ? 'checked' : ''; ?> <?php echo $form_disabled ? 'disabled' : ''; ?>>
                            <label class="custom-control-label" for="is_purchasable"><?php echo get_phrase('purchasable'); ?></label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" value="1" <?php echo !empty($plan['is_featured']) ? 'checked' : ''; ?> <?php echo $form_disabled ? 'disabled' : ''; ?>>
                            <label class="custom-control-label" for="is_featured"><?php echo get_phrase('featured_/_recommended'); ?></label>
                        </div>
                        <small class="form-text text-muted">Purchasable requires active. Archived plans cannot be active or purchasable.</small>
                    </div>

                    <hr>
                    <h4 class="mb-3 header-title"><?php echo get_phrase('localized_display_copy'); ?></h4>
                    <p class="text-muted">
                        These fields affect public subscription plan names and descriptions only. Slug, price, duration, currency, status, featured state, and sort order remain shared.
                    </p>

                    <div class="form-group">
                        <label for="translation_english_name"><?php echo get_phrase('english_display_name'); ?></label>
                        <input type="text" class="form-control" id="translation_english_name" name="translations[english][name]" maxlength="255" value="<?php echo html_escape(youngo_subscription_translation_value($plan_translations, 'english', 'name')); ?>" <?php echo $form_disabled ? 'disabled' : ''; ?>>
                        <small class="form-text text-muted"><?php echo get_phrase('leave_blank_to_use_the_canonical_plan_name.'); ?></small>
                    </div>

                    <div class="form-group">
                        <label for="translation_english_short_description"><?php echo get_phrase('english_short_description'); ?></label>
                        <textarea class="form-control" id="translation_english_short_description" name="translations[english][short_description]" rows="2" maxlength="500" <?php echo $form_disabled ? 'disabled' : ''; ?>><?php echo html_escape(youngo_subscription_translation_value($plan_translations, 'english', 'short_description')); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="translation_english_description"><?php echo get_phrase('english_description'); ?></label>
                        <textarea class="form-control" id="translation_english_description" name="translations[english][description]" rows="4" <?php echo $form_disabled ? 'disabled' : ''; ?>><?php echo html_escape(youngo_subscription_translation_value($plan_translations, 'english', 'description')); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="translation_english_badge_label"><?php echo get_phrase('english_badge_label'); ?></label>
                        <input type="text" class="form-control" id="translation_english_badge_label" name="translations[english][badge_label]" maxlength="100" value="<?php echo html_escape(youngo_subscription_translation_value($plan_translations, 'english', 'badge_label')); ?>" <?php echo $form_disabled ? 'disabled' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label for="translation_arabic_name"><?php echo get_phrase('arabic_display_name'); ?></label>
                        <input type="text" class="form-control text-right" id="translation_arabic_name" name="translations[arabic][name]" maxlength="255" dir="rtl" value="<?php echo html_escape(youngo_subscription_translation_value($plan_translations, 'arabic', 'name')); ?>" <?php echo $form_disabled ? 'disabled' : ''; ?>>
                        <small class="form-text text-muted"><?php echo get_phrase('leave_blank_to_use_the_canonical_plan_name_on_arabic_pages.'); ?></small>
                    </div>

                    <div class="form-group">
                        <label for="translation_arabic_short_description"><?php echo get_phrase('arabic_short_description'); ?></label>
                        <textarea class="form-control text-right" id="translation_arabic_short_description" name="translations[arabic][short_description]" rows="2" maxlength="500" dir="rtl" <?php echo $form_disabled ? 'disabled' : ''; ?>><?php echo html_escape(youngo_subscription_translation_value($plan_translations, 'arabic', 'short_description')); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="translation_arabic_description"><?php echo get_phrase('arabic_description'); ?></label>
                        <textarea class="form-control text-right" id="translation_arabic_description" name="translations[arabic][description]" rows="4" dir="rtl" <?php echo $form_disabled ? 'disabled' : ''; ?>><?php echo html_escape(youngo_subscription_translation_value($plan_translations, 'arabic', 'description')); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="translation_arabic_badge_label"><?php echo get_phrase('arabic_badge_label'); ?></label>
                        <input type="text" class="form-control text-right" id="translation_arabic_badge_label" name="translations[arabic][badge_label]" maxlength="100" dir="rtl" value="<?php echo html_escape(youngo_subscription_translation_value($plan_translations, 'arabic', 'badge_label')); ?>" <?php echo $form_disabled ? 'disabled' : ''; ?>>
                    </div>

                    <button type="submit" class="btn btn-primary" <?php echo $form_disabled ? 'disabled' : ''; ?>><?php echo get_phrase('save_plan'); ?></button>
                    <a href="<?php echo site_url('admin/youngo/subscription-plans'); ?>" class="btn btn-outline-secondary ml-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
