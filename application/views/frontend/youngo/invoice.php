<?php
$payment_info = isset($payment_info) && is_array($payment_info) ? $payment_info : array();
$course_details = !empty($payment_info['course_id']) ? $this->crud_model->get_course_by_id($payment_info['course_id'])->row_array() : array();
$buyer_details = !empty($payment_info['user_id']) ? $this->user_model->get_all_user($payment_info['user_id'])->row_array() : array();
$creator_id = !empty($course_details['creator']) ? (int) $course_details['creator'] : (!empty($course_details['user_id']) ? (int) trim(strtok((string) $course_details['user_id'], ',')) : 0);
$instructor_details = $creator_id > 0 ? $this->user_model->get_all_user($creator_id)->row_array() : array();
$buyer_name = trim((isset($buyer_details['first_name']) ? $buyer_details['first_name'] : '') . ' ' . (isset($buyer_details['last_name']) ? $buyer_details['last_name'] : ''));
$instructor_name = trim((isset($instructor_details['first_name']) ? $instructor_details['first_name'] : '') . ' ' . (isset($instructor_details['last_name']) ? $instructor_details['last_name'] : ''));
$subtotal = (isset($payment_info['admin_revenue']) ? (float) $payment_info['admin_revenue'] : 0) + (isset($payment_info['instructor_revenue']) ? (float) $payment_info['instructor_revenue'] : 0);

if (!function_exists('youngo_account_e')) {
    function youngo_account_e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>

<section class="youngo-account-hero print-d-none">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo get_phrase('Breadcrumb'); ?>">
            <a href="<?php echo site_url('home'); ?>"><?php echo get_phrase('Home'); ?></a>
            <span>/</span>
            <a href="<?php echo site_url('home/purchase_history'); ?>"><?php echo get_phrase('Purchase history'); ?></a>
            <span>/</span>
            <span><?php echo get_phrase('Invoice'); ?></span>
        </nav>

        <p class="youngo-eyebrow"><?php echo get_phrase('Billing'); ?></p>
        <h1><?php echo get_phrase('Invoice'); ?></h1>
        <p><?php echo get_phrase('Invoice details are read from the existing Academy LMS payment record.'); ?></p>
    </div>
</section>

<section class="youngo-account-page">
    <div class="youngo-container">
        <div class="youngo-account-layout youngo-account-layout--invoice">
            <div class="print-d-none">
                <?php include 'profile_menus.php'; ?>
            </div>

            <div class="youngo-invoice-card print-content">
                <?php if (empty($payment_info) || empty($course_details)): ?>
                    <div class="youngo-commerce-empty">
                        <div class="youngo-commerce-empty__icon"><i class="fa-solid fa-receipt"></i></div>
                        <p class="youngo-eyebrow"><?php echo get_phrase('Invoice unavailable'); ?></p>
                        <h2><?php echo get_phrase('No invoice record was found'); ?></h2>
                        <a class="youngo-button" href="<?php echo site_url('home/purchase_history'); ?>"><?php echo get_phrase('Back to purchase history'); ?></a>
                    </div>
                <?php else: ?>
                    <div class="youngo-invoice-card__header">
                        <div>
                            <p class="youngo-eyebrow"><?php echo get_phrase('Invoice ID'); ?></p>
                            <h2>#<?php echo youngo_account_e($payment_info['id']); ?></h2>
                        </div>
                        <strong><?php echo currency($payment_info['amount']); ?></strong>
                    </div>

                    <div class="youngo-invoice-summary">
                        <div>
                            <span><?php echo get_phrase('Billed To'); ?></span>
                            <strong><?php echo youngo_account_e($buyer_name); ?></strong>
                            <?php if (!empty($buyer_details['email'])): ?><small><?php echo youngo_account_e($buyer_details['email']); ?></small><?php endif; ?>
                            <?php if (!empty($buyer_details['address'])): ?><small><?php echo youngo_account_e($buyer_details['address']); ?></small><?php endif; ?>
                        </div>
                        <div>
                            <span><?php echo get_phrase('Date Of Issue'); ?></span>
                            <strong><?php echo date('d-M-Y', $payment_info['date_added']); ?></strong>
                        </div>
                        <div>
                            <span><?php echo get_phrase('Paid By'); ?></span>
                            <strong><?php echo youngo_account_e(ucfirst($payment_info['payment_type'])); ?></strong>
                        </div>
                    </div>

                    <div class="youngo-invoice-table-wrap">
                        <table class="youngo-invoice-table">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('Course'); ?></th>
                                    <th><?php echo get_phrase('Instructor'); ?></th>
                                    <th><?php echo get_phrase('QTY'); ?></th>
                                    <th><?php echo get_phrase('Price'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo youngo_account_e($course_details['title']); ?></td>
                                    <td><?php echo youngo_account_e($instructor_name); ?></td>
                                    <td>1</td>
                                    <td><?php echo currency($subtotal); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="youngo-invoice-totals">
                        <div>
                            <span><?php echo get_phrase('Subtotal'); ?></span>
                            <strong><?php echo currency($subtotal); ?></strong>
                        </div>
                        <div>
                            <span><?php echo get_phrase('Tax'); ?></span>
                            <strong><?php echo currency(isset($payment_info['tax']) ? $payment_info['tax'] : 0); ?></strong>
                        </div>
                        <div>
                            <span><?php echo get_phrase('Grand Total'); ?></span>
                            <strong><?php echo currency($payment_info['amount']); ?></strong>
                        </div>
                    </div>

                    <div class="youngo-invoice-actions print-d-none">
                        <a class="youngo-button youngo-button--secondary" href="<?php echo site_url('home/purchase_history'); ?>">
                            <i class="fa-solid fa-arrow-left"></i>
                            <?php echo get_phrase('Back'); ?>
                        </a>
                        <button class="youngo-button" type="button" onclick="window.print();">
                            <i class="fa-solid fa-print"></i>
                            <?php echo get_phrase('Print'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
