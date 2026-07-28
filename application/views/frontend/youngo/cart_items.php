<?php $cart_items = $this->session->userdata('cart_items') ? $this->session->userdata('cart_items') : array(); ?>
<?php foreach ($cart_items as $cart_item): ?>
    <?php
    $course_details = $this->crud_model->get_course_by_id($cart_item)->row_array();
    if (!$course_details) continue;
    $creator_id = !empty($course_details['creator']) ? (int) $course_details['creator'] : (int) trim(strtok((string) $course_details['user_id'], ','));
    $instructor = $creator_id > 0 ? $this->user_model->get_all_user($creator_id)->row_array() : array();
    $instructor_name = !empty($instructor) ? trim($instructor['first_name'] . ' ' . $instructor['last_name']) : '';
    ?>
    <div class="youngo-mini-course">
        <a href="<?php echo site_url('home/course/' . slugify($course_details['title']) . '/' . $course_details['id']); ?>">
            <img loading="lazy" src="<?php echo $this->crud_model->get_course_thumbnail_url($course_details['id']); ?>" alt="<?php echo htmlspecialchars($course_details['title']); ?>">
            <span>
                <strong><?php echo htmlspecialchars($course_details['title']); ?></strong>
                <?php if ($instructor_name !== ''): ?><small><?php echo get_phrase('By'); ?> <?php echo htmlspecialchars($instructor_name); ?></small><?php endif; ?>
            </span>
        </a>
        <button type="button" onclick="actionTo('<?php echo site_url('home/handle_cart_items/' . $course_details['id']); ?>');" aria-label="<?php echo get_phrase('Remove from cart'); ?>"><i class="fa-solid fa-minus"></i></button>
    </div>
<?php endforeach; ?>
<?php if (count($cart_items) == 0): ?>
    <p class="youngo-empty-note"><?php echo get_phrase('You have no items in your cart!'); ?></p>
<?php endif; ?>
