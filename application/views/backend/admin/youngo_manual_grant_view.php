<?php
$detail = isset($detail) && is_array($detail) ? $detail : array();
$grant = isset($detail['grant']) && is_array($detail['grant']) ? $detail['grant'] : array();
$course_access = isset($detail['course_access']) && is_array($detail['course_access']) ? $detail['course_access'] : array();
$subscription = isset($detail['subscription']) && is_array($detail['subscription']) ? $detail['subscription'] : array();
$read_state = isset($detail['read_state']) && is_array($detail['read_state']) ? $detail['read_state'] : array();
$is_revoked = !empty($grant['revoked_at']) || (isset($grant['status']) && $grant['status'] === 'revoked');

if (!function_exists('youngo_manual_grant_detail_date')) {
    function youngo_manual_grant_detail_date($timestamp)
    {
        return !empty($timestamp) ? date('Y-m-d H:i', (int) $timestamp) : '-';
    }
}

if (!function_exists('youngo_manual_grant_detail_user')) {
    function youngo_manual_grant_detail_user($row, $prefix)
    {
        $name = trim((string) (isset($row[$prefix . '_first_name']) ? $row[$prefix . '_first_name'] : '') . ' ' . (string) (isset($row[$prefix . '_last_name']) ? $row[$prefix . '_last_name'] : ''));
        $email = isset($row[$prefix . '_email']) ? $row[$prefix . '_email'] : '';
        if ($name === '') {
            $name = $email !== '' ? $email : '-';
        }
        return $name . ($email !== '' && $email !== $name ? ' (' . $email . ')' : '');
    }
}

if (!function_exists('youngo_manual_grant_detail_badge')) {
    function youngo_manual_grant_detail_badge($status)
    {
        $status = trim((string) $status);
        $class = $status === 'active' ? 'badge-success-lighten' : ($status === 'revoked' ? 'badge-danger-lighten' : 'badge-secondary-lighten');
        return '<span class="badge ' . $class . '">' . html_escape(ucfirst($status !== '' ? $status : 'unknown')) . '</span>';
    }
}

if (!function_exists('youngo_manual_grant_detail_target')) {
    function youngo_manual_grant_detail_target($grant)
    {
        if ($grant['grant_type'] === 'course') {
            return !empty($grant['course_title']) ? $grant['course_title'] : ('Course #' . (int) $grant['course_id']);
        }
        if ($grant['grant_type'] === 'subscription') {
            $label = !empty($grant['plan_name']) ? $grant['plan_name'] : ('Plan #' . (int) $grant['plan_id']);
            return $label . (!empty($grant['plan_slug']) ? ' (' . $grant['plan_slug'] . ')' : '');
        }
        return '-';
    }
}
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

<div class="alert alert-info" role="alert">
    Revoking a manual grant never deletes progress, enrolment, payment, watch history, subscription plan, or checkout/order rows.
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('grant_summary'); ?></h4>
                <table class="table table-striped table-centered mb-0">
                    <tbody>
                        <tr><th><?php echo get_phrase('id'); ?></th><td><?php echo (int) $grant['id']; ?></td></tr>
                        <tr><th><?php echo get_phrase('type'); ?></th><td><?php echo html_escape(ucfirst($grant['grant_type'])); ?></td></tr>
                        <tr><th><?php echo get_phrase('status'); ?></th><td><?php echo youngo_manual_grant_detail_badge($grant['status']); ?></td></tr>
                        <tr><th><?php echo get_phrase('receiver'); ?></th><td><?php echo html_escape(youngo_manual_grant_detail_user($grant, 'receiver')); ?></td></tr>
                        <tr><th><?php echo get_phrase('granted_by'); ?></th><td><?php echo html_escape(youngo_manual_grant_detail_user($grant, 'actor')); ?></td></tr>
                        <tr><th><?php echo get_phrase('target'); ?></th><td><?php echo html_escape(youngo_manual_grant_detail_target($grant)); ?></td></tr>
                        <tr><th><?php echo get_phrase('lifetime'); ?></th><td><?php echo !empty($grant['is_lifetime']) ? get_phrase('yes') : get_phrase('no'); ?></td></tr>
                        <tr><th><?php echo get_phrase('start'); ?></th><td><?php echo youngo_manual_grant_detail_date($grant['start_date']); ?></td></tr>
                        <tr><th><?php echo get_phrase('expiry'); ?></th><td><?php echo youngo_manual_grant_detail_date($grant['expiry_date']); ?></td></tr>
                        <tr><th><?php echo get_phrase('custom_duration_days'); ?></th><td><?php echo !empty($grant['custom_duration_days']) ? (int) $grant['custom_duration_days'] : '-'; ?></td></tr>
                        <tr><th><?php echo get_phrase('created'); ?></th><td><?php echo youngo_manual_grant_detail_date($grant['created_at']); ?></td></tr>
                        <tr><th><?php echo get_phrase('note'); ?></th><td><?php echo nl2br(html_escape($grant['note'])); ?></td></tr>
                        <tr><th><?php echo get_phrase('revoked'); ?></th><td><?php echo youngo_manual_grant_detail_date($grant['revoked_at']); ?></td></tr>
                        <tr><th><?php echo get_phrase('revoked_by'); ?></th><td><?php echo !empty($grant['revoked_by_user_id']) ? html_escape(youngo_manual_grant_detail_user($grant, 'revoker')) : '-'; ?></td></tr>
                        <tr><th><?php echo get_phrase('revoke_note'); ?></th><td><?php echo nl2br(html_escape($grant['revoke_note'])); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('linked_record'); ?></h4>
                <?php if ($grant['grant_type'] === 'course'): ?>
                    <?php if (empty($course_access)): ?>
                        <p class="text-muted mb-0"><?php echo get_phrase('linked_course_access_record_was_not_found.'); ?></p>
                    <?php else: ?>
                        <table class="table table-sm table-striped mb-0">
                            <tbody>
                                <tr><th><?php echo get_phrase('course_access_id'); ?></th><td><?php echo (int) $course_access['id']; ?></td></tr>
                                <tr><th><?php echo get_phrase('user_id'); ?></th><td><?php echo (int) $course_access['user_id']; ?></td></tr>
                                <tr><th><?php echo get_phrase('course_id'); ?></th><td><?php echo (int) $course_access['course_id']; ?></td></tr>
                                <tr><th><?php echo get_phrase('source'); ?></th><td><?php echo html_escape($course_access['access_source']); ?></td></tr>
                                <tr><th><?php echo get_phrase('status'); ?></th><td><?php echo youngo_manual_grant_detail_badge($course_access['status']); ?></td></tr>
                                <tr><th><?php echo get_phrase('lifetime'); ?></th><td><?php echo !empty($course_access['is_lifetime']) ? get_phrase('yes') : get_phrase('no'); ?></td></tr>
                                <tr><th><?php echo get_phrase('start'); ?></th><td><?php echo youngo_manual_grant_detail_date($course_access['start_date']); ?></td></tr>
                                <tr><th><?php echo get_phrase('expiry'); ?></th><td><?php echo youngo_manual_grant_detail_date($course_access['expiry_date']); ?></td></tr>
                                <tr><th><?php echo get_phrase('revoked'); ?></th><td><?php echo youngo_manual_grant_detail_date($course_access['revoked_at']); ?></td></tr>
                                <tr><th><?php echo get_phrase('revoked_by'); ?></th><td><?php echo !empty($course_access['revoked_by_user_id']) ? (int) $course_access['revoked_by_user_id'] : '-'; ?></td></tr>
                                <tr><th><?php echo get_phrase('revoke_note'); ?></th><td><?php echo nl2br(html_escape($course_access['revoke_note'])); ?></td></tr>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php elseif ($grant['grant_type'] === 'subscription'): ?>
                    <?php if (empty($subscription)): ?>
                        <p class="text-muted mb-0"><?php echo get_phrase('linked_subscription_record_was_not_found.'); ?></p>
                    <?php else: ?>
                        <table class="table table-sm table-striped mb-0">
                            <tbody>
                                <tr><th><?php echo get_phrase('subscription_id'); ?></th><td><?php echo (int) $subscription['id']; ?></td></tr>
                                <tr><th><?php echo get_phrase('user_id'); ?></th><td><?php echo (int) $subscription['user_id']; ?></td></tr>
                                <tr><th><?php echo get_phrase('plan_id'); ?></th><td><?php echo (int) $subscription['plan_id']; ?></td></tr>
                                <tr><th><?php echo get_phrase('source'); ?></th><td><?php echo html_escape($subscription['source']); ?></td></tr>
                                <tr><th><?php echo get_phrase('status'); ?></th><td><?php echo youngo_manual_grant_detail_badge($subscription['status']); ?></td></tr>
                                <tr><th><?php echo get_phrase('duration'); ?></th><td><?php echo !empty($subscription['duration_days']) ? (int) $subscription['duration_days'] . ' days' : '-'; ?></td></tr>
                                <tr><th><?php echo get_phrase('snapshot_price'); ?></th><td><?php echo html_escape(number_format((float) $subscription['price_paid'], 2) . ' ' . $subscription['currency']); ?></td></tr>
                                <tr><th><?php echo get_phrase('start'); ?></th><td><?php echo youngo_manual_grant_detail_date($subscription['start_date']); ?></td></tr>
                                <tr><th><?php echo get_phrase('expiry'); ?></th><td><?php echo youngo_manual_grant_detail_date($subscription['expiry_date']); ?></td></tr>
                                <tr><th><?php echo get_phrase('revoked'); ?></th><td><?php echo youngo_manual_grant_detail_date($subscription['revoked_at']); ?></td></tr>
                                <tr><th><?php echo get_phrase('revoked_by'); ?></th><td><?php echo !empty($subscription['revoked_by_user_id']) ? (int) $subscription['revoked_by_user_id'] : '-'; ?></td></tr>
                                <tr><th><?php echo get_phrase('revoke_note'); ?></th><td><?php echo nl2br(html_escape($subscription['revoke_note'])); ?></td></tr>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('current_read-layer_state'); ?></h4>
                <?php if (empty($read_state)): ?>
                    <p class="text-muted mb-0"><?php echo get_phrase('no_read-layer_state_is_available_for_this_grant.'); ?></p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><th><?php echo get_phrase('has_access'); ?></th><td><?php echo !empty($read_state['has_access']) ? get_phrase('yes') : get_phrase('no'); ?></td></tr>
                            <tr><th><?php echo get_phrase('source'); ?></th><td><?php echo html_escape(isset($read_state['access_source']) ? $read_state['access_source'] : '-'); ?></td></tr>
                            <tr><th><?php echo get_phrase('status'); ?></th><td><?php echo html_escape(isset($read_state['status']) ? $read_state['status'] : '-'); ?></td></tr>
                            <tr><th><?php echo get_phrase('lock_reason'); ?></th><td><?php echo html_escape(isset($read_state['lock_reason']) ? $read_state['lock_reason'] : '-'); ?></td></tr>
                            <tr><th><?php echo get_phrase('record_id'); ?></th><td><?php echo !empty($read_state['source_record_id']) ? (int) $read_state['source_record_id'] : '-'; ?></td></tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('actions'); ?></h4>
                <?php if ($is_revoked): ?>
                    <p class="text-muted mb-0"><?php echo get_phrase('this_manual_grant_is_already_revoked.'); ?></p>
                <?php else: ?>
                    <form method="post" action="<?php echo site_url('admin/youngo/manual-grants/' . (int) $grant['id'] . '/revoke'); ?>" onsubmit="return confirm('Revoke this manual grant? This will not delete progress, enrolment, payment, watch history, plan, or order rows.');">
                        <div class="form-group">
                            <label for="revoke_note"><?php echo get_phrase('revoke_note'); ?></label>
                            <textarea class="form-control" id="revoke_note" name="revoke_note" rows="4" maxlength="2000"></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger"><?php echo get_phrase('revoke_manual_grant'); ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
