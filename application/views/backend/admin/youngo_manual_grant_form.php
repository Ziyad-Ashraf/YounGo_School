<?php
$users = isset($users) && is_array($users) ? $users : array();
$courses = isset($courses) && is_array($courses) ? $courses : array();
$plans = isset($plans) && is_array($plans) ? $plans : array();
$schema_readiness = isset($schema_readiness) && is_array($schema_readiness) ? $schema_readiness : array();
$schema_ready = !empty($schema_readiness['base_ready']) && !empty($schema_readiness['phase_2m_applied']);
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title">
                    <i class="mdi mdi-account-key title_icon"></i> <?php echo html_escape($page_title); ?>
                    <a href="<?php echo site_url('admin/youngo/manual-grants'); ?>" class="btn btn-outline-primary btn-rounded alignToTitle">Back to grants</a>
                </h4>
            </div>
        </div>
    </div>
</div>

<?php if (!$schema_ready): ?>
<div class="alert alert-warning" role="alert">
    Phase 2M entitlement write schema is not ready. Manual grant submission is disabled until the schema diagnostic passes.
</div>
<?php endif; ?>

<div class="alert alert-info" role="alert">
    Manual grants do not create checkout orders, payments, coupons, enrolment rows, or progress rows. Subscription plan prices are local placeholders until final commercial prices are approved.
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('create_manual_grant'); ?></h4>
                <form class="required-form" action="<?php echo site_url('admin/youngo/manual-grants/create'); ?>" method="post">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="grant_type"><?php echo get_phrase('grant_type'); ?></label>
                            <select class="form-control" id="grant_type" name="grant_type" required <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                                <option value="course">Course access</option>
                                <option value="subscription">Subscription</option>
                            </select>
                        </div>
                        <div class="form-group col-md-8">
                            <label for="user_id"><?php echo get_phrase('receiver_user'); ?></label>
                            <select class="form-control" id="user_id" name="user_id" required <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                                <option value="">Select user</option>
                                <?php foreach ($users as $user): ?>
                                    <?php $user_name = trim((string) $user['first_name'] . ' ' . (string) $user['last_name']); ?>
                                    <option value="<?php echo (int) $user['id']; ?>">
                                        <?php echo html_escape(($user_name !== '' ? $user_name : $user['email']) . ' (' . $user['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted"><?php echo get_phrase('root_admin_is_intentionally_not_listed_as_a_default_receiver.'); ?></small>
                        </div>
                    </div>

                    <div id="course-grant-fields">
                        <div class="form-group">
                            <label for="course_id"><?php echo get_phrase('course'); ?></label>
                            <select class="form-control" id="course_id" name="course_id" <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                                <option value="">Select course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo (int) $course['id']; ?>"><?php echo html_escape($course['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Manual course grants may override normal purchase/subscription mode, but they do not create legacy enrolment rows.</small>
                        </div>

                        <div class="form-group">
                            <label for="course_access_type"><?php echo get_phrase('course_access_type'); ?></label>
                            <select class="form-control" id="course_access_type" name="course_access_type" <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                                <option value="lifetime">Lifetime</option>
                                <option value="timed">Timed</option>
                            </select>
                        </div>
                    </div>

                    <div id="subscription-grant-fields" style="display:none;">
                        <div class="form-group">
                            <label for="plan_id"><?php echo get_phrase('subscription_plan'); ?></label>
                            <select class="form-control" id="plan_id" name="plan_id" <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                                <option value="">Select EGP plan</option>
                                <?php foreach ($plans as $plan): ?>
                                    <option value="<?php echo (int) $plan['id']; ?>">
                                        <?php echo html_escape($plan['name'] . ' - ' . (int) $plan['duration_days'] . ' days - ' . number_format((float) $plan['price'], 2) . ' ' . $plan['currency']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Inactive/non-purchasable EGP plans may be used for manual grants. This does not make the plan purchasable.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="start_date"><?php echo get_phrase('start_date'); ?></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                            <small class="form-text text-muted"><?php echo get_phrase('leave_empty_to_start_now.'); ?></small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="expiry_date"><?php echo get_phrase('expiry_date'); ?></label>
                            <input type="date" class="form-control" id="expiry_date" name="expiry_date" <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                            <small class="form-text text-muted">Required for timed course grants unless duration is provided. Optional for subscription grants; plan duration is used by default.</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="duration_days"><?php echo get_phrase('duration_days'); ?></label>
                            <input type="number" class="form-control" id="duration_days" name="duration_days" min="1" step="1" <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                            <small class="form-text text-muted"><?php echo get_phrase('positive_whole_number_only.'); ?></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="note"><?php echo get_phrase('reason_/_note'); ?></label>
                        <textarea class="form-control" id="note" name="note" rows="4" maxlength="2000" <?php echo !$schema_ready ? 'disabled' : ''; ?>></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" <?php echo !$schema_ready ? 'disabled' : ''; ?>><?php echo get_phrase('create_manual_grant'); ?></button>
                    <a href="<?php echo site_url('admin/youngo/manual-grants'); ?>" class="btn btn-outline-secondary ml-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var type = document.getElementById('grant_type');
    var courseFields = document.getElementById('course-grant-fields');
    var subscriptionFields = document.getElementById('subscription-grant-fields');
    var courseSelect = document.getElementById('course_id');
    var planSelect = document.getElementById('plan_id');
    var accessType = document.getElementById('course_access_type');
    var expiry = document.getElementById('expiry_date');
    var duration = document.getElementById('duration_days');

    function syncGrantType() {
        var isSubscription = type && type.value === 'subscription';
        if (courseFields) courseFields.style.display = isSubscription ? 'none' : '';
        if (subscriptionFields) subscriptionFields.style.display = isSubscription ? '' : 'none';
        if (courseSelect) courseSelect.required = !isSubscription;
        if (planSelect) planSelect.required = isSubscription;
        syncAccessType();
    }

    function syncAccessType() {
        var isCourse = type && type.value === 'course';
        var isTimed = accessType && accessType.value === 'timed';
        if (expiry) expiry.required = isCourse && isTimed && !(duration && duration.value);
    }

    if (type) type.addEventListener('change', syncGrantType);
    if (accessType) accessType.addEventListener('change', syncAccessType);
    if (duration) duration.addEventListener('input', syncAccessType);
    syncGrantType();
})();
</script>
