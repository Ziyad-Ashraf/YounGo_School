<?php
$submissions = isset($submissions) && is_array($submissions) ? $submissions : array();
$status_counts = isset($status_counts) && is_array($status_counts) ? $status_counts : array();
$status_filter = isset($status_filter) ? (string) $status_filter : 'all';

if (!function_exists('youngo_instapay_admin_date')) {
    function youngo_instapay_admin_date($timestamp)
    {
        return !empty($timestamp) ? date('Y-m-d H:i', (int) $timestamp) : '-';
    }
}

if (!function_exists('youngo_instapay_admin_amount')) {
    function youngo_instapay_admin_amount($amount, $currency = 'EGP')
    {
        return $amount !== null && $amount !== '' ? html_escape(number_format((float) $amount, 2) . ' ' . $currency) : '-';
    }
}

if (!function_exists('youngo_instapay_status_meta')) {
    function youngo_instapay_status_meta($status)
    {
        $status = (string) $status;
        $meta = array(
            'pending_review' => array('class' => 'badge-warning-lighten', 'dot' => '#ffbc00'),
            'approved' => array('class' => 'badge-success-lighten', 'dot' => '#0acf97'),
            'rejected' => array('class' => 'badge-danger-lighten', 'dot' => '#fa5c7c'),
        );

        return isset($meta[$status]) ? $meta[$status] : array('class' => 'badge-secondary-lighten', 'dot' => '#98a6ad');
    }
}

if (!function_exists('youngo_instapay_admin_badge')) {
    function youngo_instapay_admin_badge($status)
    {
        $status = (string) $status;
        $meta = youngo_instapay_status_meta($status);
        $label = str_replace('_', ' ', $status !== '' ? $status : 'unknown');

        return '<span class="badge yip-badge ' . $meta['class'] . '"><i class="yip-dot" style="background-color:' . $meta['dot'] . '"></i>' . html_escape($label) . '</span>';
    }
}

if (!function_exists('youngo_instapay_admin_count')) {
    function youngo_instapay_admin_count($counts, $key)
    {
        return isset($counts[$key]) ? (int) $counts[$key] : 0;
    }
}

if (!function_exists('youngo_instapay_item_tag')) {
    function youngo_instapay_item_tag($item_type)
    {
        $item_type = (string) $item_type;
        if ($item_type === '') {
            return '';
        }

        $class = $item_type === 'subscription' ? 'yip-tag-subscription' : 'yip-tag-course';

        return '<span class="yip-tag ' . $class . '">' . html_escape($item_type) . '</span>';
    }
}
?>

<style>
    .yip-header-card .card-body { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .75rem; }
    .yip-header-card .page-title { margin-bottom: 0; }
    .yip-alert { display: flex; align-items: center; gap: .5rem; border: 1px solid rgba(57, 175, 209, .25); background-color: rgba(57, 175, 209, .08); color: #2b7d92; }

    .yip-filters { display: inline-flex; flex-wrap: wrap; gap: .375rem; padding: .3rem; background-color: #f5f6fb; border-radius: .6rem; margin-bottom: 1.25rem; }
    .yip-filter { display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .85rem; border-radius: .45rem; font-size: .8125rem; font-weight: 600; color: #6c757d; text-decoration: none; transition: background-color .15s ease, color .15s ease; }
    .yip-filter:hover { color: #495057; text-decoration: none; }
    .yip-filter-count { min-width: 1.35rem; padding: .05rem .35rem; border-radius: 1rem; font-size: .6875rem; font-weight: 700; text-align: center; background-color: rgba(108, 117, 125, .14); color: #6c757d; }
    .yip-filter.is-active { background-color: #727cf5; color: #fff; box-shadow: 0 2px 6px rgba(114, 124, 245, .35); }
    .yip-filter.is-active .yip-filter-count { background-color: rgba(255, 255, 255, .22); color: #fff; }

    .yip-table-scroll { border: 1px solid #eef0f7; border-radius: .5rem; padding: 1rem 1rem 0; }
    .yip-table-scroll .dataTables_wrapper > .row:first-child { margin: 0 0 1rem; }
    .yip-table-scroll .dataTables_wrapper > .row:last-child { margin: 1rem 0 0; padding-bottom: 1rem; }
    .yip-table-scroll .dataTables_wrapper > .row:nth-of-type(2) > div { position: relative; overflow-x: auto; }
    .yip-table-scroll .dataTables_wrapper > .row:nth-of-type(2) > div::after { content: ""; position: absolute; top: 0; right: 0; bottom: 0; width: 28px; pointer-events: none; opacity: 0; transition: opacity .2s ease; background: linear-gradient(to right, rgba(19, 23, 34, 0), rgba(19, 23, 34, .06)); }
    .yip-table-scroll .dataTables_wrapper > .row:nth-of-type(2) > div.is-scrollable:not(.at-end)::after { opacity: 1; }

    .yip-table { margin-bottom: 0; border-color: #eef0f7; }
    .yip-table thead th { position: sticky; top: 0; z-index: 1; background-color: #f5f6fb; color: #4b5165; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid #eef0f7; border-top: none; white-space: nowrap; padding: .8rem 1rem; }
    .yip-table tbody td { padding: .8rem 1rem; vertical-align: middle; border-top: 1px solid #f3f4f9; font-size: .84rem; }
    .yip-table tbody tr { transition: background-color .12s ease; }
    .yip-table tbody tr:nth-of-type(odd) { background-color: #fbfbfe; }
    .yip-table tbody tr:hover { background-color: #f2f3fc; }

    .yip-order-id { display: block; font-weight: 700; color: #313a46; }
    .yip-order-ref { display: inline-block; max-width: 190px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: var(--font-family-monospace); font-size: .74rem; color: #8891a5; vertical-align: bottom; }

    .yip-user { font-weight: 600; color: #313a46; }
    .yip-user-email { font-size: .76rem; color: #98a6ad; }

    .yip-item-title { font-weight: 600; color: #313a46; margin-bottom: .2rem; }
    .yip-tag { display: inline-block; padding: .1rem .5rem; border-radius: .3rem; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
    .yip-tag-subscription { background-color: rgba(114, 124, 245, .14); color: #6169d8; }
    .yip-tag-course { background-color: rgba(2, 168, 181, .14); color: #02a8b5; }

    .yip-amount { font-variant-numeric: tabular-nums; color: #495057; }
    .yip-amount-final { font-weight: 700; color: #313a46; }

    .yip-coupon { display: block; font-weight: 600; color: #313a46; }
    .yip-discount-amount { font-size: .76rem; color: #98a6ad; }

    .yip-date { color: #6c757d; white-space: nowrap; }

    .yip-badge { display: inline-flex; align-items: center; padding: .32rem .6rem; border-radius: 1rem; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .02em; }
    .yip-dot { width: 6px; height: 6px; border-radius: 50%; margin-right: .4rem; flex: none; }

    .yip-details-btn { font-weight: 600; white-space: nowrap; }

    .yip-empty { text-align: center; padding: 3rem 1rem; color: #98a6ad; }
    .yip-empty i { font-size: 2rem; display: block; margin-bottom: .5rem; opacity: .6; }
    .yip-empty p { margin: 0; font-size: .875rem; }

    .yip-card .dataTables_wrapper .row:first-child { margin-bottom: 1rem; align-items: center; }
    .yip-card .dataTables_length select { border-radius: .4rem; }
    .yip-card .dataTables_filter input { border-radius: .4rem; }
    .yip-card .dataTables_wrapper .row:last-child { margin-top: 1rem; align-items: center; }
    .yip-card .dataTables_info { color: #98a6ad; font-size: .8rem; }
</style>

<div class="row">
    <div class="col-xl-12">
        <div class="card yip-header-card">
            <div class="card-body">
                <h4 class="page-title">
                    <i class="mdi mdi-receipt title_icon"></i> <?php echo html_escape($page_title); ?>
                </h4>
                <a href="<?php echo site_url('admin/youngo/payment-settings'); ?>" class="btn btn-outline-primary btn-rounded">
                    <i class="mdi mdi-cogs mr-1"></i>Payment Settings
                </a>
            </div>
        </div>
    </div>
</div>

<div class="alert yip-alert" role="alert">
    <i class="mdi mdi-information-outline"></i>
    <span><?php echo get_phrase('admins_must_verify_instapay_payments_externally._approval_issues_access_only_after_the_approve_action_completes.'); ?></span>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card yip-card">
            <div class="card-body">
                <div class="yip-filters" role="tablist" aria-label="Instapay review filters">
                    <?php
                    $filters = array(
                        'pending_review' => 'Pending Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'all' => 'All',
                    );
                    ?>
                    <?php foreach ($filters as $key => $label): ?>
                        <?php $url = $key === 'all' ? site_url('admin/youngo/instapay-payments') : site_url('admin/youngo/instapay-payments?status=' . rawurlencode($key)); ?>
                        <a href="<?php echo $url; ?>" class="yip-filter <?php echo $status_filter === $key ? 'is-active' : ''; ?>">
                            <?php echo html_escape($label); ?>
                            <span class="yip-filter-count"><?php echo youngo_instapay_admin_count($status_counts, $key); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($submissions)): ?>
                    <div class="yip-empty">
                        <i class="mdi mdi-inbox"></i>
                        <p><?php echo get_phrase('no_manual_instapay_submissions_were_found'); ?><?php echo $status_filter !== 'all' ? ' for this filter' : ''; ?>.</p>
                    </div>
                <?php else: ?>
                <div class="yip-table-scroll" data-yip-scroll>
                    <table id="basic-datatable" class="table yip-table">
                        <thead>
                            <tr>
                                <th><?php echo get_phrase('order'); ?></th>
                                <th><?php echo get_phrase('user'); ?></th>
                                <th><?php echo get_phrase('item'); ?></th>
                                <th class="text-right"><?php echo get_phrase('original'); ?></th>
                                <th><?php echo get_phrase('discount'); ?></th>
                                <th class="text-right"><?php echo get_phrase('final_expected'); ?></th>
                                <th class="text-right"><?php echo get_phrase('amount_sent'); ?></th>
                                <th><?php echo get_phrase('submitted_at'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                                <th><?php echo get_phrase('access'); ?></th>
                                <th class="text-right"><?php echo get_phrase('action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <?php $coupon_display = $submission['coupon_code'] !== '' ? html_escape($submission['coupon_code']) : ''; ?>
                                <tr>
                                    <td>
                                        <span class="yip-order-id">#<?php echo (int) $submission['id']; ?></span>
                                        <span class="yip-order-ref" title="<?php echo html_escape($submission['order_reference']); ?>"><?php echo html_escape($submission['order_reference'] !== '' ? $submission['order_reference'] : ('Order #' . (int) $submission['order_id'])); ?></span>
                                    </td>
                                    <td>
                                        <div class="yip-user"><?php echo html_escape($submission['user_name']); ?></div>
                                        <div class="yip-user-email"><?php echo html_escape($submission['user_email']); ?></div>
                                    </td>
                                    <td>
                                        <div class="yip-item-title"><?php echo html_escape($submission['item_title_snapshot'] !== '' ? $submission['item_title_snapshot'] : '-'); ?></div>
                                        <?php echo youngo_instapay_item_tag($submission['item_type']); ?>
                                    </td>
                                    <td class="text-right yip-amount"><?php echo youngo_instapay_admin_amount($submission['original_amount'], $submission['currency']); ?></td>
                                    <td>
                                        <?php if ($coupon_display !== ''): ?>
                                            <span class="yip-coupon"><?php echo $coupon_display; ?></span>
                                            <span class="yip-discount-amount"><?php echo youngo_instapay_admin_amount($submission['discount_amount'], $submission['currency']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right yip-amount yip-amount-final"><?php echo youngo_instapay_admin_amount($submission['expected_amount'], $submission['currency']); ?></td>
                                    <td class="text-right yip-amount"><?php echo youngo_instapay_admin_amount($submission['submitted_amount'], $submission['currency']); ?></td>
                                    <td class="yip-date"><?php echo youngo_instapay_admin_date($submission['created_at']); ?></td>
                                    <td><?php echo youngo_instapay_admin_badge($submission['status']); ?></td>
                                    <td><?php echo !empty($submission['access_issued']) ? '<span class="badge yip-badge badge-success-lighten"><i class="mdi mdi-check-circle-outline mr-1"></i>Issued</span>' : '<span class="badge yip-badge badge-secondary-lighten">' . html_escape(get_phrase('not_issued')) . '</span>'; ?></td>
                                    <td class="text-right">
                                        <a href="<?php echo site_url('admin/youngo/instapay-payments/' . (int) $submission['id']); ?>" class="btn btn-sm btn-outline-primary yip-details-btn">Details</a>
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

<script>
    (function () {
        function wire(container) {
            var el = container.querySelector('.dataTables_wrapper > .row:nth-of-type(2) > div');
            if (!el || el.dataset.yipWired) {
                return !!el;
            }
            el.dataset.yipWired = '1';

            function update() {
                var scrollable = el.scrollWidth > el.clientWidth + 2;
                el.classList.toggle('is-scrollable', scrollable);
                el.classList.toggle('at-end', el.scrollLeft + el.clientWidth >= el.scrollWidth - 2);
            }
            el.addEventListener('scroll', update, { passive: true });
            window.addEventListener('resize', update);
            update();
            setTimeout(update, 300);
            return true;
        }

        var containers = document.querySelectorAll('[data-yip-scroll]');
        var attempts = 0;
        var timer = setInterval(function () {
            attempts++;
            var allWired = true;
            Array.prototype.forEach.call(containers, function (container) {
                if (!wire(container)) {
                    allWired = false;
                }
            });
            if (allWired || attempts > 20) {
                clearInterval(timer);
            }
        }, 100);
    })();
</script>
