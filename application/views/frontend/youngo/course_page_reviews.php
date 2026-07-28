<?php
if (!function_exists('youngo_frontend_content_language') && file_exists(APPPATH . 'helpers/youngo_frontend_content_helper.php')) {
    $this->load->helper('youngo_frontend_content');
}

$youngo_frontend_language = isset($youngo_frontend_language) ? $youngo_frontend_language : (function_exists('youngo_frontend_content_language') ? youngo_frontend_content_language() : 'english');

if (!function_exists('youngo_course_detail_phrase')) {
    function youngo_course_detail_phrase($phrase_key, $fallback = '', $language_code = null) {
        $language_code = $language_code ?: 'english';
        $value = youngo_frontend_phrase($phrase_key, $fallback, $language_code);

        if ($language_code === 'arabic'
            && function_exists('youngo_frontend_phrase_value_has_arabic')
            && !youngo_frontend_phrase_value_has_arabic($value)
            && function_exists('youngo_frontend_local_phrase_value')
        ) {
            $local_value = youngo_frontend_local_phrase_value($phrase_key, 'arabic');
            if ($local_value !== '' && youngo_frontend_phrase_value_has_arabic($local_value)) {
                return $local_value;
            }
        }

        return $value;
    }
}

$user_id = (int) $this->session->userdata('user_id');
$admin_login = $this->session->userdata('admin_login');
$my_rating = $this->db->where('user_id', $user_id)->where('ratable_id', $course_details['id'])->where('ratable_type', 'course')->get('rating');
$youngo_review_access_allowed = false;

if ($user_id > 0 && file_exists(APPPATH . 'helpers/youngo_entitlement_helper.php') && file_exists(APPPATH . 'models/Youngo_entitlement_model.php')) {
    try {
        $this->load->helper('youngo_entitlement');
        if (function_exists('youngo_get_course_access_state')) {
            $youngo_review_access_state = youngo_get_course_access_state($user_id, $course_details['id'], array(
                'allow_admin_bypass' => false,
                'allow_instructor_bypass' => false,
                'gate' => 'course_review',
            ));
            $youngo_review_access_allowed = is_array($youngo_review_access_state) && !empty($youngo_review_access_state['has_access']);
        }
    } catch (Throwable $exception) {
        $youngo_review_access_allowed = false;
    }
}

if (!$youngo_review_access_allowed) {
    $youngo_review_access_allowed = (bool) enroll_status($course_details['id']);
}
?>

<?php if ($my_rating->num_rows() == 0 && $youngo_review_access_allowed): ?>
    <div class="youngo-review-form" id="course_page_add_review_form">
        <h3><?php echo youngo_course_detail_phrase('write_a_review', '', $youngo_frontend_language); ?></h3>
        <form class="ajaxForm" action="<?php echo site_url('home/rate_course'); ?>" method="post">
            <input type="hidden" name="course_id" value="<?php echo $course_details['id']; ?>">
            <label>
                <span><?php echo youngo_course_detail_phrase('rating', '', $youngo_frontend_language); ?></span>
                <select name="starRating">
                    <option value="1"><?php echo youngo_course_detail_phrase('1_star_rating', '', $youngo_frontend_language); ?></option>
                    <option value="2"><?php echo youngo_course_detail_phrase('2_star_rating', '', $youngo_frontend_language); ?></option>
                    <option value="3"><?php echo youngo_course_detail_phrase('3_star_rating', '', $youngo_frontend_language); ?></option>
                    <option value="4"><?php echo youngo_course_detail_phrase('4_star_rating', '', $youngo_frontend_language); ?></option>
                    <option value="5"><?php echo youngo_course_detail_phrase('5_star_rating', '', $youngo_frontend_language); ?></option>
                </select>
            </label>
            <label>
                <span><?php echo youngo_course_detail_phrase('review', '', $youngo_frontend_language); ?></span>
                <textarea name="review" rows="4" placeholder="<?php echo youngo_course_detail_phrase('write_your_comment', '', $youngo_frontend_language); ?>"></textarea>
            </label>
            <button class="youngo-button" type="submit"><?php echo youngo_course_detail_phrase('submit', '', $youngo_frontend_language); ?></button>
        </form>
    </div>
<?php endif; ?>

<?php
$ratings = $this->crud_model->get_ratings('course', $course_details['id'])->result_array();
if (count($ratings) == 0):
?>
    <p class="youngo-empty-note"><?php echo youngo_course_detail_phrase('no_reviews_yet.', '', $youngo_frontend_language); ?></p>
<?php endif; ?>

<?php foreach ($ratings as $rating):
    $user_details = $this->user_model->get_user($rating['user_id'])->row_array();
    if (empty($user_details)) {
        continue;
    }
?>
    <article class="youngo-review-card" id="userReview<?php echo $rating['id']; ?>">
        <div class="youngo-review-card__avatar">
            <img loading="lazy" src="<?php echo $this->user_model->get_user_image_url($user_details['id']); ?>" alt="<?php echo htmlspecialchars($user_details['first_name'] . ' ' . $user_details['last_name']); ?>">
        </div>
        <div class="youngo-review-card__content">
            <div class="youngo-review-card__top">
                <div>
                    <h3><?php echo $user_details['first_name'] . ' ' . $user_details['last_name']; ?></h3>
                    <p><?php echo date('d-M-Y', $rating['date_added']); ?></p>
                </div>
                <div class="youngo-stars" aria-label="<?php echo (int) $rating['rating']; ?> <?php echo youngo_course_detail_phrase('stars', '', $youngo_frontend_language); ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fa-solid fa-star <?php echo $rating['rating'] >= $i ? 'is-filled' : ''; ?>"></i>
                    <?php endfor; ?>
                </div>
            </div>
            <p class="youngo-review-card__body"><?php echo $rating['review']; ?></p>
            <?php if ($user_details['id'] == $user_id || $admin_login): ?>
                <div class="youngo-review-card__actions">
                    <?php if ($user_details['id'] == $user_id): ?>
                        <a class="youngo-review-action" href="#" onclick="$('#myReview<?php echo $rating['id']; ?>').toggle(); return false;"><i class="fas fa-pencil-alt"></i> <span><?php echo youngo_course_detail_phrase('edit', '', $youngo_frontend_language); ?></span></a>
                    <?php endif; ?>
                    <a class="youngo-review-action is-danger" href="#" onclick="actionTo('<?php echo site_url('home/remove_rating/' . $course_details['id'] . '/' . $rating['id']); ?>'); return false;" aria-label="<?php echo youngo_course_detail_phrase('remove_review', '', $youngo_frontend_language); ?>"><i class="fas fa-trash-alt"></i> <span><?php echo youngo_course_detail_phrase('remove', '', $youngo_frontend_language); ?></span></a>
                </div>
            <?php endif; ?>
            <?php if ($user_details['id'] == $user_id): ?>
                <div class="youngo-review-edit d-hidden" id="myReview<?php echo $rating['id']; ?>">
                    <form class="ajaxForm" action="<?php echo site_url('home/rate_course'); ?>" method="post">
                        <input type="hidden" name="course_id" value="<?php echo $course_details['id']; ?>">
                        <select name="starRating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php if ($rating['rating'] == $i) echo 'selected'; ?>><?php echo youngo_course_detail_phrase($i . '_star_rating', '', $youngo_frontend_language); ?></option>
                            <?php endfor; ?>
                        </select>
                        <textarea rows="4" name="review" placeholder="<?php echo youngo_course_detail_phrase('write_your_comment', '', $youngo_frontend_language); ?>"><?php echo $rating['review']; ?></textarea>
                        <button class="youngo-button youngo-button--small" type="submit"><?php echo youngo_course_detail_phrase('submit', '', $youngo_frontend_language); ?></button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>
