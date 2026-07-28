<?php
$schema_status = isset($schema_status) && is_array($schema_status) ? $schema_status : array();
$plans = isset($plans) && is_array($plans) ? $plans : array();
$currency_readiness = isset($currency_readiness) && is_array($currency_readiness) ? $currency_readiness : array();
$schema_ready = !empty($schema_status['phase_2l_applied']);
$expected_currency = !empty($currency_readiness['expected_currency']) ? $currency_readiness['expected_currency'] : 'EGP';
$system_currency = !empty($currency_readiness['system_currency']) ? $currency_readiness['system_currency'] : (function_exists('get_settings') ? get_settings('system_currency') : 'USD');
$system_currency_ready = !empty($currency_readiness['system_currency_ready']);
$non_ready_plan_count = isset($currency_readiness['non_ready_plan_count']) ? (int) $currency_readiness['non_ready_plan_count'] : 0;

if (!function_exists('youngo_subscription_plan_badge')) {
    function youngo_subscription_plan_badge($enabled, $label_true, $label_false)
    {
        $class = $enabled ? 'badge-success-lighten' : 'badge-secondary-lighten';
        $label = $enabled ? $label_true : $label_false;
        return '<span class="badge ' . $class . '">' . html_escape($label) . '</span>';
    }
}

if (!function_exists('youngo_subscription_plan_date')) {
    function youngo_subscription_plan_date($timestamp)
    {
        return !empty($timestamp) ? date('Y-m-d H:i', (int) $timestamp) : '-';
    }
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title">
                    <i class="mdi mdi-crown title_icon"></i> <?php echo html_escape($page_title); ?>
                    <a href="<?php echo site_url('admin/youngo/subscription-plans/create'); ?>" class="btn btn-outline-primary btn-rounded alignToTitle">
                        <i class="mdi mdi-plus"></i> Add Subscription Plan
                    </a>
                </h4>
            </div>
        </div>
    </div>
</div>

<?php if (!$schema_ready): ?>
<div class="alert alert-warning" role="alert">
    Phase 2L archive/audit schema is not applied. Existing plans can be listed, but create, edit, status, archive, and restore actions are disabled by the model until the migration is applied.
</div>
<?php endif; ?>

<?php if (!$system_currency_ready): ?>
<div class="alert alert-warning" role="alert">
    YounGo commercial currency is <?php echo html_escape($expected_currency); ?>. Current system currency is <?php echo html_escape($system_currency); ?>, so creating, editing, activating, and making subscription plans purchasable is blocked until the approved settings flow changes system currency to <?php echo html_escape($expected_currency); ?>. Existing inactive USD placeholders are safe to list and view.
</div>
<?php endif; ?>

<?php if ($non_ready_plan_count > 0): ?>
<div class="alert alert-info" role="alert">
    <?php echo (int) $non_ready_plan_count; ?> subscription plan(s) are not stored in <?php echo html_escape($expected_currency); ?>. They are not commercially ready and cannot be activated or made purchasable until corrected through the dashboard after system currency is <?php echo html_escape($expected_currency); ?>.
</div>
<?php endif; ?>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('subscription_plans'); ?></h4>
                <p class="text-muted mb-3">
                    Current system currency: <strong><?php echo html_escape($system_currency); ?></strong>.
                    Expected YounGo commercial currency: <strong><?php echo html_escape($expected_currency); ?></strong>.
                </p>
                <div class="table-responsive-sm mt-4">
                    <table id="basic-datatable" class="table table-striped table-centered mb-0">
                        <thead>
                            <tr>
                                <th><?php echo get_phrase('id'); ?></th>
                                <th><?php echo get_phrase('name_/_slug'); ?></th>
                                <th><?php echo get_phrase('duration'); ?></th>
                                <th><?php echo get_phrase('price'); ?></th>
                                <th><?php echo get_phrase('active'); ?></th>
                                <th><?php echo get_phrase('purchasable'); ?></th>
                                <th><?php echo get_phrase('featured'); ?></th>
                                <th><?php echo get_phrase('sort'); ?></th>
                                <th><?php echo get_phrase('updated'); ?></th>
                                <th><?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plans as $plan): ?>
                            <?php $is_archived = !empty($plan['archived_at']); ?>
                            <?php $plan_currency_ready = isset($plan['currency']) && trim((string) $plan['currency']) === $expected_currency; ?>
                            <?php $can_commercialize = $system_currency_ready && $plan_currency_ready; ?>
                            <tr>
                                <td><?php echo (int) $plan['id']; ?></td>
                                <td>
                                    <strong><?php echo html_escape($plan['name']); ?></strong>
                                    <?php if ($is_archived): ?>
                                        <span class="badge badge-dark-lighten ml-1"><?php echo get_phrase('archived'); ?></span>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted"><?php echo html_escape($plan['slug']); ?></small>
                                </td>
                                <td><?php echo (int) $plan['duration_days']; ?> days</td>
                                <td>
                                    <?php echo html_escape(number_format((float) $plan['price'], 2)); ?> <?php echo html_escape($plan['currency']); ?>
                                    <?php if (!$plan_currency_ready): ?>
                                        <br><span class="badge badge-warning-lighten"><?php echo get_phrase('not_egp-ready'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo youngo_subscription_plan_badge((int) $plan['is_active'] === 1, 'Active', 'Inactive'); ?></td>
                                <td><?php echo youngo_subscription_plan_badge((int) $plan['is_purchasable'] === 1, 'Purchasable', 'Hidden'); ?></td>
                                <td><?php echo youngo_subscription_plan_badge((int) $plan['is_featured'] === 1, 'Featured', 'Normal'); ?></td>
                                <td><?php echo (int) $plan['sort_order']; ?></td>
                                <td><?php echo youngo_subscription_plan_date(isset($plan['updated_at']) ? $plan['updated_at'] : null); ?></td>
                                <td>
                                    <div class="dropright">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-rounded btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="mdi mdi-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="<?php echo site_url('admin/youngo/subscription-plans/' . (int) $plan['id']); ?>">View</a></li>
                                            <?php if (!$is_archived): ?>
                                                <li><a class="dropdown-item" href="<?php echo site_url('admin/youngo/subscription-plans/' . (int) $plan['id'] . '/edit'); ?>">Edit</a></li>
                                            <?php endif; ?>
                                            <?php if ($schema_ready): ?>
                                                <?php if (!$is_archived): ?>
                                                    <li>
                                                        <form method="post" action="<?php echo site_url('admin/youngo/subscription-plans/' . (int) $plan['id'] . '/status'); ?>" class="px-3 py-1">
                                                            <?php $active_action = (int) $plan['is_active'] === 1 ? 'deactivate' : 'activate'; ?>
                                                            <input type="hidden" name="status_action" value="<?php echo $active_action; ?>">
                                                            <button type="submit" class="btn btn-link dropdown-item p-0" <?php echo $active_action === 'activate' && !$can_commercialize ? 'disabled title="' . get_phrase('youngo_subscription_plans_must_use_egp_before_activation.') . '"' : ''; ?>><?php echo $active_action === 'deactivate' ? get_phrase('deactivate') : get_phrase('activate'); ?></button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="post" action="<?php echo site_url('admin/youngo/subscription-plans/' . (int) $plan['id'] . '/status'); ?>" class="px-3 py-1">
                                                            <?php $purchase_action = (int) $plan['is_purchasable'] === 1 ? 'hide_from_purchase' : 'make_purchasable'; ?>
                                                            <input type="hidden" name="status_action" value="<?php echo $purchase_action; ?>">
                                                            <button type="submit" class="btn btn-link dropdown-item p-0" <?php echo $purchase_action === 'make_purchasable' && !$can_commercialize ? 'disabled title="' . get_phrase('youngo_subscription_plans_must_use_egp_before_purchase_is_enabled.') . '"' : ''; ?>><?php echo $purchase_action === 'hide_from_purchase' ? get_phrase('hide_from_purchase') : get_phrase('make_purchasable'); ?></button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="post" action="<?php echo site_url('admin/youngo/subscription-plans/' . (int) $plan['id'] . '/archive'); ?>" class="px-3 py-1" onsubmit="return confirm('Archive this subscription plan?');">
                                                            <button type="submit" class="btn btn-link dropdown-item p-0 text-danger"><?php echo get_phrase('archive'); ?></button>
                                                        </form>
                                                    </li>
                                                <?php else: ?>
                                                    <li>
                                                        <form method="post" action="<?php echo site_url('admin/youngo/subscription-plans/' . (int) $plan['id'] . '/restore'); ?>" class="px-3 py-1">
                                                            <button type="submit" class="btn btn-link dropdown-item p-0"><?php echo get_phrase('restore'); ?></button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($plans)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">No subscription plans were found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
