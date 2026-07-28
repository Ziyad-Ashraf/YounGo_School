<?php
$context = isset($youngo_checkout_order_context) && is_array($youngo_checkout_order_context)
    ? $youngo_checkout_order_context
    : array();
$notice = isset($context['notice']) && is_array($context['notice']) ? $context['notice'] : array();
$order = isset($context['order']) && is_array($context['order']) ? $context['order'] : array();
$review_snapshot = isset($context['review_snapshot']) && is_array($context['review_snapshot']) ? $context['review_snapshot'] : array();
$course = isset($context['course']) && is_array($context['course']) ? $context['course'] : array();
$mode = isset($context['mode']) ? (string) $context['mode'] : 'sandbox';
$currency = isset($context['currency']) ? (string) $context['currency'] : 'EGP';
$paymob = isset($context['paymob']) && is_array($context['paymob']) ? $context['paymob'] : array();
$instapay = isset($context['instapay']) && is_array($context['instapay']) ? $context['instapay'] : array();
$instapay_target = isset($instapay['target']) && is_array($instapay['target']) ? $instapay['target'] : array();
$instapay_submission = isset($instapay['latest_submission']) && is_array($instapay['latest_submission']) ? $instapay['latest_submission'] : array();
$instapay_status = isset($instapay_submission['status']) ? (string) $instapay_submission['status'] : '';
$instapay_enabled = !empty($instapay['enabled']);
$instapay_can_submit = !empty($instapay['can_submit']);
$instapay_max_upload_mb = isset($instapay_target['max_upload_mb']) ? (string) $instapay_target['max_upload_mb'] : '5.00';
$zero_amount_coupon = isset($context['zero_amount_coupon']) && is_array($context['zero_amount_coupon']) ? $context['zero_amount_coupon'] : array();
$zero_coupon_completion_available = !empty($zero_amount_coupon['completion_available']);
$zero_coupon_already_completed = !empty($zero_amount_coupon['already_completed']);
$zero_coupon_subscription_deferred = !empty($zero_amount_coupon['subscription_deferred']);
$zero_coupon_is_zero_total_order = !empty($zero_amount_coupon['is_zero_total_coupon_order']);
$zero_coupon_message = isset($zero_amount_coupon['message']) ? (string) $zero_amount_coupon['message'] : '';
$notice_code = isset($notice['code']) ? (string) $notice['code'] : 'checkout_local_status';
$notice_message = isset($notice['message']) ? (string) $notice['message'] : 'Checkout is available for local testing only.';
$order_reference = isset($order['order_reference']) ? (string) $order['order_reference'] : '';
$order_status = isset($order['status']) ? (string) $order['status'] : 'draft';
$original_amount = isset($review_snapshot['original_amount']) ? (string) $review_snapshot['original_amount'] : (isset($course['amount']) ? (string) $course['amount'] : '0.00');
$discount_amount = isset($review_snapshot['discount_amount']) ? (string) $review_snapshot['discount_amount'] : '0.00';
$final_amount = isset($review_snapshot['final_amount']) ? (string) $review_snapshot['final_amount'] : (isset($order['total_amount']) ? (string) $order['total_amount'] : '0.00');
$instapay_expected_amount = isset($instapay['expected_amount']) ? (string) $instapay['expected_amount'] : $final_amount;
$current_coupon_code = isset($review_snapshot['coupon_code']) ? trim((string) $review_snapshot['coupon_code']) : '';
$current_coupon_type = isset($review_snapshot['coupon_discount_type']) ? trim((string) $review_snapshot['coupon_discount_type']) : '';
$current_coupon_value = isset($review_snapshot['coupon_discount_value']) ? trim((string) $review_snapshot['coupon_discount_value']) : '';
$coupon_value_label = $current_coupon_value;
if ($current_coupon_type === 'percentage' && $coupon_value_label !== '' && is_numeric($coupon_value_label)) {
    $coupon_value_label .= '%';
}
$can_edit_coupon = !empty($order) && $order_reference !== '' && $order_status === 'draft' && !in_array($instapay_status, array('pending_review', 'approved'), true);
$csrf_name = isset($this->security) && method_exists($this->security, 'get_csrf_token_name') ? $this->security->get_csrf_token_name() : '';
$csrf_hash = isset($this->security) && method_exists($this->security, 'get_csrf_hash') ? $this->security->get_csrf_hash() : '';
$youngo_checkout_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_checkout_is_arabic = $youngo_checkout_language === 'arabic';

if ($youngo_checkout_is_arabic) {
    ob_start(static function ($html) {
        $translations = array(
            'Home' => 'الرئيسية', 'Courses' => 'الدورات', 'Checkout' => 'إتمام الطلب',
            'Local sandbox only' => 'تجربة محلية فقط', 'YounGo checkout' => 'إتمام طلب YounGo',
            'This checkout flow is limited to local testing and does not open a payment gateway.' => 'عملية الدفع هذه مخصصة للاختبار المحلي فقط ولا تفتح بوابة دفع حقيقية.',
            'Order status' => 'حالة الطلب', 'Back to courses' => 'العودة إلى الدورات',
            'Reason' => 'السبب', 'Order' => 'الطلب', 'Original amount' => 'المبلغ الأصلي',
            'Discount' => 'الخصم', 'Final amount' => 'المبلغ النهائي', 'Status' => 'الحالة',
            'Gateway' => 'بوابة الدفع', 'Access issued' => 'تم تفعيل الوصول', 'Yes' => 'نعم', 'No' => 'لا',
            'Coupon' => 'كوبون الخصم', 'No coupon applied' => 'لم يتم تطبيق كوبون',
            'Coupon code' => 'رمز الكوبون', 'Enter coupon code' => 'أدخل رمز الكوبون',
            'Apply' => 'تطبيق', 'Clear coupon' => 'إزالة الكوبون',
            'Coupon changes are available only while the order is still draft.' => 'يمكن تعديل الكوبون ما دام الطلب في حالة مسودة.',
            'Complete checkout / Activate access' => 'إكمال الطلب وتفعيل الوصول',
            'No payment is required because your coupon covers the full amount.' => 'لا يلزم الدفع لأن الكوبون يغطي المبلغ كاملًا.',
            'Checkout complete. Your access is active.' => 'اكتمل الطلب وتم تفعيل وصولك.',
            'Plan' => 'الخطة', 'Course' => 'الدورة', 'Course mode' => 'نوع الدورة',
            'Checkout currency' => 'عملة الدفع', 'Mode' => 'الوضع', 'sandbox' => 'تجريبي',
            'Instapay' => 'InstaPay', 'Final amount to pay' => 'المبلغ المطلوب دفعه',
            'Payment account' => 'حساب الدفع', 'Instapay address' => 'عنوان InstaPay',
            'Open Instapay link' => 'فتح رابط InstaPay',
            'Accepted files' => 'الملفات المقبولة', 'Maximum size' => 'الحد الأقصى للحجم',
            'Transaction screenshot' => 'صورة إيصال التحويل', 'Transaction reference' => 'رقم مرجع التحويل',
            'Note' => 'ملاحظات', 'optional' => 'اختياري', 'Submit payment for review' => 'إرسال الدفع للمراجعة',
            'Pending review' => 'قيد المراجعة', 'Approved' => 'تمت الموافقة', 'Rejected' => 'مرفوض',
            'You may submit a new screenshot if the order is still eligible.' => 'يمكنك إرسال صورة جديدة إذا كان الطلب ما زال مؤهلًا.',
            'Payment methods' => 'طرق الدفع', 'Available' => 'متاح', 'Not required' => 'غير مطلوب',
            'Cards' => 'البطاقات', 'Digital Wallets' => 'المحافظ الرقمية', 'Not available yet' => 'غير متاح حاليًا',
            'Coming soon' => 'قريبًا', 'not_started' => 'لم يبدأ', 'draft' => 'مسودة',
            'Paymob redirect is disabled or not ready for this local test order. There is no gateway selection.' => 'التحويل إلى Paymob متوقف أو غير جاهز لهذا الطلب التجريبي المحلي. لا يوجد اختيار لبوابة الدفع.',
            'Return URL handling is UX-only. Access is issued only after verified server-side payment processing in a later phase.' => 'رابط العودة مخصص لتجربة الواجهة فقط. لا يتم تفعيل الوصول إلا بعد التحقق من الدفع من الخادم في مرحلة لاحقة.',
            'Checkout order loaded for local testing.' => 'تم تحميل الطلب للاختبار المحلي.'
        );
        return strtr($html, $translations);
    });
}

if (!function_exists('youngo_checkout_e')) {
    function youngo_checkout_e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>

<section class="youngo-account-hero youngo-checkout-hero" <?php echo $youngo_checkout_is_arabic ? 'dir="rtl"' : ''; ?>>
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo $youngo_checkout_is_arabic ? 'مسار التنقل' : 'Breadcrumb'; ?>">
            <a href="<?php echo site_url('home'); ?>"><?php echo $youngo_checkout_is_arabic ? 'الرئيسية' : 'Home'; ?></a>
            <span>/</span>
            <a href="<?php echo site_url('home/courses'); ?>"><?php echo $youngo_checkout_is_arabic ? 'الدورات' : 'Courses'; ?></a>
            <span>/</span>
            <span><?php echo $youngo_checkout_is_arabic ? 'إتمام الطلب' : 'Checkout'; ?></span>
        </nav>

        <div class="youngo-checkout-hero__content">
            <div>
                <p class="youngo-eyebrow"><?php echo $youngo_checkout_is_arabic ? 'إتمام الطلب' : 'Checkout'; ?></p>
                <h1><?php echo $youngo_checkout_is_arabic ? 'أكمل طلبك' : 'Complete your order'; ?></h1>
                <p><?php echo $youngo_checkout_is_arabic ? 'راجع المبلغ وأرسل إثبات الدفع.' : 'Review the amount and submit payment proof.'; ?></p>
            </div>
            <?php if ($order_reference !== ''): ?>
                <div class="youngo-checkout-reference">
                    <span><?php echo $youngo_checkout_is_arabic ? 'رقم الطلب' : 'Order reference'; ?></span>
                    <strong><?php echo youngo_checkout_e($order_reference); ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="youngo-account-page youngo-checkout-page" <?php echo $youngo_checkout_is_arabic ? 'dir="rtl"' : ''; ?>>
    <div class="youngo-container">
        <div class="youngo-account-panel youngo-checkout-panel">
            <div class="youngo-account-panel__header">
                <div>
                    <p class="youngo-eyebrow"><?php echo $youngo_checkout_is_arabic ? 'ملخص الطلب' : 'Order summary'; ?></p>
                    <h2><?php echo !empty($review_snapshot['item_title_snapshot']) ? youngo_checkout_e($review_snapshot['item_title_snapshot']) : (!empty($course['title']) ? youngo_checkout_e($course['title']) : get_phrase('Checkout request')); ?></h2>
                </div>
                <a class="youngo-button youngo-button--secondary youngo-button--small" href="<?php echo site_url('home/courses'); ?>"><?php echo $youngo_checkout_is_arabic ? 'العودة للدورات' : 'Back to courses'; ?></a>
            </div>

            <div class="youngo-empty-state youngo-checkout-notice">
                <span class="youngo-checkout-notice__icon" aria-hidden="true">✓</span>
                <div>
                    <p><?php echo youngo_checkout_e($notice_message); ?></p>
                </div>
            </div>

            <?php if ($this->session->flashdata('flash_message') != ''): ?>
                <div class="youngo-empty-state">
                    <p><?php echo youngo_checkout_e($this->session->flashdata('flash_message')); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($this->session->flashdata('error_message') != ''): ?>
                <div class="youngo-empty-state">
                    <p><?php echo youngo_checkout_e($this->session->flashdata('error_message')); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($order)): ?>
                <div class="youngo-learning-stats youngo-checkout-summary" aria-label="<?php echo $youngo_checkout_is_arabic ? 'ملخص الطلب' : 'Checkout order summary'; ?>">
                    <div>
                        <span><?php echo get_phrase('Order'); ?></span>
                        <strong><?php echo youngo_checkout_e($order_reference); ?></strong>
                    </div>
                    <div>
                        <span><?php echo get_phrase('Discount'); ?></span>
                        <strong><?php echo youngo_checkout_e($discount_amount); ?> <?php echo youngo_checkout_e($currency); ?></strong>
                    </div>
                    <div>
                        <span><?php echo get_phrase('Final amount'); ?></span>
                        <strong><?php echo youngo_checkout_e($final_amount); ?> <?php echo youngo_checkout_e($currency); ?></strong>
                    </div>
                </div>

                <div class="youngo-empty-state">
                    <div class="youngo-checkout-step"><span>01</span><strong><?php echo $youngo_checkout_is_arabic ? 'لو معاك كوبون خصم، أدخله أولًا' : 'Have a coupon? Enter it first'; ?></strong></div>
                    <p><?php echo get_phrase('Coupon'); ?>:
                        <?php if ($current_coupon_code !== ''): ?>
                            <strong><?php echo youngo_checkout_e($current_coupon_code); ?></strong>
                            <?php if ($current_coupon_type !== '' && $current_coupon_value !== ''): ?>
                                <span><?php echo $current_coupon_type === 'percentage' ? 'نسبة الخصم' : youngo_checkout_e($current_coupon_type); ?>: <?php echo youngo_checkout_e($coupon_value_label); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php echo get_phrase('No coupon applied'); ?>
                        <?php endif; ?>
                    </p>

                    <?php if ($can_edit_coupon): ?>
                        <form method="post" action="<?php echo site_url('youngo/checkout/coupon/apply/' . rawurlencode($order_reference)); ?>">
                            <?php if ($csrf_name !== '' && $csrf_hash !== ''): ?>
                                <input type="hidden" name="<?php echo youngo_checkout_e($csrf_name); ?>" value="<?php echo youngo_checkout_e($csrf_hash); ?>">
                            <?php endif; ?>
                            <label for="youngo_checkout_coupon_code"><?php echo get_phrase('Coupon code'); ?></label>
                            <input type="text" id="youngo_checkout_coupon_code" name="coupon_code" value="<?php echo youngo_checkout_e($current_coupon_code); ?>" placeholder="<?php echo get_phrase('Enter coupon code'); ?>" autocomplete="off">
                            <button type="submit" class="youngo-button youngo-button--small"><?php echo get_phrase('Apply'); ?></button>
                        </form>

                        <?php if ($current_coupon_code !== ''): ?>
                            <form method="post" action="<?php echo site_url('youngo/checkout/coupon/clear/' . rawurlencode($order_reference)); ?>">
                                <?php if ($csrf_name !== '' && $csrf_hash !== ''): ?>
                                    <input type="hidden" name="<?php echo youngo_checkout_e($csrf_name); ?>" value="<?php echo youngo_checkout_e($csrf_hash); ?>">
                                <?php endif; ?>
                                <button type="submit" class="youngo-button youngo-button--secondary youngo-button--small"><?php echo get_phrase('Clear coupon'); ?></button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><?php echo get_phrase('Coupon changes are available only while the order is still draft.'); ?></p>
                    <?php endif; ?>

                    <?php if ($zero_coupon_completion_available): ?>
                        <div class="youngo-checkout-free-success" role="status">
                            <span class="youngo-checkout-free-success__icon" aria-hidden="true">✓</span>
                            <div>
                                <strong>طلبك مجاني</strong>
                                <p>لا يلزم دفع أي مبلغ، الكوبون يغطي قيمة الطلب بالكامل.</p>
                            </div>
                        </div>
                        <form method="post" action="<?php echo site_url('youngo/checkout/zero-coupon/complete/' . rawurlencode($order_reference)); ?>">
                            <?php if ($csrf_name !== '' && $csrf_hash !== ''): ?>
                                <input type="hidden" name="<?php echo youngo_checkout_e($csrf_name); ?>" value="<?php echo youngo_checkout_e($csrf_hash); ?>">
                            <?php endif; ?>
                            <button type="submit" class="youngo-button youngo-button--small">متابعة التعلم</button>
                        </form>
                    <?php elseif ($zero_coupon_already_completed): ?>
                        <div class="youngo-checkout-free-success" role="status">
                            <span class="youngo-checkout-free-success__icon" aria-hidden="true">✓</span>
                            <div>
                                <strong>تم تفعيل الوصول</strong>
                                <p>اكتمل الطلب ويمكنك بدء التعلم الآن.</p>
                            </div>
                        </div>
                    <?php elseif (!empty($review_snapshot['zero_final_amount_policy_not_enabled'])): ?>
                        <p><?php echo youngo_checkout_e($zero_coupon_message !== '' ? $zero_coupon_message : get_phrase('This zero-amount coupon cannot activate access yet.')); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($order) && !$zero_coupon_is_zero_total_order): ?>
                <div class="youngo-empty-state" data-youngo-instapay-checkout-panel>
                    <div class="youngo-checkout-step"><span>03</span><strong><?php echo $youngo_checkout_is_arabic ? 'إرسال إثبات الدفع' : 'Submit payment proof'; ?></strong></div>
                    <p><strong><?php echo get_phrase('Instapay'); ?></strong></p>

                    <?php if ($zero_coupon_is_zero_total_order): ?>
                        <?php if ($zero_coupon_subscription_deferred): ?>
                            <p><?php echo get_phrase('Manual payment upload is disabled for zero-total subscription checkout. Subscription activation is deferred.'); ?></p>
                        <?php else: ?>
                            <p><?php echo get_phrase('Manual payment upload is not required for a zero-amount coupon checkout.'); ?></p>
                        <?php endif; ?>
                    <?php elseif (!$instapay_enabled): ?>
                        <p><?php echo get_phrase('Manual payment will be available soon.'); ?></p>
                    <?php else: ?>
                        <p><?php echo get_phrase('Final amount to pay'); ?>: <strong><?php echo youngo_checkout_e($instapay_expected_amount); ?> EGP</strong></p>
                        <?php if (!empty($instapay_target['label'])): ?>
                            <p><?php echo get_phrase('Payment account'); ?>: <?php echo youngo_checkout_e($instapay_target['label']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($instapay_target['address'])): ?>
                            <p><?php echo get_phrase('Instapay address'); ?>: <?php echo youngo_checkout_e($instapay_target['address']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($instapay_target['link'])): ?>
                            <p><a class="youngo-button youngo-button--secondary youngo-button--small" href="<?php echo youngo_checkout_e($instapay_target['link']); ?>" target="_blank" rel="noopener noreferrer"><?php echo get_phrase('Open Instapay link'); ?></a></p>
                        <?php endif; ?>
                        <?php if (!empty($instapay_target['instructions'])): ?>
                            <p><?php echo nl2br(youngo_checkout_e($instapay_target['instructions'])); ?></p>
                        <?php endif; ?>

                        <?php if ($instapay_status === 'pending_review'): ?>
                            <p><strong><?php echo get_phrase('Pending review'); ?></strong></p>
                            <p><?php echo get_phrase('Your payment screenshot was submitted and is waiting for admin review. Access is not granted until approval.'); ?></p>
                        <?php elseif ($instapay_status === 'approved'): ?>
                            <p><strong><?php echo get_phrase('Approved'); ?></strong></p>
                            <p><?php echo get_phrase('Your manual payment review is approved. Access issuance is handled by a later approval phase.'); ?></p>
                        <?php elseif ($instapay_status === 'rejected'): ?>
                            <p><strong><?php echo get_phrase('Rejected'); ?></strong></p>
                            <p><?php echo get_phrase('You may submit a new screenshot if the order is still eligible.'); ?></p>
                        <?php endif; ?>

                        <?php if ($instapay_can_submit): ?>
                            <form method="post" enctype="multipart/form-data" action="<?php echo youngo_checkout_e(isset($instapay['submit_url']) ? $instapay['submit_url'] : ''); ?>">
                                <?php if ($csrf_name !== '' && $csrf_hash !== ''): ?>
                                    <input type="hidden" name="<?php echo youngo_checkout_e($csrf_name); ?>" value="<?php echo youngo_checkout_e($csrf_hash); ?>">
                                <?php endif; ?>
                                <label for="youngo_instapay_screenshot"><?php echo get_phrase('Transaction screenshot'); ?></label>
                                <input type="file" id="youngo_instapay_screenshot" name="instapay_screenshot" accept="image/jpeg,image/png,image/webp" required>
                                <p><?php echo get_phrase('Accepted files'); ?>: JPG, PNG, WebP. <?php echo get_phrase('Maximum size'); ?>: <?php echo youngo_checkout_e($instapay_max_upload_mb); ?> MB.</p>

                                <label for="youngo_instapay_reference"><?php echo get_phrase('Transaction reference'); ?> (<?php echo get_phrase('optional'); ?>)</label>
                                <input type="text" id="youngo_instapay_reference" name="transaction_reference" value="" autocomplete="off" maxlength="255">

                                <label for="youngo_instapay_user_note"><?php echo get_phrase('Note'); ?> (<?php echo get_phrase('optional'); ?>)</label>
                                <textarea id="youngo_instapay_user_note" name="user_note" rows="3" maxlength="2000"></textarea>

                                <button type="submit" class="youngo-button youngo-button--small"><?php echo get_phrase('Submit payment for review'); ?></button>
                            </form>
                        <?php elseif ($instapay_status === ''): ?>
                            <p><?php echo get_phrase('Manual payment submission is not available for this order state.'); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($order) && !$zero_coupon_is_zero_total_order): ?>
                <div class="youngo-empty-state" data-youngo-disabled-payment-methods>
                    <div class="youngo-checkout-step"><span>04</span><strong><?php echo get_phrase('Payment methods'); ?></strong></div>
                    <p><strong><?php echo get_phrase('Cards'); ?></strong>: <?php echo get_phrase('Not available yet'); ?></p>
                    <p><strong><?php echo get_phrase('Digital Wallets'); ?></strong>: <?php echo get_phrase('Not available yet'); ?></p>
                    <p><?php echo get_phrase('Paymob redirect is disabled or not ready for this local test order. There is no gateway selection.'); ?></p>
                    <button type="button" class="youngo-button youngo-button--small youngo-button--secondary" disabled><?php echo get_phrase('Not available yet'); ?></button>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>
