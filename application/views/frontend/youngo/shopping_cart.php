<?php
$cart_items = $this->session->userdata('cart_items') ? $this->session->userdata('cart_items') : array();
$cart_count = is_array($cart_items) ? count($cart_items) : 0;
?>

<section class="youngo-commerce-hero">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo get_phrase('Breadcrumb'); ?>">
            <a href="<?php echo site_url('home'); ?>"><?php echo get_phrase('Home'); ?></a>
            <span>/</span>
            <span><?php echo get_phrase('Shopping Cart'); ?></span>
        </nav>

        <div class="youngo-commerce-hero__grid">
            <div>
                <p class="youngo-eyebrow"><?php echo get_phrase('Course checkout'); ?></p>
                <h1><?php echo get_phrase('Shopping Cart'); ?></h1>
                <p><?php echo get_phrase('Review selected courses, apply available coupons, and continue through the existing secure Academy LMS checkout flow.'); ?></p>
            </div>

            <aside class="youngo-commerce-summary-card" aria-label="<?php echo get_phrase('Cart summary'); ?>">
                <span><?php echo get_phrase('Items ready'); ?></span>
                <strong><?php echo $cart_count; ?></strong>
                <small><?php echo get_phrase('Payments are kept in test or sandbox mode for this demo environment.'); ?></small>
            </aside>
        </div>
    </div>
</section>

<section class="youngo-commerce-page">
    <div class="youngo-container">
        <div id="shoppingCart">
            <?php include "shopping_cart_inner_view.php"; ?>
        </div>
    </div>
</section>
