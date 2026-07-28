<?php
$usage_rows = isset($usage_rows) && is_array($usage_rows) ? $usage_rows : array();
$usage_counts = isset($usage_counts) && is_array($usage_counts) ? $usage_counts : array();
$usage_filter = isset($usage_filter) ? (string) $usage_filter : 'all';

if (!function_exists('youngo_coupon_usage_date')) {
    function youngo_coupon_usage_date($timestamp)
    {
        return !empty($timestamp) ? date('Y-m-d H:i', (int) $timestamp) : '-';
    }
}

if (!function_exists('youngo_coupon_usage_amount')) {
    function youngo_coupon_usage_amount($amount, $currency = 'EGP')
    {
        return $amount !== null && $amount !== '' ? html_escape(number_format((float) $amount, 2) . ' ' . $currency) : '-';
    }
}

if (!function_exists('youngo_coupon_usage_count')) {
    function youngo_coupon_usage_count($counts, $key)
    {
        return isset($counts[$key]) ? (int) $counts[$key] : 0;
    }
}

if (!function_exists('youngo_coupon_usage_badge')) {
    function youngo_coupon_usage_badge($status)
    {
        $status = trim((string) $status);
        $classes = array(
            'paid' => 'badge-success-lighten',
            'draft' => 'badge-secondary-lighten',
            'pending_gateway' => 'badge-warning-lighten',
            'awaiting_webhook' => 'badge-warning-lighten',
            'failed' => 'badge-danger-lighten',
            'cancelled' => 'badge-secondary-lighten',
        );
        $class = isset($classes[$status]) ? $classes[$status] : 'badge-secondary-lighten';

        return '<span class="badge ycu-badge ' . $class . '">' . html_escape($status !== '' ? str_replace('_', ' ', $status) : 'unknown') . '</span>';
    }
}

if (!function_exists('youngo_coupon_usage_item_tag')) {
    function youngo_coupon_usage_item_tag($item_type)
    {
        $item_type = trim((string) $item_type);
        $class = $item_type === 'subscription' ? 'ycu-tag-subscription' : 'ycu-tag-course';

        return '<span class="ycu-tag ' . $class . '">' . html_escape($item_type !== '' ? $item_type : 'unknown') . '</span>';
    }
}

if (!function_exists('youngo_coupon_usage_access_label')) {
    function youngo_coupon_usage_access_label($row)
    {
        $type = isset($row['access_reference_type']) ? (string) $row['access_reference_type'] : '';
        $id = isset($row['access_reference_id']) ? (int) $row['access_reference_id'] : 0;
        if ($type === '' || $id <= 0) {
            return '<span class="text-muted">-</span>';
        }

        $status = isset($row['access_status']) && (string) $row['access_status'] !== '' ? ' - ' . (string) $row['access_status'] : '';
        return '<span class="ycu-access-ref">' . html_escape(str_replace('_', ' ', $type)) . ' #' . $id . html_escape($status) . '</span>';
    }
}

if (!function_exists('youngo_coupon_usage_secondary')) {
    function youngo_coupon_usage_secondary($label, $value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            $value = '-';
        }

        return '<span class="ycu-small"><span>' . html_escape($label) . ':</span> ' . html_escape($value) . '</span>';
    }
}
?>

<style>
    .ycu-header-card .card-body { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .75rem; }
    .ycu-header-card .page-title { margin-bottom: .25rem; }
    .ycu-header-copy { min-width: 240px; }
    .ycu-subtitle { margin: 0; color: #98a6ad; font-size: .86rem; }
    .ycu-alert { display: flex; align-items: center; gap: .5rem; border: 1px solid rgba(57, 175, 209, .25); background-color: rgba(57, 175, 209, .08); color: #2b7d92; }
    .ycu-filters { display: inline-flex; flex-wrap: wrap; gap: .375rem; padding: .3rem; background-color: #f5f6fb; border-radius: .6rem; margin-bottom: 1.25rem; }
    .ycu-filter { display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .85rem; border-radius: .45rem; font-size: .8125rem; font-weight: 600; color: #6c757d; text-decoration: none; transition: background-color .15s ease, color .15s ease; }
    .ycu-filter:hover { color: #495057; text-decoration: none; }
    .ycu-filter-count { min-width: 1.35rem; padding: .05rem .35rem; border-radius: 1rem; font-size: .6875rem; font-weight: 700; text-align: center; background-color: rgba(108, 117, 125, .14); color: #6c757d; }
    .ycu-filter.is-active { background-color: #727cf5; color: #fff; box-shadow: 0 2px 6px rgba(114, 124, 245, .35); }
    .ycu-filter.is-active .ycu-filter-count { background-color: rgba(255, 255, 255, .22); color: #fff; }

    .ycu-card .dataTables_wrapper .row:first-child { margin-bottom: 1rem; align-items: center; }
    .ycu-card .dataTables_length select,
    .ycu-card .dataTables_filter input { border-radius: .4rem; }
    .ycu-card .dataTables_wrapper .row:last-child { margin-top: 1rem; align-items: center; }
    .ycu-card .dataTables_info { color: #98a6ad; font-size: .8rem; }

    .ycu-table-wrap { border: 1px solid #eef0f7; border-radius: .5rem; padding: 1rem 1rem 0; overflow-x: auto; }
    .ycu-table { width: 100% !important; margin-bottom: 0; border-color: #eef0f7; table-layout: auto; }
    .ycu-table thead th { background-color: #f5f6fb; color: #4b5165; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; padding: .8rem 1rem; }
    .ycu-table tbody td { padding: .85rem 1rem; vertical-align: top; border-top: 1px solid #f3f4f9; font-size: .84rem; }
    .ycu-table tbody tr:nth-of-type(odd) { background-color: #fbfbfe; }
    .ycu-table tbody tr:hover { background-color: #f2f3fc; }
    .ycu-order-id, .ycu-user, .ycu-item-title, .ycu-coupon, .ycu-cell-main { display: block; font-weight: 700; color: #313a46; }
    .ycu-order-ref, .ycu-user-email, .ycu-small { display: block; color: #98a6ad; font-size: .76rem; line-height: 1.45; }
    .ycu-small span { color: #6c757d; font-weight: 600; }
    .ycu-order-ref { max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: var(--font-family-monospace); }
    .ycu-user-email { max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ycu-tag { display: inline-block; padding: .1rem .5rem; border-radius: .3rem; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
    .ycu-tag-subscription { background-color: rgba(114, 124, 245, .14); color: #6169d8; }
    .ycu-tag-course { background-color: rgba(2, 168, 181, .14); color: #02a8b5; }
    .ycu-amount-stack { min-width: 125px; font-variant-numeric: tabular-nums; }
    .ycu-amount-stack .ycu-small { white-space: nowrap; }
    .ycu-amount-final { color: #313a46; font-weight: 800; }
    .ycu-method-stack { min-width: 128px; }
    .ycu-badge { display: inline-flex; align-items: center; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .02em; }
    .ycu-access-ref { color: #313a46; font-weight: 600; }
    .ycu-date-stack { min-width: 145px; }
    .ycu-empty { text-align: center; padding: 3rem 1rem; color: #98a6ad; }
    .ycu-empty i { font-size: 2rem; display: block; margin-bottom: .5rem; opacity: .6; }
    .ycu-empty h5 { color: #313a46; margin-bottom: .35rem; }
    @media (max-width: 767.98px) {
        .ycu-header-card .card-body { align-items: flex-start; }
        .ycu-header-card .btn { margin-top: .25rem; }
        .ycu-table-wrap { padding: .75rem .75rem 0; }
    }
</style>

<div class="row">
    <div class="col-xl-12">
        <div class="card ycu-header-card">
            <div class="card-body">
                <div class="ycu-header-copy">
                    <h4 class="page-title">
                        <i class="mdi mdi-ticket-percent title_icon"></i> <?php echo html_escape($page_title); ?>
                    </h4>
                    <p class="ycu-subtitle">Read-only report for coupon-backed checkout orders and zero-amount coupon access.</p>
                </div>
                <a href="<?php echo site_url('admin/youngo/payment-settings'); ?>" class="btn btn-outline-primary btn-rounded">
                    <i class="mdi mdi-cogs mr-1"></i>Payment Settings
                </a>
            </div>
        </div>
    </div>
</div>

<div class="alert ycu-alert" role="alert">
    <i class="mdi mdi-information-outline"></i>
    <span>This page is read-only. It reports checkout orders and linked coupon usage records without approving payments, issuing access, or changing coupon state.</span>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card ycu-card">
            <div class="card-body">
                <div class="ycu-filters" role="tablist" aria-label="Coupon checkout usage filters">
                    <?php
                    $filters = array(
                        'all' => 'All coupon checkout usage',
                        'zero_amount' => 'Zero amount coupon only',
                        'course' => 'Course purchases',
                        'subscription' => 'Subscription purchases',
                    );
                    ?>
                    <?php foreach ($filters as $key => $label): ?>
                        <?php $url = $key === 'all' ? site_url('admin/youngo/checkout-coupon-usage') : site_url('admin/youngo/checkout-coupon-usage?filter=' . rawurlencode($key)); ?>
                        <a href="<?php echo $url; ?>" class="ycu-filter <?php echo $usage_filter === $key ? 'is-active' : ''; ?>">
                            <?php echo html_escape($label); ?>
                            <span class="ycu-filter-count"><?php echo youngo_coupon_usage_count($usage_counts, $key); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($usage_rows)): ?>
                    <div class="ycu-empty">
                        <i class="mdi mdi-ticket-percent-outline"></i>
                        <h5>No coupon checkout usage found</h5>
                        <p class="mb-0"><?php echo $usage_filter !== 'all' ? 'No rows match this filter yet.' : 'Coupon-backed checkout orders will appear here after checkout completion.'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="ycu-table-wrap table-responsive" data-ycu-report-columns="selected_payment_method payment_gateway access_reference">
                        <table id="basic-datatable" class="table ycu-table">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('order'); ?></th>
                                    <th><?php echo get_phrase('user'); ?></th>
                                    <th><?php echo get_phrase('item'); ?></th>
                                    <th><?php echo get_phrase('coupon'); ?></th>
                                    <th><?php echo get_phrase('amounts'); ?></th>
                                    <th><?php echo get_phrase('method'); ?> / <?php echo get_phrase('gateway'); ?></th>
                                    <th><?php echo get_phrase('access'); ?></th>
                                    <th><?php echo get_phrase('status'); ?></th>
                                    <th><?php echo get_phrase('dates'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usage_rows as $row): ?>
                                    <tr>
                                        <td>
                                            <span class="ycu-order-id">#<?php echo (int) $row['checkout_order_id']; ?></span>
                                            <span class="ycu-order-ref" title="<?php echo html_escape($row['order_reference']); ?>"><?php echo html_escape($row['order_reference']); ?></span>
                                        </td>
                                        <td>
                                            <span class="ycu-user"><?php echo html_escape($row['user_name']); ?></span>
                                            <span class="ycu-user-email"><?php echo html_escape($row['user_email']); ?></span>
                                        </td>
                                        <td>
                                            <span class="ycu-item-title"><?php echo html_escape($row['item_name'] !== '' ? $row['item_name'] : '-'); ?></span>
                                            <?php echo youngo_coupon_usage_item_tag($row['item_type']); ?>
                                            <?php if (!empty($row['item_id'])): ?>
                                                <span class="ycu-small">ID #<?php echo (int) $row['item_id']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="ycu-coupon"><?php echo html_escape($row['coupon_code'] !== '' ? $row['coupon_code'] : '-'); ?></span>
                                            <?php if (!empty($row['coupon_usage_id'])): ?>
                                                <span class="ycu-small">Usage #<?php echo (int) $row['coupon_usage_id']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="ycu-amount-stack">
                                            <?php echo youngo_coupon_usage_secondary('Original', youngo_coupon_usage_amount($row['original_amount'], $row['currency'])); ?>
                                            <?php echo youngo_coupon_usage_secondary('Discount', youngo_coupon_usage_amount($row['discount_amount'], $row['currency'])); ?>
                                            <span class="ycu-small ycu-amount-final"><span>Final:</span> <?php echo youngo_coupon_usage_amount($row['final_amount'], $row['currency']); ?></span>
                                        </td>
                                        <td class="ycu-method-stack">
                                            <span class="ycu-cell-main"><?php echo html_escape($row['selected_payment_method'] !== '' ? $row['selected_payment_method'] : '-'); ?></span>
                                            <span class="ycu-small"><span>Gateway:</span> <?php echo html_escape($row['payment_gateway'] !== '' ? $row['payment_gateway'] : '-'); ?></span>
                                        </td>
                                        <td><?php echo youngo_coupon_usage_access_label($row); ?></td>
                                        <td><?php echo youngo_coupon_usage_badge($row['status']); ?></td>
                                        <td class="ycu-date-stack">
                                            <?php echo youngo_coupon_usage_secondary('Created', youngo_coupon_usage_date($row['created_at'])); ?>
                                            <?php echo youngo_coupon_usage_secondary('Completed', youngo_coupon_usage_date(!empty($row['completed_at']) ? $row['completed_at'] : $row['paid_at'])); ?>
                                            <?php echo youngo_coupon_usage_secondary('Coupon used', youngo_coupon_usage_date($row['coupon_used_at'])); ?>
                                            <?php if (!empty($row['paid_at']) && $row['paid_at'] !== $row['completed_at']): ?>
                                                <?php echo youngo_coupon_usage_secondary('Paid', youngo_coupon_usage_date($row['paid_at'])); ?>
                                            <?php endif; ?>
                                        </td>
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
