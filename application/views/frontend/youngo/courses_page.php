<?php
$selected_category = isset($_GET['category']) && $_GET['category'] !== '' ? $_GET['category'] : 'all';
$selected_price = isset($_GET['price']) && $_GET['price'] !== '' ? $_GET['price'] : 'all';
$selected_level = isset($_GET['level']) && $_GET['level'] !== '' ? $_GET['level'] : 'all';
$selected_language = isset($_GET['language']) && $_GET['language'] !== '' ? $_GET['language'] : 'all';
$selected_rating = isset($_GET['rating']) && $_GET['rating'] !== '' ? $_GET['rating'] : 'all';
$selected_sorting = isset($_GET['sort_by']) && $_GET['sort_by'] !== '' ? $_GET['sort_by'] : 'newest';
$search_value = isset($_GET['title']) && $_GET['title'] !== '' ? $_GET['title'] : (isset($_GET['query']) ? $_GET['query'] : '');
$layout = isset($layout) && $layout === 'list' ? 'list' : 'grid';
$courses = isset($courses) && is_array($courses) ? $courses : array();
$total_result = isset($total_result) ? (int) $total_result : count($courses);
$youngo_frontend_language = isset($youngo_frontend_language) ? $youngo_frontend_language : (function_exists('youngo_frontend_content_language') ? youngo_frontend_content_language() : 'english');
$youngo_courses_home_url = function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_frontend_language) : site_url('home');
$youngo_courses_index_url = function_exists('youngo_frontend_courses_url') ? youngo_frontend_courses_url($youngo_frontend_language) : site_url('home/courses');
$my_wishlist_items = array();

if ($user_id = $this->session->userdata('user_id')) {
    $wishlist = $this->user_model->get_all_user($user_id)->row('wishlist');
    if ($wishlist != '') {
        $my_wishlist_items = json_decode($wishlist, true);
        $my_wishlist_items = is_array($my_wishlist_items) ? $my_wishlist_items : array();
    }
}

if (!function_exists('youngo_courses_e')) {
    function youngo_courses_e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('youngo_courses_short_text')) {
    function youngo_courses_short_text($value, $length = 130) {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)));
        if (strlen($text) <= $length) {
            return $text;
        }

        return rtrim(substr($text, 0, $length - 1)) . '...';
    }
}

if (!function_exists('youngo_courses_duration_label')) {
    function youngo_courses_duration_label($duration) {
        $original_duration = trim((string) $duration);
        if ($original_duration === '') {
            return '';
        }

        $normalized_duration = preg_replace('/\s*hours?$/i', '', $original_duration);
        $normalized_duration = preg_replace('/\.\d+/', '', trim($normalized_duration));

        if (!preg_match('/^\d{1,3}:\d{1,2}(:\d{1,2})?$/', $normalized_duration)) {
            if (preg_match('/^0\s+hours?$/i', $original_duration)) {
                return '';
            }

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
            $labels[] = $hours . 'h';
        }
        if ($minutes > 0) {
            $labels[] = $minutes . 'm';
        }

        return empty($labels) ? '' : implode(' ', $labels);
    }
}

?>

<section class="youngo-courses-hero">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo youngo_frontend_phrase('Breadcrumb'); ?>">
            <a href="<?php echo $youngo_courses_home_url; ?>"><?php echo youngo_frontend_phrase('Home'); ?></a>
            <span>/</span>
            <span><?php echo youngo_frontend_phrase('Courses'); ?></span>
        </nav>

        <div class="youngo-courses-hero__grid">
            <div>
                <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('course_discovery'); ?></p>
                <h1><?php echo youngo_frontend_phrase('explore_youngo_courses'); ?></h1>
                <p><?php echo youngo_frontend_phrase('find_structured,_friendly_learning_paths_for_curious_kids_and_the_families_supporting_them.'); ?></p>
            </div>
            <div class="youngo-courses-hero__stats" aria-label="<?php echo youngo_frontend_phrase('course_results'); ?>">
                <strong><?php echo $total_result; ?></strong>
                <span><?php echo $total_result === 1 ? youngo_frontend_phrase('course_available') : youngo_frontend_phrase('courses_available'); ?></span>
            </div>
        </div>
    </div>
</section>

<section class="youngo-courses-page">
    <div class="youngo-container">
        <form action="<?php echo $youngo_courses_index_url; ?>" method="get" id="course_filter_form" class="youngo-courses-layout">
            <?php include __DIR__ . '/course_listing/filter_panel.php'; ?>

            <div class="youngo-courses-results">
                <?php include __DIR__ . '/course_listing/sorting_bar.php'; ?>

                <?php if (count($courses) > 0): ?>
                    <div class="youngo-courses-grid <?php echo $layout === 'list' ? 'is-list' : ''; ?>">
                        <?php foreach ($courses as $course): ?>
                            <?php include __DIR__ . '/course_listing/course_card.php'; ?>
                        <?php endforeach; ?>
                    </div>

                    <?php $pagination_links = $this->pagination->create_links(); ?>
                    <?php if ($pagination_links !== ''): ?>
                        <nav class="youngo-pagination" aria-label="<?php echo youngo_frontend_phrase('course_pagination'); ?>">
                            <?php echo $pagination_links; ?>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="youngo-courses-empty">
                        <div class="youngo-courses-empty__icon"><i class="fa-regular fa-compass"></i></div>
                        <h2><?php echo youngo_frontend_phrase('course_not_found'); ?></h2>
                        <p><?php echo youngo_frontend_phrase('try_adjusting_your_search_or_clearing_a_few_filters_to_see_more_courses.'); ?></p>
                        <a class="youngo-button" href="<?php echo $youngo_courses_index_url; ?>"><?php echo youngo_frontend_phrase('reset_filters'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>
