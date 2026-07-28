<?php
if (!function_exists('youngo_frontend_content_language') && file_exists(APPPATH . 'helpers/youngo_frontend_content_helper.php')) {
    $this->load->helper('youngo_frontend_content');
}
if (!function_exists('youngo_checkout_cta_decision') && file_exists(APPPATH . 'helpers/youngo_checkout_cta_helper.php')) {
    $this->load->helper('youngo_checkout_cta');
}

$youngo_frontend_language = isset($youngo_frontend_language) ? $youngo_frontend_language : (function_exists('youngo_frontend_content_language') ? youngo_frontend_content_language() : 'english');
$youngo_course_home_url = function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_frontend_language) : site_url('home');
$youngo_course_courses_url = function_exists('youngo_frontend_courses_url') ? youngo_frontend_courses_url($youngo_frontend_language) : site_url('home/courses');
$youngo_course_contact_url = function_exists('youngo_frontend_contact_url') ? youngo_frontend_contact_url($youngo_frontend_language) : site_url('home/contact_us');
$course_details = $this->crud_model->get_course_by_id($course_id)->row_array();
if (function_exists('youngo_frontend_translate_course_row')) {
    $course_details = youngo_frontend_translate_course_row($course_details, $youngo_frontend_language);
}

if (!function_exists('youngo_course_detail_phrase')) {
    function youngo_course_detail_phrase($phrase_key, $fallback = '', $language_code = null) {
        $language_code = $language_code ?: 'english';
        static $arabic_instructor_labels = array(
            'created_by' => 'إعداد',
            'instructor' => 'المدرّس',
            'meet_your_guide' => 'تعرّف على مدرّسك',
            'course_guide' => 'مدرّس الدورة',
            'trusted_guide' => 'مدرّس موثوق',
            'structured_lessons' => 'دروس منظّمة',
            'view_profile' => 'عرض الملف الشخصي',
            'follow' => 'متابعة',
            'unfollow' => 'إلغاء المتابعة'
        );
        if ($language_code === 'arabic' && $phrase_key === 'course_content_not_available_yet') {
            return 'محتوى الدورة غير متاح بعد';
        }
        if ($language_code === 'arabic' && isset($arabic_instructor_labels[$phrase_key])) {
            return $arabic_instructor_labels[$phrase_key];
        }
        $value = youngo_frontend_phrase($phrase_key, $fallback, $language_code);

        $needs_local_arabic_fallback = $language_code === 'arabic'
            && function_exists('youngo_frontend_phrase_value_has_arabic')
            && function_exists('youngo_frontend_local_phrase_value')
            && (
                !youngo_frontend_phrase_value_has_arabic($value)
                || strpos($value, 'ترجمة مطلوبة') !== false
                || strpos($value, '????') !== false
                || stripos($value, 'translation required') !== false
                || stripos($value, 'translation needed') !== false
            );

        if ($needs_local_arabic_fallback) {
            $local_value = youngo_frontend_local_phrase_value($phrase_key, 'arabic');
            if ($local_value !== '' && youngo_frontend_phrase_value_has_arabic($local_value)) {
                return $local_value;
            }

            $course_detail_duration_units = array(
                'hours' => 'ساعات',
                'minutes' => 'دقائق',
                'months' => 'أشهر',
            );
            if (isset($course_detail_duration_units[$phrase_key])) {
                return $course_detail_duration_units[$phrase_key];
            }
        }

        return $value;
    }
}

if (empty($course_details)):
?>
    <section class="youngo-placeholder">
        <div class="youngo-container">
            <p class="youngo-eyebrow"><?php echo youngo_course_detail_phrase('course', '', $youngo_frontend_language); ?></p>
            <h1><?php echo youngo_course_detail_phrase('course_not_found', '', $youngo_frontend_language); ?></h1>
            <a class="youngo-button" href="<?php echo $youngo_course_courses_url; ?>"><?php echo youngo_course_detail_phrase('browse_courses', '', $youngo_frontend_language); ?></a>
        </div>
    </section>
<?php
    return;
endif;

if (!function_exists('youngo_course_duration_label')) {
    function youngo_course_duration_label($duration, $language_code = 'english') {
        $original_duration = trim((string) $duration);
        if ($original_duration === '') {
            return '';
        }

        $normalized_duration = preg_replace('/\s*(?:\?{2,}\s*)+:?\s*hours?$/i', '', $original_duration);
        $normalized_duration = preg_replace('/\s*(?:ترجمة مطلوبة|translation required|translation needed):?\s*hours?$/iu', '', $normalized_duration);
        $normalized_duration = preg_replace('/\s*hours?$/i', '', $normalized_duration);
        $normalized_duration = preg_replace('/\.\d+/', '', trim($normalized_duration));

        if ($normalized_duration === '' && preg_match('/hours?$/i', $original_duration)) {
            return '';
        }

        if (preg_match('/^([0-9]+)\s*hours?$/i', $original_duration, $hour_matches)) {
            return $language_code === 'arabic'
                ? (int) $hour_matches[1] . ' ' . youngo_course_detail_phrase('hours', '', $language_code)
                : (int) $hour_matches[1] . 'h';
        }

        if (preg_match('/^[0-9]+$/', $normalized_duration)) {
            return $language_code === 'arabic'
                ? (int) $normalized_duration . ' ' . youngo_course_detail_phrase('hours', '', $language_code)
                : (int) $normalized_duration . 'h';
        }

        if (preg_match('/^hours?$/i', $original_duration)) {
            return youngo_course_detail_phrase('hours', $original_duration, $language_code);
        }

        if (preg_match('/^([0-9]+)\s*minutes?$/i', $original_duration, $minute_matches)) {
            return $language_code === 'arabic'
                ? (int) $minute_matches[1] . ' ' . youngo_course_detail_phrase('minutes', '', $language_code)
                : (int) $minute_matches[1] . 'm';
        }

        if (preg_match('/^minutes?$/i', $original_duration)) {
            return youngo_course_detail_phrase('minutes', $original_duration, $language_code);
        }

        if (!preg_match('/^\d{1,3}:\d{1,2}(:\d{1,2})?$/', $normalized_duration)) {
            return $original_duration;
        }

        $parts = array_map('intval', explode(':', $normalized_duration));
        if (count($parts) === 2) {
            $hours = 0;
            $minutes = $parts[0];
            $seconds = $parts[1];
        } else {
            $hours = $parts[0];
            $minutes = $parts[1];
            $seconds = $parts[2];
        }

        if ($seconds >= 30) {
            $minutes++;
        }

        if ($minutes >= 60) {
            $hours += (int) floor($minutes / 60);
            $minutes = $minutes % 60;
        }

        $labels = array();
        if ($hours > 0) {
            $labels[] = $language_code === 'arabic'
                ? $hours . ' ' . youngo_course_detail_phrase('hours', '', $language_code)
                : $hours . 'h';
        }
        if ($minutes > 0) {
            $labels[] = $language_code === 'arabic'
                ? $minutes . ' ' . youngo_course_detail_phrase('minutes', '', $language_code)
                : $minutes . 'm';
        }

        return empty($labels)
            ? ($language_code === 'arabic' ? '0 ' . youngo_course_detail_phrase('minutes', '', $language_code) : '0m')
            : implode(' ', $labels);
    }
}

if (!function_exists('youngo_course_date_label')) {
    function youngo_course_date_label($timestamp, $language)
    {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return '';
        }

        return $language === 'arabic' ? date('d/m/Y', $timestamp) : date('M d, Y', $timestamp);
    }
}

$lessons = $this->crud_model->get_lessons('course', $course_details['id']);
$instructor_details = $this->user_model->get_all_user($course_details['creator'])->row_array();
$course_duration = $this->crud_model->get_total_duration_of_lesson_by_course_id($course_details['id']);
$course_duration_label = youngo_course_duration_label($course_duration, $youngo_frontend_language);
$number_of_enrolments = $this->crud_model->enrol_history($course_details['id'])->num_rows();
$total_rating = $this->crud_model->get_ratings('course', $course_details['id'], true)->row()->rating;
$number_of_ratings = $this->crud_model->get_ratings('course', $course_details['id'])->num_rows();
$average_rating = $number_of_ratings > 0 ? round($total_rating / $number_of_ratings, 1) : 0;
$average_ceil_rating = $number_of_ratings > 0 ? ceil($total_rating / $number_of_ratings) : 0;
$my_wishlist_items = array();
$current_user_id = (int) $this->session->userdata('user_id');

$youngo_access_state = array(
    'has_access' => false,
    'lesson_access_allowed' => false,
    'course_visible_in_my_courses' => false,
    'access_source' => 'none',
    'status' => 'none',
    'is_lifetime' => false,
    'start_date' => null,
    'expiry_date' => null,
    'warning_80_percent' => false,
    'course_access_mode' => isset($course_details['youngo_access_mode']) ? $course_details['youngo_access_mode'] : null,
    'subscription_eligible' => isset($course_details['youngo_access_mode']) ? ($course_details['youngo_access_mode'] !== 'purchase_only' && (empty($course_details['youngo_subscription_excluded']) || (int) $course_details['youngo_subscription_excluded'] !== 1)) : false,
    'lock_reason' => $current_user_id > 0 ? null : 'login_required',
    'message_key' => $current_user_id > 0 ? 'access_none' : 'access_login_required',
    'source_record_id' => null,
    'legacy_enrol_id' => null,
);
$youngo_entitlement_available = false;

if (file_exists(APPPATH . 'helpers/youngo_entitlement_helper.php') && file_exists(APPPATH . 'models/Youngo_entitlement_model.php')) {
    $this->load->helper('youngo_entitlement');
    if (function_exists('youngo_get_course_access_state')) {
        $youngo_entitlement_available = true;
        try {
            if ($current_user_id > 0) {
                $youngo_access_state = youngo_get_course_access_state($current_user_id, $course_details['id'], array(
                    'allow_admin_bypass' => true,
                    'allow_instructor_bypass' => true,
                ));
            } elseif (function_exists('youngo_default_course_access_state')) {
                $youngo_access_state = youngo_default_course_access_state($course_details['id']);
                $youngo_access_state['course_access_mode'] = isset($course_details['youngo_access_mode']) ? $course_details['youngo_access_mode'] : null;
                $youngo_access_state['subscription_eligible'] = isset($course_details['youngo_access_mode']) ? ($course_details['youngo_access_mode'] !== 'purchase_only' && (empty($course_details['youngo_subscription_excluded']) || (int) $course_details['youngo_subscription_excluded'] !== 1)) : false;
                $youngo_access_state['lock_reason'] = 'login_required';
                $youngo_access_state['message_key'] = 'access_login_required';
            }
        } catch (Throwable $exception) {
            $youngo_entitlement_available = false;
        }
    }
}

$legacy_is_purchased = is_purchased($course_details['id']);
$youngo_course_player_title = isset($course_details['youngo_canonical_title']) ? $course_details['youngo_canonical_title'] : $course_details['title'];
$youngo_lecture_count = $this->db->get_where('lesson', array('course_id' => $course_details['id'], 'lesson_type !=' => 'quiz'))->num_rows();
$youngo_has_access = $youngo_entitlement_available && !empty($youngo_access_state['has_access']);
$youngo_access_mode = isset($course_details['youngo_access_mode']) ? (string) $course_details['youngo_access_mode'] : '';
$youngo_is_managed_access = in_array($youngo_access_mode, array('subscription_only', 'subscription_and_purchase', 'purchase_only'), true);
$youngo_is_subscription_only = $youngo_access_mode === 'subscription_only';
$youngo_checkout_not_ready = $youngo_is_subscription_only && !$youngo_has_access;
$youngo_managed_access_message = 'subscribe_to_unlock_this_course';
$youngo_subscription_url = site_url('subscriptions');
$youngo_status_class = $youngo_has_access ? 'is-active' : 'is-neutral';
$youngo_status_icon = 'fa-circle-info';
$youngo_status_label = 'course_access';
$youngo_status_message = '';

if ($youngo_entitlement_available) {
    if ($youngo_has_access) {
        $youngo_status_class = 'is-active';
        $youngo_status_icon = 'fa-circle-check';
        if ($youngo_access_state['access_source'] === 'admin') {
            $youngo_status_label = 'admin_access';
            $youngo_status_message = 'you_can_preview_this_course_as_an_admin.';
        } elseif ($youngo_access_state['access_source'] === 'instructor') {
            $youngo_status_label = 'instructor_access';
            $youngo_status_message = 'you_are_assigned_to_this_course_and_can_preview_the_learning_flow.';
        } elseif ($youngo_access_state['access_source'] === 'subscription') {
            $youngo_status_label = 'subscription_access';
            $youngo_status_message = 'your_active_subscription_includes_this_course.';
        } elseif ($youngo_access_state['access_source'] === 'manual_grant') {
            $youngo_status_label = 'manual_grant_access';
            $youngo_status_message = 'your_school/admin_has_granted_access_to_this_course.';
        } elseif ($youngo_access_state['access_source'] === 'course_purchase') {
            $youngo_status_label = 'purchased_access';
            $youngo_status_message = 'your_account_has_active_purchased_access_to_this_course.';
        } else {
            $youngo_status_label = 'access_active';
            $youngo_status_message = 'you_can_continue_this_course_from_your_account.';
        }
    } elseif (in_array($youngo_access_state['status'], array('expired', 'locked', 'revoked'))) {
        $youngo_status_class = 'is-locked';
        $youngo_status_icon = 'fa-lock';
        $youngo_status_label = 'access_locked';
        $youngo_status_message = $youngo_access_state['status'] === 'expired'
            ? 'previous_access_has_expired._progress_remains_saved_in_your_account.'
            : 'this_course_is_currently_locked_for_this_account.';
    } elseif ($current_user_id <= 0) {
        $youngo_status_class = 'is-neutral';
        $youngo_status_icon = 'fa-user-lock';
        $youngo_status_label = 'sign_in_to_track_access';
        $youngo_status_message = 'use_a_student_account_to_keep_course_access_and_progress_in_one_place.';
    } elseif (!empty($youngo_access_state['subscription_eligible']) && $youngo_access_state['course_access_mode'] === 'subscription_only') {
        $youngo_status_class = 'is-planned';
        $youngo_status_icon = 'fa-calendar-check';
        $youngo_status_label = 'subscription_course';
        $youngo_status_message = $youngo_managed_access_message;
    }

    if (!empty($youngo_access_state['warning_80_percent'])) {
        $youngo_status_class = 'is-warning';
        $youngo_status_icon = 'fa-triangle-exclamation';
        $youngo_status_message = 'your_access_period_is_almost_finished._continue_learning_while_it_is_active.';
    }
}

$youngo_checkout_cta_decision = array(
    'show_cta' => false,
    'reason_code' => 'checkout_cta_helper_unavailable',
    'state' => 'disabled',
    'label_text' => '',
    'target_url' => null,
);
if (function_exists('youngo_checkout_cta_decision')) {
    try {
        $youngo_checkout_cta_decision = youngo_checkout_cta_decision($course_details, $current_user_id, array(
            'access_state' => $youngo_access_state,
        ));
    } catch (Throwable $exception) {
        $youngo_checkout_cta_decision['reason_code'] = 'checkout_cta_decision_failed';
    }
}

if ($current_user_id > 0) {
    $wishlist = $this->user_model->get_all_user($current_user_id)->row('wishlist');
    if ($wishlist != '') {
        $my_wishlist_items = json_decode($wishlist, true);
        $my_wishlist_items = is_array($my_wishlist_items) ? $my_wishlist_items : array();
    }
}

$outcomes = json_decode($course_details['outcomes']);
$outcomes = is_array($outcomes) ? $outcomes : array();
$requirements = json_decode($course_details['requirements']);
$requirements = is_array($requirements) ? $requirements : array();
$faqs = json_decode($course_details['faqs'], true);
$faqs = is_array($faqs) ? $faqs : array();
$sections = $this->crud_model->get_section('course', $course_details['id'])->result_array();
if (function_exists('youngo_frontend_translate_section_rows')) {
    $sections = youngo_frontend_translate_section_rows($sections, $youngo_frontend_language);
}
$related_courses = $this->crud_model->get_related_courses($course_details['category_id'], $course_details['sub_category_id'], $course_details['id'], 4)->result_array();
if (function_exists('youngo_frontend_translate_course_rows')) {
    $related_courses = youngo_frontend_translate_course_rows($related_courses, $youngo_frontend_language);
}
$share_url = function_exists('youngo_frontend_course_detail_url') ? youngo_frontend_course_detail_url($course_details, $youngo_frontend_language) : site_url('home/course/' . slugify($course_details['title']) . '/' . $course_details['id']);
$updated_at = $course_details['last_modified'] > 0 ? $course_details['last_modified'] : $course_details['date_added'];
?>

<section class="youngo-course-hero">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo youngo_course_detail_phrase('breadcrumb', '', $youngo_frontend_language); ?>">
            <a href="<?php echo $youngo_course_home_url; ?>"><?php echo youngo_course_detail_phrase('home', '', $youngo_frontend_language); ?></a>
            <span>/</span>
            <a href="<?php echo $youngo_course_courses_url; ?>"><?php echo youngo_course_detail_phrase('courses', '', $youngo_frontend_language); ?></a>
            <span>/</span>
            <span><?php echo youngo_course_detail_phrase('details', '', $youngo_frontend_language); ?></span>
        </nav>

        <div class="youngo-course-hero__grid">
            <div class="youngo-course-hero__content">
                <p class="youngo-eyebrow"><?php echo youngo_course_detail_phrase('guided_learning_for_curious_kids', '', $youngo_frontend_language); ?></p>
                <h1><?php echo $course_details['title']; ?></h1>
                <p class="youngo-course-hero__lead"><?php echo $course_details['short_description']; ?></p>

                <div class="youngo-course-meta">
                    <?php if (!empty($instructor_details)): ?>
                        <a class="youngo-course-instructor-chip" href="<?php echo site_url('home/instructor_page/' . $course_details['creator']); ?>">
                            <img loading="lazy" src="<?php echo $this->user_model->get_user_image_url($instructor_details['id']); ?>" alt="<?php echo htmlspecialchars($instructor_details['first_name'] . ' ' . $instructor_details['last_name']); ?>">
                            <span><?php echo youngo_course_detail_phrase('created_by', '', $youngo_frontend_language); ?> <strong><?php echo $instructor_details['first_name'] . ' ' . $instructor_details['last_name']; ?></strong></span>
                        </a>
                    <?php endif; ?>
                    <span><i class="fa-solid fa-star"></i> <?php echo $average_rating; ?> (<?php echo $number_of_ratings . ' ' . youngo_course_detail_phrase('reviews', '', $youngo_frontend_language); ?>)</span>
                    <?php if ($course_duration_label): ?>
                        <span><i class="fa-regular fa-clock"></i> <?php echo $course_duration_label; ?></span>
                    <?php endif; ?>
                    <span><i class="fa-solid fa-user-group"></i> <?php echo $number_of_enrolments . ' ' . youngo_course_detail_phrase('enrolled', '', $youngo_frontend_language); ?></span>
                </div>

                <div class="youngo-course-chips">
                    <?php if (!empty($course_details['level'])): ?><span><?php echo youngo_course_detail_phrase(strtolower(trim($course_details['level'])), $course_details['level'], $youngo_frontend_language); ?></span><?php endif; ?>
                    <?php if (!empty($course_details['language'])): ?><span><?php echo youngo_course_detail_phrase(strtolower(trim($course_details['language'])), $course_details['language'], $youngo_frontend_language); ?></span><?php endif; ?>
                    <span><?php echo youngo_course_detail_phrase('updated', '', $youngo_frontend_language); ?> <?php echo youngo_course_date_label($updated_at, $youngo_frontend_language); ?></span>
                    <?php if ($course_details['status'] == 'upcoming' && !empty($course_details['publish_date'])): ?>
                        <span><?php echo youngo_course_detail_phrase('starts', '', $youngo_frontend_language); ?> <?php echo youngo_course_date_label(strtotime($course_details['publish_date']), $youngo_frontend_language); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <aside class="youngo-course-card" aria-label="<?php echo youngo_course_detail_phrase('course_actions', '', $youngo_frontend_language); ?>">
                <div class="youngo-course-media">
                    <img loading="lazy" src="<?php echo $this->crud_model->get_course_thumbnail_url($course_details['id']); ?>" alt="<?php echo htmlspecialchars($course_details['title']); ?>">
                    <button type="button" onclick="lesson_preview('<?php echo site_url('home/course_preview/' . $course_details['id']); ?>', '<?php echo htmlspecialchars($course_details['title'], ENT_QUOTES); ?>')" aria-label="<?php echo youngo_course_detail_phrase('preview_this_course', '', $youngo_frontend_language); ?>">
                        <i class="fa-solid fa-play"></i>
                    </button>
                    <span class="youngo-wishlist-toggle <?php if (in_array($course_details['id'], $my_wishlist_items)) echo 'red-heart'; ?>" id="coursesWishlistIcon<?php echo $course_details['id']; ?>">
                        <i class="fa-solid fa-heart checkPropagation" onclick="actionTo('<?php echo site_url('home/toggleWishlistItems/' . $course_details['id']); ?>')"></i>
                    </span>
                </div>

                <div class="youngo-course-price">
                    <span class="youngo-course-price__label"><?php echo youngo_course_detail_phrase('course_access', '', $youngo_frontend_language); ?></span>
                    <div class="youngo-course-price__value">
                    <?php if ($youngo_is_subscription_only && empty($course_details['is_free_course'])): ?>
                        <strong><?php echo youngo_course_detail_phrase('subscription_access', '', $youngo_frontend_language); ?></strong>
                    <?php elseif ($course_details['is_free_course']): ?>
                        <strong><?php echo youngo_course_detail_phrase('free', '', $youngo_frontend_language); ?></strong>
                    <?php elseif ($course_details['discount_flag']): ?>
                        <strong><?php echo currency($course_details['discounted_price']); ?></strong>
                        <del><?php echo currency($course_details['price']); ?></del>
                    <?php else: ?>
                        <strong><?php echo currency($course_details['price']); ?></strong>
                    <?php endif; ?>
                    </div>
                </div>

                <?php if ($youngo_entitlement_available && $youngo_status_message !== ''): ?>
                    <div class="youngo-course-access-status <?php echo $youngo_status_class; ?>">
                        <span class="youngo-course-access-status__badge"><i class="fa-solid <?php echo $youngo_status_icon; ?>"></i></span>
                        <div>
                            <strong><?php echo youngo_course_detail_phrase($youngo_status_label, '', $youngo_frontend_language); ?></strong>
                            <p><?php echo youngo_course_detail_phrase($youngo_status_message, '', $youngo_frontend_language); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="youngo-course-actions">
                    <?php if (($legacy_is_purchased || $youngo_has_access) && $youngo_lecture_count > 0): ?>
                        <a class="youngo-button" href="<?php echo site_url('home/lesson/' . slugify($youngo_course_player_title) . '/' . $course_details['id']); ?>"><i class="far fa-play-circle"></i> <?php echo youngo_course_detail_phrase('start_now', '', $youngo_frontend_language); ?></a>
                    <?php elseif ($legacy_is_purchased || $youngo_has_access): ?>
                        <span class="youngo-button youngo-button--secondary" aria-disabled="true"><i class="fa-solid fa-hourglass-half"></i> <?php echo youngo_course_detail_phrase('course_content_not_available_yet', 'Course content is not available yet', $youngo_frontend_language); ?></span>
                    <?php else: ?>
                        <?php if ($youngo_is_managed_access): ?>
                            <a class="youngo-button youngo-button--subscription" href="<?php echo $youngo_subscription_url; ?>"><i class="fa-solid fa-crown"></i> <?php echo youngo_course_detail_phrase('view_subscription_plans', '', $youngo_frontend_language); ?></a>
                            <p class="youngo-course-actions__subscription-copy"><i class="fa-solid fa-sparkles"></i> <?php echo youngo_course_detail_phrase($youngo_managed_access_message, '', $youngo_frontend_language); ?></p>
                        <?php elseif (!empty($youngo_checkout_cta_decision['show_cta']) && !empty($youngo_checkout_cta_decision['target_url'])): ?>
                            <a class="youngo-button" href="<?php echo htmlspecialchars($youngo_checkout_cta_decision['target_url'], ENT_QUOTES, 'UTF-8'); ?>" data-youngo-checkout-cta="local-course-detail"><i class="fa-regular fa-credit-card"></i> <?php echo htmlspecialchars($youngo_checkout_cta_decision['label_text'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php elseif (isset($youngo_checkout_cta_decision['state']) && $youngo_checkout_cta_decision['state'] === 'login_required' && !empty($youngo_checkout_cta_decision['login_url'])): ?>
                            <a class="youngo-button youngo-button--secondary" href="<?php echo htmlspecialchars($youngo_checkout_cta_decision['login_url'], ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-user-lock"></i> <?php echo htmlspecialchars($youngo_checkout_cta_decision['label_text'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php elseif ($course_details['is_free_course'] == 1): ?>
                            <a class="youngo-button" href="<?php echo site_url('home/get_enrolled_to_free_course/' . $course_details['id']); ?>"><?php echo youngo_course_detail_phrase('enroll_now', '', $youngo_frontend_language); ?></a>
                        <?php elseif ($youngo_checkout_not_ready): ?>
                            <span class="youngo-button youngo-button--secondary" aria-disabled="true"><i class="fa-solid fa-calendar-check"></i> <?php echo youngo_course_detail_phrase('subscription_checkout_is_not_available_yet', '', $youngo_frontend_language); ?></span>
                        <?php else: ?>
                            <a class="youngo-button" href="<?php echo $youngo_course_contact_url; ?>"><i class="fa-regular fa-envelope"></i> <?php echo youngo_course_detail_phrase('contact', '', $youngo_frontend_language); ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <p class="youngo-course-actions__note"><?php echo youngo_course_detail_phrase('join_with_your_account_and_keep_course_access_in_one_place.', '', $youngo_frontend_language); ?></p>

                <div class="youngo-course-facts">
                    <div><span><?php echo youngo_course_detail_phrase('lectures', '', $youngo_frontend_language); ?></span><strong><?php echo $youngo_lecture_count; ?></strong></div>
                    <?php $number_of_quiz = $this->db->get_where('lesson', array('course_id' => $course_details['id'], 'lesson_type' => 'quiz'))->num_rows(); ?>
                    <?php if ($number_of_quiz > 0): ?><div><span><?php echo youngo_frontend_text('Quizzes', $youngo_frontend_language); ?></span><strong><?php echo $number_of_quiz; ?></strong></div><?php endif; ?>
                    <div><span><?php echo youngo_course_detail_phrase('expiry_period', '', $youngo_frontend_language); ?></span><strong><?php echo $course_details['expiry_period'] <= 0 ? youngo_course_detail_phrase('lifetime', '', $youngo_frontend_language) : $course_details['expiry_period'] . ' ' . youngo_course_detail_phrase('months', '', $youngo_frontend_language); ?></strong></div>
                    <?php if (addon_status('certificate')): ?><div><span><?php echo youngo_course_detail_phrase('certificate', '', $youngo_frontend_language); ?></span><strong><?php echo youngo_course_detail_phrase('yes', '', $youngo_frontend_language); ?></strong></div><?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</section>

<section class="youngo-course-body">
    <div class="youngo-container youngo-course-body__grid">
        <div class="youngo-course-content">
            <section class="youngo-course-section">
                <div class="youngo-course-section__heading">
                    <p class="youngo-eyebrow"><?php echo youngo_course_detail_phrase('overview', '', $youngo_frontend_language); ?></p>
                    <h2><?php echo youngo_course_detail_phrase('course_description', '', $youngo_frontend_language); ?></h2>
                </div>
                <div class="youngo-rich-text"><?php echo $course_details['description']; ?></div>
            </section>

            <?php if (count(array_filter($outcomes)) > 0): ?>
                <section class="youngo-course-section">
                    <div class="youngo-course-section__heading">
                        <p class="youngo-eyebrow"><?php echo youngo_course_detail_phrase('learning_goals', '', $youngo_frontend_language); ?></p>
                        <h2><?php echo youngo_course_detail_phrase('what_will_i_learn?', '', $youngo_frontend_language); ?></h2>
                    </div>
                    <div class="youngo-check-grid">
                        <?php foreach ($outcomes as $outcome): ?>
                            <?php if ($outcome != ""): ?><div><i class="fa-solid fa-check"></i><span><?php echo $outcome; ?></span></div><?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (count(array_filter($requirements)) > 0): ?>
                <section class="youngo-course-section">
                    <div class="youngo-course-section__heading">
                        <p class="youngo-eyebrow"><?php echo youngo_course_detail_phrase('before_class', '', $youngo_frontend_language); ?></p>
                        <h2><?php echo youngo_course_detail_phrase('requirements', '', $youngo_frontend_language); ?></h2>
                    </div>
                    <div class="youngo-check-grid is-soft">
                        <?php foreach ($requirements as $requirement): ?>
                            <?php if ($requirement != ""): ?><div><i class="fa-solid fa-circle-check"></i><span><?php echo $requirement; ?></span></div><?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="youngo-course-section" id="curriculum">
                <div class="youngo-course-section__heading">
                    <p class="youngo-eyebrow"><?php echo youngo_course_detail_phrase('curriculum', '', $youngo_frontend_language); ?></p>
                    <h2><?php echo youngo_course_detail_phrase('lessons_inside_this_course', '', $youngo_frontend_language); ?></h2>
                </div>
                <div class="youngo-curriculum">
                    <?php foreach ($sections as $key => $section): ?>
                        <?php
                        $section_lessons = $this->crud_model->get_lessons('section', $section['id'])->result_array();
                        if (function_exists('youngo_frontend_translate_lesson_rows')) {
                            $section_lessons = youngo_frontend_translate_lesson_rows($section_lessons, $youngo_frontend_language);
                        }
                        $section_duration_label = youngo_course_duration_label($this->crud_model->get_total_duration_of_lesson_by_section_id($section['id']), $youngo_frontend_language);
                        ?>
                        <details class="youngo-curriculum-section" <?php if ($key == 0) echo 'open'; ?>>
                            <summary>
                                <span class="youngo-curriculum-section__title"><?php echo $section['title']; ?></span>
                                <small class="youngo-curriculum-section__meta">
                                    <span><?php echo count($section_lessons) . ' ' . youngo_course_detail_phrase('lessons', '', $youngo_frontend_language); ?></span>
                                    <?php if ($section_duration_label): ?><span><?php echo $section_duration_label; ?></span><?php endif; ?>
                                </small>
                                <?php if (false): ?>
                                <small><?php echo count($section_lessons) . ' ' . youngo_course_detail_phrase('lessons', '', $youngo_frontend_language); ?> · <?php echo $section_duration_label; ?></small>
                                <?php endif; ?>
                            </summary>
                            <ul>
                                <?php foreach ($section_lessons as $lesson): ?>
                                    <?php $lesson_duration_label = youngo_course_duration_label($lesson['duration'], $youngo_frontend_language); ?>
                                    <li>
                                        <a href="#" onclick="actionTo('<?php echo site_url('home/play_lesson/' . $lesson['id']); ?>'); return false;">
                                            <span class="youngo-lesson-title"><i class="fa-regular fa-circle-play"></i> <span><?php echo $lesson['title']; ?></span></span>
                                            <?php if ($lesson_duration_label): ?><small class="youngo-duration-pill"><?php echo $lesson_duration_label; ?></small><?php endif; ?>
                                        </a>
                                        <?php if ($lesson['is_free']): ?>
                                            <button class="youngo-preview-pill" type="button" onclick="lesson_preview('<?php echo site_url('home/play_lesson/' . $lesson['id'] . '/preview'); ?>', '<?php echo htmlspecialchars($lesson['title'], ENT_QUOTES); ?>')"><i class="fa-regular fa-eye"></i> <?php echo youngo_course_detail_phrase('preview', '', $youngo_frontend_language); ?></button>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php endforeach; ?>
                    <?php if (count($sections) == 0): ?>
                        <p class="youngo-empty-note"><?php echo youngo_course_detail_phrase('no_curriculum_sections_are_available_yet.', '', $youngo_frontend_language); ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="youngo-course-section youngo-course-instructor-section" id="instructor" <?php echo $youngo_frontend_language === 'arabic' ? 'dir="rtl" lang="ar"' : 'lang="en"'; ?>>
                <div class="youngo-course-section__heading">
                    <p class="youngo-eyebrow"><?php echo youngo_course_detail_phrase('instructor', '', $youngo_frontend_language); ?></p>
                    <h2><?php echo youngo_course_detail_phrase('meet_your_guide', '', $youngo_frontend_language); ?></h2>
                </div>
                <div class="youngo-instructor-list">
                    <?php $multi_instructor_id_arr = explode(',', $course_details['user_id']); ?>
                    <?php foreach ($multi_instructor_id_arr as $instructor_id): ?>
                        <?php if ((int) $instructor_id > 0): ?>
                            <?php
                            $instructor = $this->user_model->get_all_user($instructor_id)->row_array();
                            $is_following = $this->user_model->is_following($instructor_id, $this->session->userdata('user_id'));
                            ?>
                            <article class="youngo-instructor-card">
                                <div class="youngo-instructor-avatar">
                                    <img loading="lazy" src="<?php echo $this->user_model->get_user_image_url($instructor['id']); ?>" alt="<?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?>">
                                </div>
                                <div class="youngo-instructor-card__body">
                                    <div class="youngo-instructor-card__top">
                                        <div>
                                            <h3><?php echo $instructor['first_name'] . ' ' . $instructor['last_name']; ?></h3>
                                            <?php if (!empty($instructor['title'])): ?><p class="youngo-instructor-role"><?php echo $instructor['title']; ?></p><?php endif; ?>
                                        </div>
                                        <span class="youngo-instructor-badge"><?php echo youngo_course_detail_phrase('course_guide', '', $youngo_frontend_language); ?></span>
                                    </div>
                                    <?php if (!empty($instructor['biography'])): ?><div class="youngo-rich-text youngo-instructor-bio"><?php echo strip_tags($instructor['biography']); ?></div><?php endif; ?>
                                    <div class="youngo-instructor-trust">
                                        <span><i class="fa-solid fa-shield-halved"></i> <?php echo youngo_course_detail_phrase('trusted_guide', '', $youngo_frontend_language); ?></span>
                                        <span><i class="fa-solid fa-list-check"></i> <?php echo youngo_course_detail_phrase('structured_lessons', '', $youngo_frontend_language); ?></span>
                                    </div>
                                    <div class="youngo-instructor-card__actions">
                                        <a class="youngo-button youngo-button--small youngo-button--secondary" href="<?php echo site_url('home/instructor_page/' . $instructor_id); ?>" target="_blank"><?php echo youngo_course_detail_phrase('view_profile', '', $youngo_frontend_language); ?></a>
                                        <?php if ($this->session->userdata('role') != 1 && (int) $this->session->userdata('user_id') != (int) $instructor_id): ?>
                                            <a id="follow-btn-<?php echo $instructor['id']; ?>" href="javascript:;" onclick="toggleFollow(<?php echo $instructor['id']; ?>, this)">
                                                <span class="youngo-button youngo-button--small <?php echo $is_following ? 'youngo-button--secondary' : ''; ?>"><?php echo $is_following ? youngo_course_detail_phrase('unfollow', '', $youngo_frontend_language) : youngo_course_detail_phrase('follow', '', $youngo_frontend_language); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="youngo-course-section" id="reviews">
                <div class="youngo-course-section__heading">
                    <p class="youngo-eyebrow"><?php echo youngo_course_detail_phrase('reviews', '', $youngo_frontend_language); ?></p>
                    <h2><?php echo youngo_course_detail_phrase('family_and_learner_feedback', '', $youngo_frontend_language); ?></h2>
                </div>
                <div class="reviews">
                    <?php include "course_page_reviews.php"; ?>
                </div>
            </section>

            <?php if (count($faqs) > 0): ?>
                <section class="youngo-course-section">
                    <div class="youngo-course-section__heading">
                        <p class="youngo-eyebrow"><?php echo youngo_course_detail_phrase('questions', '', $youngo_frontend_language); ?></p>
                        <h2><?php echo youngo_course_detail_phrase('frequently_asked_questions', '', $youngo_frontend_language); ?></h2>
                    </div>
                    <div class="youngo-course-faqs">
                        <?php foreach ($faqs as $faq_question => $faq): ?>
                            <details>
                                <summary><?php echo $faq_question; ?></summary>
                                <div><?php echo $faq; ?></div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php
            $custom_fields = $this->db->where('course_id', $course_id)->order_by('sorting', 'ASC')->get('custom_fields')->result_array();
            if (count($custom_fields) > 0):
            ?>
                <section class="youngo-course-section">
                    <div class="youngo-course-section__heading">
                        <p class="youngo-eyebrow"><?php echo youngo_course_detail_phrase('more_details', '', $youngo_frontend_language); ?></p>
                        <h2><?php echo youngo_course_detail_phrase('additional_information', '', $youngo_frontend_language); ?></h2>
                    </div>
                    <div class="youngo-custom-fields">
                        <?php foreach ($custom_fields as $field): ?>
                            <?php
                            $title = htmlspecialchars($field['custom_title']);
                            $items_data = json_decode($field['custom_field'], true);
                            $items = isset($items_data['data']) && is_array($items_data['data']) ? $items_data['data'] : array();
                            ?>
                            <article>
                                <h3><?php echo $title; ?></h3>
                                <?php if ($field['custom_type'] == 'text' || $field['custom_type'] == 'faq'): ?>
                                    <?php foreach ($items as $item): ?>
                                        <?php if (!empty($item['title'])): ?><h4><?php echo $item['title']; ?></h4><?php endif; ?>
                                        <?php if (!empty($item['description'])): ?><div class="youngo-rich-text"><?php echo $item['description']; ?></div><?php endif; ?>
                                    <?php endforeach; ?>
                                <?php elseif ($field['custom_type'] == 'image' || $field['custom_type'] == 'gallery'): ?>
                                    <div class="youngo-custom-gallery">
                                        <?php foreach ($items as $item): ?>
                                            <?php if (!empty($item['file'])): ?>
                                                <a href="<?php echo base_url('uploads/custom_fields/' . $item['file']); ?>" target="_blank">
                                                    <img loading="lazy" src="<?php echo base_url('uploads/custom_fields/' . $item['file']); ?>" alt="<?php echo !empty($item['title']) ? htmlspecialchars($item['title']) : $title; ?>">
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($field['custom_type'] == 'video'): ?>
                                    <?php foreach ($items as $item): ?>
                                        <?php if (!empty($item['file'])): ?><a class="youngo-text-link" href="<?php echo $item['file']; ?>" target="_blank"><?php echo !empty($item['title']) ? $item['title'] : youngo_course_detail_phrase('watch_video', '', $youngo_frontend_language); ?></a><?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <aside class="youngo-course-side">
            <div class="youngo-side-card">
                <h3><?php echo youngo_course_detail_phrase('course_confidence', '', $youngo_frontend_language); ?></h3>
                <p><?php echo youngo_course_detail_phrase('a_structured_learning_path_with_clear_lessons,_instructor_guidance,_and_progress-friendly_activities.', '', $youngo_frontend_language); ?></p>
                <div class="youngo-share-row">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" aria-label="<?php echo youngo_course_detail_phrase('share_on_facebook', '', $youngo_frontend_language); ?>"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo urlencode($course_details['title']); ?>" target="_blank" aria-label="<?php echo youngo_course_detail_phrase('share_on_twitter', '', $youngo_frontend_language); ?>"><i class="fa-brands fa-twitter"></i></a>
                    <a href="https://api.whatsapp.com/send?text=<?php echo $share_url; ?>" target="_blank" aria-label="<?php echo youngo_course_detail_phrase('share_on_whatsapp', '', $youngo_frontend_language); ?>"><i class="fa-brands fa-whatsapp"></i></a>
                    <a href="https://www.linkedin.com/shareArticle?url=<?php echo $share_url; ?>&title=<?php echo urlencode($course_details['title']); ?>" target="_blank" aria-label="<?php echo youngo_course_detail_phrase('share_on_linkedin', '', $youngo_frontend_language); ?>"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </div>
        </aside>
    </div>
</section>

<?php if (count($related_courses) > 0): ?>
    <section class="youngo-related-courses">
        <div class="youngo-container">
            <div class="youngo-section__heading">
                <p class="youngo-eyebrow"><?php echo youngo_course_detail_phrase('keep_exploring', '', $youngo_frontend_language); ?></p>
                <h2><?php echo youngo_course_detail_phrase('related_courses', '', $youngo_frontend_language); ?></h2>
            </div>
            <div class="youngo-related-grid">
                <?php foreach ($related_courses as $course): ?>
                    <?php
                    $related_duration = $this->crud_model->get_total_duration_of_lesson_by_course_id($course['id']);
                    $related_duration_label = youngo_course_duration_label($related_duration, $youngo_frontend_language);
                    $related_rating_total = $this->crud_model->get_ratings('course', $course['id'], true)->row()->rating;
                    $related_rating_count = $this->crud_model->get_ratings('course', $course['id'])->num_rows();
                    $related_rating = $related_rating_count > 0 ? round($related_rating_total / $related_rating_count, 1) : 0;
                    $related_is_subscription_only = isset($course['youngo_access_mode']) && $course['youngo_access_mode'] === 'subscription_only';
                    ?>
                    <a class="youngo-related-card" href="<?php echo function_exists('youngo_frontend_course_detail_url') ? youngo_frontend_course_detail_url($course, $youngo_frontend_language) : site_url('home/course/' . rawurlencode(slugify($course['title'])) . '/' . $course['id']); ?>">
                        <div class="youngo-related-card__media">
                            <img loading="lazy" src="<?php echo $this->crud_model->get_course_thumbnail_url($course['id']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                            <span class="youngo-wishlist-toggle <?php if (in_array($course['id'], $my_wishlist_items)) echo 'red-heart'; ?>" id="coursesWishlistIcon<?php echo $course['id']; ?>">
                                <i class="fa-solid fa-heart checkPropagation" onclick="actionTo('<?php echo site_url('home/toggleWishlistItems/' . $course['id']); ?>')"></i>
                            </span>
                        </div>
                        <div class="youngo-related-card__body">
                        <h3><?php echo $course['title']; ?></h3>
                        <p class="youngo-related-card__meta">
                            <span><i class="fa-solid fa-star"></i> <?php echo $related_rating; ?></span>
                            <?php if ($related_duration_label): ?><span><i class="fa-regular fa-clock"></i> <?php echo $related_duration_label; ?></span><?php endif; ?>
                        </p>
                        <?php if (false): ?>
                        <p><i class="fa-solid fa-star"></i> <?php echo $related_rating; ?> · <?php echo $related_duration_label; ?></p>
                        <?php endif; ?>
                        <strong class="youngo-related-card__price">
                            <?php if ($related_is_subscription_only && empty($course['is_free_course'])): ?>
                                <?php echo youngo_course_detail_phrase('subscription_access', '', $youngo_frontend_language); ?>
                            <?php elseif ($course['is_free_course']): ?>
                                <?php echo youngo_course_detail_phrase('free', '', $youngo_frontend_language); ?>
                            <?php elseif ($course['discount_flag']): ?>
                                <?php echo currency($course['discounted_price']); ?>
                            <?php else: ?>
                                <?php echo currency($course['price']); ?>
                            <?php endif; ?>
                        </strong>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<div class="youngo-modal" id="lesson_preview" aria-hidden="true">
    <div class="youngo-modal__dialog">
        <div class="youngo-modal__header">
            <h3 class="title"></h3>
            <button type="button" data-youngo-modal-close aria-label="<?php echo youngo_course_detail_phrase('close', '', $youngo_frontend_language); ?>">&times;</button>
        </div>
        <div class="youngo-modal__body"></div>
    </div>
</div>

<script>
function toggleFollow(instructor_id, element) {
    $.ajax({
        url: "<?php echo site_url('home/toggle_following'); ?>",
        type: 'POST',
        dataType: 'json',
        data: {
            instructor_id: instructor_id,
            user_id: <?php echo (int) $this->session->userdata('user_id'); ?>
        },
        success: function(response) {
            var btn = $(element).find('span');
            if (response.status === 'followed') {
                btn.text('<?php echo youngo_course_detail_phrase('unfollow', '', $youngo_frontend_language); ?>').addClass('youngo-button--secondary');
            } else if (response.status === 'unfollowed') {
                btn.text('<?php echo youngo_course_detail_phrase('follow', '', $youngo_frontend_language); ?>').removeClass('youngo-button--secondary');
            }
        }
    });
}
</script>
