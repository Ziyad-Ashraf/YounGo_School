<?php
$plan = isset($plan) && is_array($plan) ? $plan : array();
$schema_status = isset($schema_status) && is_array($schema_status) ? $schema_status : array();
$dependency_counts = isset($dependency_counts) && is_array($dependency_counts) ? $dependency_counts : array();
$audit_history = isset($audit_history) && is_array($audit_history) ? $audit_history : array();
$currency_readiness = isset($currency_readiness) && is_array($currency_readiness) ? $currency_readiness : array();
$schema_ready = !empty($schema_status['phase_2l_applied']);
$expected_currency = !empty($currency_readiness['expected_currency']) ? $currency_readiness['expected_currency'] : 'EGP';
$system_currency = !empty($currency_readiness['system_currency']) ? $currency_readiness['system_currency'] : (function_exists('get_settings') ? get_settings('system_currency') : 'USD');
$system_currency_ready = !empty($currency_readiness['system_currency_ready']);
$is_archived = !empty($plan['archived_at']);
$plan_currency_ready = isset($plan['currency']) && trim((string) $plan['currency']) === $expected_currency;
$can_commercialize = $system_currency_ready && $plan_currency_ready;

if (!function_exists('youngo_subscription_detail_date')) {
    function youngo_subscription_detail_date($timestamp)
    {
        return !empty($timestamp) ? date('Y-m-d H:i', (int) $timestamp) : '-';
    }
}

if (!function_exists('youngo_subscription_detail_yes_no')) {
    function youngo_subscription_detail_yes_no($enabled, $yes, $no)
    {
        return $enabled ? '<span class="badge badge-success-lighten">' . html_escape($yes) . '</span>' : '<span class="badge badge-secondary-lighten">' . html_escape($no) . '</span>';
    }
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title">
                    <i class="mdi mdi-crown title_icon"></i> <?php echo html_escape($page_title); ?>
                    <a href="<?php echo site_url('admin/youngo/subscription-plans'); ?>" class="btn btn-outline-primary btn-rounded alignToTitle">Back to plans</a>
                </h4>
            </div>
        </div>
    </div>
</div>

<?php if (!$schema_ready): ?>
<div class="alert alert-warning" role="alert">
    Phase 2L archive/audit schema is not applied. Details are readable, but plan changes are disabled until the migration is applied.
</div>
<?php endif; ?>

<?php if (!$system_currency_ready): ?>
<div class="alert alert-warning" role="alert">
    YounGo commercial currency is <?php echo html_escape($expected_currency); ?>. Current system currency is <?php echo html_escape($system_currency); ?>, so editing, activating, and making plans purchasable is blocked until the approved settings flow changes system currency to <?php echo html_escape($expected_currency); ?>.
</div>
<?php endif; ?>

<?php if (!$plan_currency_ready): ?>
<div class="alert alert-info" role="alert">
    This plan is stored in <?php echo html_escape(isset($plan['currency']) ? $plan['currency'] : ''); ?>, not <?php echo html_escape($expected_currency); ?>. It may remain inactive/non-purchasable, but it cannot be activated or made purchasable until corrected through the dashboard after system currency is <?php echo html_escape($expected_currency); ?>.
</div>
<?php endif; ?>

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo html_escape($plan['name']); ?></h4>
                <p class="text-muted">
                    Current system currency: <strong><?php echo html_escape($system_currency); ?></strong>.
                    Expected YounGo commercial currency: <strong><?php echo html_escape($expected_currency); ?></strong>.
                </p>
                <table class="table table-striped table-centered mb-0">
                    <tbody>
                        <tr><th><?php echo get_phrase('id'); ?></th><td><?php echo (int) $plan['id']; ?></td></tr>
                        <tr><th><?php echo get_phrase('slug'); ?></th><td><?php echo html_escape($plan['slug']); ?></td></tr>
                        <tr><th><?php echo get_phrase('duration'); ?></th><td><?php echo (int) $plan['duration_days']; ?> days</td></tr>
                        <tr>
                            <th><?php echo get_phrase('price'); ?></th>
                            <td>
                                <?php echo html_escape(number_format((float) $plan['price'], 2)); ?> <?php echo html_escape($plan['currency']); ?>
                                <?php if (!$plan_currency_ready): ?>
                                    <span class="badge badge-warning-lighten ml-1"><?php echo get_phrase('not_egp-ready'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr><th><?php echo get_phrase('active'); ?></th><td><?php echo youngo_subscription_detail_yes_no((int) $plan['is_active'] === 1, 'Active', 'Inactive'); ?></td></tr>
                        <tr><th><?php echo get_phrase('purchasable'); ?></th><td><?php echo youngo_subscription_detail_yes_no((int) $plan['is_purchasable'] === 1, 'Purchasable', 'Hidden'); ?></td></tr>
                        <tr><th><?php echo get_phrase('featured'); ?></th><td><?php echo youngo_subscription_detail_yes_no((int) $plan['is_featured'] === 1, 'Featured', 'Normal'); ?></td></tr>
                        <tr><th><?php echo get_phrase('sort_order'); ?></th><td><?php echo (int) $plan['sort_order']; ?></td></tr>
                        <tr><th><?php echo get_phrase('created'); ?></th><td><?php echo youngo_subscription_detail_date(isset($plan['created_at']) ? $plan['created_at'] : null); ?></td></tr>
                        <tr><th><?php echo get_phrase('updated'); ?></th><td><?php echo youngo_subscription_detail_date(isset($plan['updated_at']) ? $plan['updated_at'] : null); ?></td></tr>
                        <tr><th><?php echo get_phrase('archived'); ?></th><td><?php echo $is_archived ? youngo_subscription_detail_date($plan['archived_at']) : '-'; ?></td></tr>
                        <tr><th><?php echo get_phrase('archived_by_user'); ?></th><td><?php echo !empty($plan['archived_by_user_id']) ? (int) $plan['archived_by_user_id'] : '-'; ?></td></tr>
                    </tbody>
                </table>

                <div class="mt-3">
                    <?php if (!$is_archived): ?>
                        <a href="<?php echo site_url('admin/youngo/subscription-plans/' . (int) $plan['id'] . '/edit'); ?>" class="btn btn-outline-primary <?php echo !$system_currency_ready ? 'disabled' : ''; ?>" <?php echo !$system_currency_ready ? 'aria-disabled="true" title="<?php echo get_phrase('system_currency_must_be_egp_before_editing_subscription_plans.'); ?>"' : ''; ?>>Edit</a>
                    <?php endif; ?>
                    <?php if ($schema_ready): ?>
                        <?php if (!$is_archived): ?>
                            <form method="post" action="<?php echo site_url('admin/youngo/subscription-plans/' . (int) $plan['id'] . '/status'); ?>" class="d-inline">
                                <?php $active_action = (int) $plan['is_active'] === 1 ? 'deactivate' : 'activate'; ?>
                                <input type="hidden" name="status_action" value="<?php echo $active_action; ?>">
                                <button type="submit" class="btn btn-outline-secondary" <?php echo $active_action === 'activate' && !$can_commercialize ? 'disabled title="' . get_phrase('youngo_subscription_plans_must_use_egp_before_activation.') . '"' : ''; ?>><?php echo $active_action === 'deactivate' ? get_phrase('deactivate') : get_phrase('activate'); ?></button>
                            </form>
                            <form method="post" action="<?php echo site_url('admin/youngo/subscription-plans/' . (int) $plan['id'] . '/status'); ?>" class="d-inline">
                                <?php $purchase_action = (int) $plan['is_purchasable'] === 1 ? 'hide_from_purchase' : 'make_purchasable'; ?>
                                <input type="hidden" name="status_action" value="<?php echo $purchase_action; ?>">
                                <button type="submit" class="btn btn-outline-secondary" <?php echo $purchase_action === 'make_purchasable' && !$can_commercialize ? 'disabled title="' . get_phrase('youngo_subscription_plans_must_use_egp_before_purchase_is_enabled.') . '"' : ''; ?>><?php echo $purchase_action === 'hide_from_purchase' ? get_phrase('hide_from_purchase') : get_phrase('make_purchasable'); ?></button>
                            </form>
                            <form method="post" action="<?php echo site_url('admin/youngo/subscription-plans/' . (int) $plan['id'] . '/archive'); ?>" class="d-inline" onsubmit="return confirm('Archive this subscription plan?');">
                                <button type="submit" class="btn btn-outline-danger"><?php echo get_phrase('archive'); ?></button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?php echo site_url('admin/youngo/subscription-plans/' . (int) $plan['id'] . '/restore'); ?>" class="d-inline">
                                <button type="submit" class="btn btn-outline-primary"><?php echo get_phrase('restore'); ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('dependencies'); ?></h4>
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php foreach ($dependency_counts as $table => $count): ?>
                        <tr>
                            <th><?php echo html_escape($table); ?></th>
                            <td><?php echo $count === null ? get_phrase('table_missing') : (int) $count; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('audit_history'); ?></h4>
                <?php if (!$schema_ready): ?>
                    <p class="text-muted mb-0"><?php echo get_phrase('audit_table_is_not_available_yet.'); ?></p>
                <?php elseif (empty($audit_history)): ?>
                    <p class="text-muted mb-0"><?php echo get_phrase('no_audit_events_found.'); ?></p>
                <?php else: ?>
                    <div class="table-responsive-sm">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('action'); ?></th>
                                    <th><?php echo get_phrase('actor'); ?></th>
                                    <th><?php echo get_phrase('created'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($audit_history as $event): ?>
                                <tr>
                                    <td><?php echo html_escape($event['action']); ?></td>
                                    <td><?php echo !empty($event['actor_user_id']) ? (int) $event['actor_user_id'] : '-'; ?></td>
                                    <td><?php echo youngo_subscription_detail_date($event['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
