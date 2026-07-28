<?php
$cart_items = $this->session->userdata('cart_items') ? $this->session->userdata('cart_items') : array();
$total = 0;
$coupon_details = null;
$coupon_discounted_price = 0;
$tax = 0;
?>
<div class="youngo-cart-fragment">
    <?php if (count($cart_items) == 0): ?>
        <div class="youngo-commerce-empty">
            <div class="youngo-commerce-empty__icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <p class="youngo-eyebrow"><?php echo get_phrase('Empty cart'); ?></p>
            <h2><?php echo get_phrase('Your cart is waiting for a course'); ?></h2>
            <p><?php echo get_phrase('Browse YounGo courses and add a paid course when you are ready to check out. Free courses can be enrolled from the course details page.'); ?></p>
            <a class="youngo-button" href="<?php echo site_url('home/courses'); ?>"><?php echo get_phrase('Browse courses'); ?></a>
        </div>
    <?php else: ?>
        <div class="youngo-cart-layout">
            <div class="youngo-cart-list">
                <div class="youngo-commerce-toolbar">
                    <div>
                        <p class="youngo-eyebrow"><?php echo get_phrase('Cart items'); ?></p>
                        <h2><?php echo get_phrase('Your selected courses'); ?></h2>
                    </div>
                    <a class="youngo-button youngo-button--secondary" href="<?php echo site_url('home/courses'); ?>"><?php echo get_phrase('Add more courses'); ?></a>
                </div>

                <?php foreach ($cart_items as $item): ?>
                    <?php
                    $course_details = $this->crud_model->get_course_by_id($item)->row_array();
                    if (!$course_details) continue;
                    $course_price = 0;
                    if (!$course_details['is_free_course']) {
                        $course_price = $course_details['discount_flag'] ? $course_details['discounted_price'] : $course_details['price'];
                        $total += $course_price;
                    }
                    $creator_id = !empty($course_details['creator']) ? (int) $course_details['creator'] : (int) trim(strtok((string) $course_details['user_id'], ','));
                    $instructor = $creator_id > 0 ? $this->user_model->get_all_user($creator_id)->row_array() : array();
                    $instructor_name = !empty($instructor) ? trim($instructor['first_name'] . ' ' . $instructor['last_name']) : '';
                    $lesson_count = $this->crud_model->get_lessons('course', $course_details['id'])->num_rows();
                    ?>
                    <article class="youngo-cart-row">
                        <img loading="lazy" src="<?php echo $this->crud_model->get_course_thumbnail_url($course_details['id']); ?>" alt="<?php echo htmlspecialchars($course_details['title']); ?>">
                        <div class="youngo-cart-row__body">
                            <a href="<?php echo site_url('home/course/' . slugify($course_details['title']) . '/' . $course_details['id']); ?>"><?php echo htmlspecialchars($course_details['title']); ?></a>
                            <div class="youngo-cart-row__meta">
                                <?php if ($instructor_name !== ''): ?><span><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($instructor_name); ?></span><?php endif; ?>
                                <span><i class="fa-regular fa-list-alt"></i> <?php echo $lesson_count . ' ' . get_phrase('lessons'); ?></span>
                            </div>
                        </div>
                        <strong>
                            <?php if ($course_details['is_free_course']): ?>
                                <?php echo get_phrase('Free'); ?>
                            <?php elseif ($course_details['discount_flag']): ?>
                                <?php echo currency($course_details['discounted_price']); ?>
                            <?php else: ?>
                                <?php echo currency($course_details['price']); ?>
                            <?php endif; ?>
                        </strong>
                        <button type="button" onclick="actionTo('<?php echo site_url('home/handle_cart_items/' . $course_details['id']); ?>');" aria-label="<?php echo get_phrase('Remove'); ?>"><i class="fa-solid fa-trash-can"></i></button>
                    </article>
                <?php endforeach; ?>
            </div>

            <aside class="youngo-cart-summary" aria-label="<?php echo get_phrase('Order summary'); ?>">
                <h3><?php echo get_phrase('Order summary'); ?></h3>

                <?php if (isset($coupon_code) && !empty($coupon_code)): ?>
                    <?php if ($this->crud_model->check_coupon_validity($coupon_code)): ?>
                        <?php
                        $coupon_details = $this->crud_model->get_coupon_details_by_code($coupon_code)->row_array();
                        $coupon_discounted_price = ($total * $coupon_details['discount_percentage']) / 100;
                        $total = $total - $coupon_discounted_price;
                        $total = ($total > 0) ? $total : 0;
                        $this->session->set_userdata('applied_coupon', $coupon_code);
                        ?>
                        <div class="youngo-commerce-alert youngo-commerce-alert--success">
                            <?php echo get_phrase('You received') . ' ' . currency($coupon_discounted_price) . ' (' . $coupon_details['discount_percentage']; ?>%) <?php echo site_phrase('coupon discount'); ?>
                        </div>
                    <?php else: ?>
                        <div class="youngo-commerce-alert youngo-commerce-alert--danger">
                            <?php echo get_phrase('Your coupon code has expired'); ?>
                            <?php $this->session->set_userdata('applied_coupon', null); ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php $this->session->set_userdata('applied_coupon', null); ?>
                <?php endif; ?>

                <div class="youngo-cart-summary__line">
                    <span><?php echo get_phrase('Subtotal'); ?></span>
                    <strong><?php echo currency($total); ?></strong>
                </div>

                <?php if (get_settings('course_selling_tax') > 0): ?>
                    <?php
                    $tax = round(($total / 100) * get_settings('course_selling_tax'), 2);
                    $total = round($total + $tax, 2);
                    ?>
                    <div class="youngo-cart-summary__line">
                        <span><?php echo get_phrase('Tax'); ?> <small>(<?php echo get_settings('course_selling_tax'); ?>%)</small></span>
                        <strong><?php echo currency($tax); ?></strong>
                    </div>
                <?php endif; ?>

                <div class="youngo-cart-total">
                    <?php $this->session->set_userdata('total_price_of_checking_out', $total); ?>
                    <span><?php echo get_phrase('Total'); ?></span>
                    <strong><?php echo currency($total); ?></strong>
                </div>

                <form class="youngo-coupon-form ajaxForm" action="<?php echo site_url('home/apply_coupon'); ?>" method="post">
                    <label for="youngo_coupon_code"><?php echo get_phrase('Coupon code'); ?></label>
                    <div>
                        <input type="text" id="youngo_coupon_code" name="coupon_code" placeholder="<?php echo site_phrase('Apply coupon'); ?>" aria-label="<?php echo site_phrase('Apply coupon'); ?>">
                        <button type="submit"><?php echo get_phrase('Apply'); ?></button>
                    </div>
                </form>

                <div class="youngo-commerce-alert">
                    <strong><?php echo get_phrase('Demo checkout notice'); ?></strong>
                    <span><?php echo get_phrase('Real payments are not active for this environment. Any available gateway should remain disabled or sandbox-only before server testing.'); ?></span>
                </div>

                <?php if (isset($coupon_code) && !empty($coupon_code) && isset($coupon_details) && $coupon_details['discount_percentage'] == 100 && $total == 0 && $coupon_details['expiry_date'] >= time()): ?>
                    <a href="<?php echo site_url('home/coupon_offer_100_percent'); ?>" class="youngo-button"><?php echo get_phrase('Enroll Now'); ?></a>
                <?php else: ?>
                    <form class="youngo-checkout-form" action="<?php echo site_url('home/course_payment'); ?>" method="post">
                        <label class="youngo-gift-toggle" for="is_gift">
                            <input type="checkbox" id="is_gift" name="is_gift" value="1" <?php if (isset($_GET['gift'])) echo 'checked'; ?> onchange="
                                if ($(this).prop('checked') == true) {
                                    $('#gift_email_section').removeClass('d-hidden');
                                } else {
                                    $('#gift_email_section').addClass('d-hidden');
                                }
                                $('#gift_email').prop('required', $(this).prop('checked') == true);
                            ">
                            <span><?php echo get_phrase('Send as a gift'); ?></span>
                        </label>

                        <div id="gift_email_section" class="youngo-gift-field <?php if (!isset($_GET['gift'])) echo 'd-hidden'; ?>">
                            <label for="gift_email"><?php echo get_phrase('Recipient email'); ?></label>
                            <input type="email" name="gift_email" id="gift_email" onkeyup="check_gift_user(this)" placeholder="<?php echo site_phrase('Email address'); ?>" <?php if (isset($_GET['gift'])) echo 'required'; ?>>
                            <span id="check_gift_user_message"></span>
                        </div>

                        <button id="payment-button" class="youngo-button" type="submit"><?php echo get_phrase('Continue to Payment'); ?></button>
                    </form>
                <?php endif; ?>
            </aside>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
    var youngoGiftTimer = 0;
    function check_gift_user(e) {
        if (!window.jQuery) {
            return;
        }

        $('#payment-button').attr('disabled', true);
        $('#check_gift_user_message').html('<?php echo get_phrase('Searching'); ?>...');
        var gift_email = $(e).val().replace(/\s/g, '');

        clearTimeout(youngoGiftTimer);
        youngoGiftTimer = setTimeout(function () {
            actionTo('<?php echo site_url('home/check_gift_user?gift_email='); ?>' + gift_email, 'post');
            $(e).val(gift_email);
            $('#payment-button').attr('disabled', false);
        }, 2000);
    }
</script>
