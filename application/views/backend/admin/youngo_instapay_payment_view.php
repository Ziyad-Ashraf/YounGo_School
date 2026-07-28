<?php
$detail = isset($detail) && is_array($detail) ? $detail : array();
$payment_summary = isset($detail['order_payment_summary']) && is_array($detail['order_payment_summary']) ? $detail['order_payment_summary'] : array();

if (!function_exists('youngo_instapay_detail_date')) {
    function youngo_instapay_detail_date($timestamp)
    {
        return !empty($timestamp) ? date('Y-m-d H:i', (int) $timestamp) : '-';
    }
}

if (!function_exists('youngo_instapay_detail_amount')) {
    function youngo_instapay_detail_amount($amount, $currency = 'EGP')
    {
        return $amount !== null && $amount !== '' ? html_escape(number_format((float) $amount, 2) . ' ' . $currency) : '-';
    }
}

if (!function_exists('youngo_instapay_detail_badge')) {
    function youngo_instapay_detail_badge($status)
    {
        $status = (string) $status;
        $classes = array(
            'pending_review' => 'badge-warning-lighten',
            'approved' => 'badge-success-lighten',
            'rejected' => 'badge-danger-lighten',
        );
        $class = isset($classes[$status]) ? $classes[$status] : 'badge-secondary-lighten';

        return '<span class="badge ' . $class . '">' . html_escape(str_replace('_', ' ', $status !== '' ? $status : 'unknown')) . '</span>';
    }
}

if (!function_exists('youngo_instapay_detail_text')) {
    function youngo_instapay_detail_text($value, $default = '-')
    {
        return $value !== null && $value !== '' ? html_escape((string) $value) : html_escape($default);
    }
}

$preview_url = !empty($detail['id']) ? site_url('admin/youngo/instapay-payments/' . (int) $detail['id'] . '/evidence') : '';
$download_url = !empty($detail['id']) ? site_url('admin/youngo/instapay-payments/' . (int) $detail['id'] . '/evidence/download') : '';
$approve_url = !empty($detail['id']) ? site_url('admin/youngo/instapay-payments/' . (int) $detail['id'] . '/approve') : '';
$reject_url = !empty($detail['id']) ? site_url('admin/youngo/instapay-payments/' . (int) $detail['id'] . '/reject') : '';
$csrf_name = isset($this->security) && method_exists($this->security, 'get_csrf_token_name') ? $this->security->get_csrf_token_name() : '';
$csrf_hash = isset($this->security) && method_exists($this->security, 'get_csrf_hash') ? $this->security->get_csrf_hash() : '';
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title">
                    <i class="mdi mdi-receipt title_icon"></i> <?php echo html_escape($page_title); ?>
                    <a href="<?php echo site_url('admin/youngo/instapay-payments'); ?>" class="btn btn-outline-primary btn-rounded alignToTitle">Back to inbox</a>
                </h4>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info" role="alert">
    Verify payment externally before approval. Screenshot evidence alone must not grant access.
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('submission_summary'); ?></h4>
                <table class="table table-striped table-centered mb-0">
                    <tbody>
                        <tr><th><?php echo get_phrase('submission_id'); ?></th><td>#<?php echo (int) $detail['id']; ?></td></tr>
                        <tr><th><?php echo get_phrase('status'); ?></th><td><?php echo youngo_instapay_detail_badge($detail['status']); ?></td></tr>
                        <tr><th><?php echo get_phrase('order'); ?></th><td><?php echo youngo_instapay_detail_text($detail['order_reference'], 'Order #' . (int) $detail['order_id']); ?></td></tr>
                        <tr><th><?php echo get_phrase('order_status'); ?></th><td><?php echo youngo_instapay_detail_text($detail['order_status']); ?></td></tr>
                        <tr><th><?php echo get_phrase('submitted_at'); ?></th><td><?php echo youngo_instapay_detail_date($detail['created_at']); ?></td></tr>
                        <tr><th><?php echo get_phrase('transaction_reference'); ?></th><td><?php echo youngo_instapay_detail_text($detail['transaction_reference']); ?></td></tr>
                        <tr><th><?php echo get_phrase('user_note'); ?></th><td><?php echo nl2br(youngo_instapay_detail_text($detail['user_note'])); ?></td></tr>
                        <tr><th><?php echo get_phrase('admin_note'); ?></th><td><?php echo nl2br(youngo_instapay_detail_text($detail['admin_note'])); ?></td></tr>
                        <tr><th><?php echo get_phrase('reviewed_by_user_id'); ?></th><td><?php echo !empty($detail['reviewed_by_user_id']) ? (int) $detail['reviewed_by_user_id'] : '-'; ?></td></tr>
                        <tr><th><?php echo get_phrase('reviewed_at'); ?></th><td><?php echo youngo_instapay_detail_date($detail['reviewed_at']); ?></td></tr>
                        <tr><th><?php echo get_phrase('approved_at'); ?></th><td><?php echo youngo_instapay_detail_date($detail['approved_at']); ?></td></tr>
                        <tr><th><?php echo get_phrase('rejected_at'); ?></th><td><?php echo youngo_instapay_detail_date($detail['rejected_at']); ?></td></tr>
                        <tr><th><?php echo get_phrase('access_issued'); ?></th><td><?php echo !empty($detail['access_issued']) ? get_phrase('yes') : get_phrase('no'); ?></td></tr>
                        <tr><th><?php echo get_phrase('access_reference'); ?></th><td><?php echo youngo_instapay_detail_text($detail['access_reference_type']); ?> <?php echo !empty($detail['access_reference_id']) ? '#' . (int) $detail['access_reference_id'] : ''; ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('checkout_snapshot'); ?></h4>
                <table class="table table-sm table-striped mb-0">
                    <tbody>
                        <tr><th><?php echo get_phrase('user'); ?></th><td><?php echo html_escape($detail['user_name']); ?> <span class="text-muted"><?php echo html_escape($detail['user_email']); ?></span></td></tr>
                        <tr><th><?php echo get_phrase('item'); ?></th><td><?php echo youngo_instapay_detail_text($detail['item_title_snapshot']); ?></td></tr>
                        <tr><th><?php echo get_phrase('item_type'); ?></th><td><?php echo youngo_instapay_detail_text($detail['item_type']); ?></td></tr>
                        <tr><th><?php echo get_phrase('course_id'); ?></th><td><?php echo !empty($detail['course_id']) ? (int) $detail['course_id'] : '-'; ?></td></tr>
                        <tr><th><?php echo get_phrase('subscription_plan_id'); ?></th><td><?php echo !empty($detail['subscription_plan_id']) ? (int) $detail['subscription_plan_id'] : '-'; ?></td></tr>
                        <tr><th><?php echo get_phrase('original_amount'); ?></th><td><?php echo youngo_instapay_detail_amount($detail['original_amount'], $detail['currency']); ?></td></tr>
                        <tr><th><?php echo get_phrase('coupon_code'); ?></th><td><?php echo youngo_instapay_detail_text($detail['coupon_code']); ?></td></tr>
                        <tr><th><?php echo get_phrase('coupon_discount_type/value'); ?></th><td><?php echo youngo_instapay_detail_text($detail['coupon_discount_type']); ?> <?php echo youngo_instapay_detail_text($detail['coupon_discount_value']); ?></td></tr>
                        <tr><th><?php echo get_phrase('discount_amount'); ?></th><td><?php echo youngo_instapay_detail_amount($detail['discount_amount'], $detail['currency']); ?></td></tr>
                        <tr><th><?php echo get_phrase('final_expected_amount'); ?></th><td><strong><?php echo youngo_instapay_detail_amount($detail['expected_amount'], $detail['currency']); ?></strong></td></tr>
                        <tr><th><?php echo get_phrase('submitted_amount'); ?></th><td><?php echo youngo_instapay_detail_amount($detail['submitted_amount'], $detail['currency']); ?></td></tr>
                    </tbody>
                </table>
                <?php if ($detail['submitted_amount'] !== null && $detail['submitted_amount'] !== '' && (float) $detail['submitted_amount'] !== (float) $detail['expected_amount']): ?>
                    <div class="alert alert-warning mt-3 mb-0">Submitted amount differs from the expected final amount. Verify externally before the future approval phase.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('instapay_target_snapshot'); ?></h4>
                <table class="table table-sm table-striped mb-0">
                    <tbody>
                        <tr><th><?php echo get_phrase('target_label'); ?></th><td><?php echo youngo_instapay_detail_text($detail['instapay_target_label']); ?></td></tr>
                        <tr><th><?php echo get_phrase('address'); ?></th><td><?php echo youngo_instapay_detail_text($detail['instapay_target_address']); ?></td></tr>
                        <tr><th><?php echo get_phrase('link'); ?></th><td><?php echo !empty($detail['instapay_target_link']) ? '<a href="' . html_escape($detail['instapay_target_link']) . '" target="_blank" rel="noopener noreferrer">' . html_escape($detail['instapay_target_link']) . '</a>' : '-'; ?></td></tr>
                        <tr><th><?php echo get_phrase('english_instructions'); ?></th><td><?php echo nl2br(youngo_instapay_detail_text($detail['instapay_instructions_en'])); ?></td></tr>
                        <tr><th><?php echo get_phrase('arabic_instructions'); ?></th><td dir="rtl"><?php echo nl2br(youngo_instapay_detail_text($detail['instapay_instructions_ar'])); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('evidence'); ?></h4>
                <?php if (!empty($detail['screenshot_path'])): ?>
                    <p><strong>Original filename:</strong> <?php echo youngo_instapay_detail_text($detail['screenshot_original_name']); ?></p>
                    <p><strong>MIME:</strong> <?php echo youngo_instapay_detail_text($detail['screenshot_mime']); ?></p>
                    <p><strong>Size:</strong> <?php echo !empty($detail['screenshot_size']) ? number_format((int) $detail['screenshot_size']) . ' bytes' : '-'; ?></p>
                    <div class="mb-3">
                        <a href="<?php echo $preview_url; ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">Preview evidence</a>
                        <a href="<?php echo $download_url; ?>" class="btn btn-sm btn-outline-secondary">Download</a>
                    </div>
                    <a href="<?php echo $preview_url; ?>" target="_blank" rel="noopener noreferrer">
                        <img src="<?php echo $preview_url; ?>" alt="<?php echo get_phrase('instapay_evidence_preview'); ?>" class="img-fluid rounded border">
                    </a>
                <?php else: ?>
                    <p class="text-muted mb-0"><?php echo get_phrase('no_screenshot_evidence_is_attached.'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('review_actions'); ?></h4>
                <?php if ($detail['status'] === 'pending_review'): ?>
                    <form method="post" action="<?php echo $approve_url; ?>" class="mb-3" onsubmit="return confirm('Approve this manual Instapay payment? This confirms external receipt and will issue access.');">
                        <?php if ($csrf_name !== ''): ?>
                            <input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label class="d-block">
                                <input type="checkbox" name="external_payment_confirmed" value="1" required>
                                I confirm that this payment was received externally.
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="instapay-approve-note"><?php echo get_phrase('admin_note'); ?></label>
                            <textarea id="instapay-approve-note" name="admin_note" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-block"><?php echo get_phrase('approve_payment_and_issue_access'); ?></button>
                    </form>

                    <form method="post" action="<?php echo $reject_url; ?>" onsubmit="return confirm('Reject this manual Instapay submission? No access will be issued.');">
                        <?php if ($csrf_name !== ''): ?>
                            <input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="instapay-reject-note"><?php echo get_phrase('admin_note'); ?></label>
                            <textarea id="instapay-reject-note" name="admin_note" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger btn-block"><?php echo get_phrase('reject_payment'); ?></button>
                    </form>
                <?php else: ?>
                    <p class="text-muted mb-0">This submission has already been reviewed. Approved submissions cannot be rejected, and rejected submissions require a new learner submission before approval.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('payment/access_safety'); ?></h4>
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th><?php echo get_phrase('gateway'); ?></th><td><?php echo youngo_instapay_detail_text($payment_summary['payment_gateway']); ?></td></tr>
                        <tr><th><?php echo get_phrase('paid_at'); ?></th><td><?php echo youngo_instapay_detail_date($payment_summary['paid_at']); ?></td></tr>
                        <tr><th><?php echo get_phrase('completed_at'); ?></th><td><?php echo youngo_instapay_detail_date($payment_summary['completed_at']); ?></td></tr>
                        <tr><th><?php echo get_phrase('access_issued'); ?></th><td><?php echo !empty($payment_summary['entitlement_issued']) ? get_phrase('yes') : get_phrase('no'); ?></td></tr>
                        <tr><th><?php echo get_phrase('access_status'); ?></th><td><?php echo youngo_instapay_detail_text($payment_summary['entitlement_issuance_status']); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
