<?php
$summary = isset($youngo_course_entitlement_summary) && is_array($youngo_course_entitlement_summary) ? $youngo_course_entitlement_summary : array();

if (!function_exists('youngo_admin_course_summary_count')) {
    function youngo_admin_course_summary_count($summary, $key)
    {
        return isset($summary[$key]) ? (int) $summary[$key] : 0;
    }
}

if (!function_exists('youngo_admin_course_summary_date')) {
    function youngo_admin_course_summary_date($timestamp)
    {
        return !empty($timestamp) ? date('Y-m-d H:i', (int) $timestamp) : '-';
    }
}

if (!function_exists('youngo_admin_course_summary_badge')) {
    function youngo_admin_course_summary_badge($status)
    {
        $status = trim((string) $status);
        $class = $status === 'active' ? 'badge-success-lighten' : ($status === 'revoked' ? 'badge-danger-lighten' : ($status === 'expired' ? 'badge-warning-lighten' : 'badge-secondary-lighten'));
        return '<span class="badge ' . $class . '">' . html_escape(ucfirst($status !== '' ? $status : 'unknown')) . '</span>';
    }
}

if (!function_exists('youngo_admin_access_mode_label')) {
    function youngo_admin_access_mode_label($mode)
    {
        $labels = array(
            'subscription_only' => 'Subscription only',
            'subscription_and_purchase' => 'Subscription and purchase',
            'purchase_only' => 'Purchase only',
        );
        return isset($labels[$mode]) ? $labels[$mode] : 'Not configured';
    }
}

$recent_grants = isset($summary['recent_manual_grants']) && is_array($summary['recent_manual_grants']) ? $summary['recent_manual_grants'] : array();
$manual_grants_url = !empty($summary['manual_grants_url']) ? $summary['manual_grants_url'] : site_url('admin/youngo/manual-grants');
?>

<div class="card border mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h4 class="header-title mb-1"><?php echo get_phrase('youngo_course_access_summary'); ?></h4>
                <p class="text-muted mb-3"><?php echo get_phrase('read-only_course_access_snapshot._subscription_eligibility_is_not_treated_as_enrolment.'); ?></p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="<?php echo html_escape($manual_grants_url); ?>">View Manual Grants</a>
        </div>

        <div class="row">
            <div class="col-md-3 mb-2">
                <div class="border rounded p-2 h-100">
                    <small class="text-muted"><?php echo get_phrase('legacy_enrolments'); ?></small>
                    <h4 class="mb-0"><?php echo youngo_admin_course_summary_count($summary, 'legacy_enrol_count'); ?></h4>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="border rounded p-2 h-100">
                    <small class="text-muted"><?php echo get_phrase('active_course_access'); ?></small>
                    <h4 class="mb-0"><?php echo youngo_admin_course_summary_count($summary, 'active_course_access_count'); ?></h4>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="border rounded p-2 h-100">
                    <small class="text-muted"><?php echo get_phrase('access_ended'); ?></small>
                    <h4 class="mb-0"><?php echo youngo_admin_course_summary_count($summary, 'revoked_course_access_count') + youngo_admin_course_summary_count($summary, 'expired_course_access_count'); ?></h4>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="border rounded p-2 h-100">
                    <small class="text-muted"><?php echo get_phrase('subscription_eligible'); ?></small>
                    <h4 class="mb-0"><?php echo !empty($summary['subscription_eligible']) ? get_phrase('yes') : get_phrase('no'); ?></h4>
                    <small class="text-muted"><?php echo html_escape(youngo_admin_access_mode_label(isset($summary['youngo_access_mode']) ? $summary['youngo_access_mode'] : '')); ?></small>
                </div>
            </div>
        </div>

        <h5 class="mb-2"><?php echo get_phrase('recent_manual_grants_for_this_course'); ?></h5>
        <?php if (empty($recent_grants)): ?>
            <p class="text-muted mb-0"><?php echo get_phrase('no_recent_manual_course_grants_were_found.'); ?></p>
        <?php else: ?>
            <div class="table-responsive-sm">
                <table class="table table-sm table-centered mb-0">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('id'); ?></th>
                            <th><?php echo get_phrase('target'); ?></th>
                            <th><?php echo get_phrase('status'); ?></th>
                            <th><?php echo get_phrase('created'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_grants as $grant): ?>
                            <tr>
                                <td><?php echo (int) $grant['id']; ?></td>
                                <td><?php echo html_escape($grant['target_label']); ?></td>
                                <td><?php echo youngo_admin_course_summary_badge($grant['status']); ?></td>
                                <td><?php echo youngo_admin_course_summary_date($grant['created_at']); ?></td>
                                <td><a class="btn btn-xs btn-outline-primary" href="<?php echo html_escape($grant['url']); ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
