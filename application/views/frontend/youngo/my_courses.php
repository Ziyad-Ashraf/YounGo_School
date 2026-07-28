<?php
$user_id = (int) $this->session->userdata('user_id');
$user_details = isset($user_details) && is_array($user_details) ? $user_details : array();
$courses = isset($learner_course_access_items) && is_array($learner_course_access_items) ? $learner_course_access_items : array();
$access_counts = isset($learner_access_counts) && is_array($learner_access_counts) ? $learner_access_counts : array();
$subscription_summary = isset($learner_subscription_summary) && is_array($learner_subscription_summary) ? $learner_subscription_summary : array();
$youngo_my_courses_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_my_courses_home_url = function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_my_courses_language) : site_url('home');
$youngo_my_courses_courses_url = function_exists('youngo_frontend_courses_url') ? youngo_frontend_courses_url($youngo_my_courses_language) : site_url('home/courses');
$youngo_my_courses_access_url = function_exists('youngo_frontend_my_access_url') ? youngo_frontend_my_access_url($youngo_my_courses_language) : site_url('home/my_access');

if (!function_exists('youngo_my_courses_e')) {
    function youngo_my_courses_e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('youngo_my_courses_date')) {
    function youngo_my_courses_date($timestamp) {
        $timestamp = (int) $timestamp;
        return $timestamp > 0 ? date('M d, Y', $timestamp) : '';
    }
}

$completed_courses = 0;
$in_progress_courses = 0;
foreach ($courses as $course) {
    $progress = isset($course['progress']) ? (int) $course['progress'] : 0;
    if ($progress >= 100) {
        $completed_courses++;
    } elseif ($progress > 0) {
        $in_progress_courses++;
    }
}

$student_name = trim((isset($user_details['first_name']) ? $user_details['first_name'] : '') . ' ' . (isset($user_details['last_name']) ? $user_details['last_name'] : ''));
if ($student_name === '') {
    $student_name = youngo_frontend_phrase('YounGo learner');
}
$student_email = isset($user_details['email']) ? $user_details['email'] : '';
$active_subscription_count = isset($subscription_summary['active_count']) ? (int) $subscription_summary['active_count'] : 0;
?>

<section class="youngo-my-courses-hero">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo youngo_frontend_phrase('Breadcrumb'); ?>">
            <a href="<?php echo $youngo_my_courses_home_url; ?>"><?php echo youngo_frontend_phrase('Home'); ?></a>
            <span>/</span>
            <span><?php echo youngo_frontend_phrase('My Courses'); ?></span>
        </nav>

        <div class="youngo-my-courses-hero__grid">
            <div>
                <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('Learner space'); ?></p>
                <h1><?php echo youngo_frontend_phrase('My Courses'); ?></h1>
                <p><?php echo youngo_frontend_phrase('Continue courses from enrolments, school-granted access, and YounGo course access in one place.'); ?></p>
            </div>

            <aside class="youngo-student-card" aria-label="<?php echo youngo_frontend_phrase('Student profile'); ?>">
                <img loading="lazy" src="<?php echo $this->user_model->get_user_image_url($user_id); ?>" alt="<?php echo youngo_my_courses_e($student_name); ?>">
                <div>
                    <span><?php echo youngo_frontend_phrase('Signed in as'); ?></span>
                    <strong><?php echo youngo_my_courses_e($student_name); ?></strong>
                    <?php if ($student_email !== ''): ?><small><?php echo youngo_my_courses_e($student_email); ?></small><?php endif; ?>
                </div>
            </aside>
        </div>

        <div class="youngo-learning-stats" aria-label="<?php echo youngo_frontend_phrase('Learning summary'); ?>">
            <div>
                <span><?php echo youngo_frontend_phrase('Active courses'); ?></span>
                <strong><?php echo count($courses); ?></strong>
            </div>
            <div>
                <span><?php echo youngo_frontend_phrase('In progress'); ?></span>
                <strong><?php echo $in_progress_courses; ?></strong>
            </div>
            <div>
                <span><?php echo youngo_frontend_phrase('Completed'); ?></span>
                <strong><?php echo $completed_courses; ?></strong>
            </div>
            <div>
                <span><?php echo youngo_frontend_phrase('Subscriptions'); ?></span>
                <strong><?php echo $active_subscription_count; ?></strong>
            </div>
        </div>
    </div>
</section>

<section class="youngo-my-courses-page">
    <div class="youngo-container">
        <?php if ($active_subscription_count > 0): ?>
            <div class="youngo-my-courses-toolbar">
                <div>
                    <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('Subscription active'); ?></p>
                    <h2><?php echo youngo_frontend_phrase('Your subscription access is active'); ?></h2>
                </div>
                <a class="youngo-button youngo-button--secondary" href="<?php echo $youngo_my_courses_access_url; ?>"><?php echo youngo_frontend_phrase('View My Access'); ?></a>
            </div>
        <?php endif; ?>

        <?php if (count($courses) > 0): ?>
            <div class="youngo-my-courses-toolbar">
                <div>
                    <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('Continue learning'); ?></p>
                    <h2><?php echo youngo_frontend_phrase('Your active learning access'); ?></h2>
                </div>
                <div class="youngo-learning-actions">
                    <a class="youngo-button youngo-button--secondary" href="<?php echo $youngo_my_courses_access_url; ?>"><?php echo youngo_frontend_phrase('My Access'); ?></a>
                    <a class="youngo-button youngo-button--secondary" href="<?php echo $youngo_my_courses_courses_url; ?>"><?php echo youngo_frontend_phrase('Explore more courses'); ?></a>
                </div>
            </div>

            <div class="youngo-learning-list">
                <?php foreach ($courses as $course): ?>
                    <?php
                    $course_id = isset($course['course_id']) ? (int) $course['course_id'] : (isset($course['id']) ? (int) $course['id'] : 0);
                    $title = isset($course['title']) ? $course['title'] : '';
                    $progress = isset($course['progress']) ? (int) $course['progress'] : 0;
                    $expiry_date = isset($course['expiry_date']) ? (int) $course['expiry_date'] : 0;
                    $is_lifetime = !empty($course['is_lifetime']);
                    $status_class = isset($course['access_status_class']) ? $course['access_status_class'] : 'is-active';
                    $status_label = isset($course['access_status_label']) ? $course['access_status_label'] : youngo_frontend_phrase('Access active');
                    $access_label = isset($course['access_label']) ? $course['access_label'] : youngo_frontend_phrase('Access');
                    $access_message = isset($course['access_message']) ? $course['access_message'] : '';
                    $course_url = isset($course['course_url']) ? $course['course_url'] : (function_exists('youngo_frontend_public_url') ? youngo_frontend_public_url('home/course/' . rawurlencode(slugify($title)) . '/' . $course_id, $youngo_my_courses_language) : site_url('home/course/' . rawurlencode(slugify($title)) . '/' . $course_id));
                    $lesson_url = isset($course['lesson_url']) ? $course['lesson_url'] : site_url('home/lesson/' . slugify($title) . '/' . $course_id);
                    ?>
                    <article class="youngo-learning-card">
                        <a class="youngo-learning-card__media" href="<?php echo $course_url; ?>" aria-label="<?php echo youngo_my_courses_e($title); ?>">
                            <img loading="lazy" src="<?php echo $this->crud_model->get_course_thumbnail_url($course_id); ?>" alt="<?php echo youngo_my_courses_e($title); ?>">
                            <?php if ($progress >= 100): ?>
                                <span class="youngo-learning-status youngo-learning-status--complete"><?php echo youngo_frontend_phrase('Completed'); ?></span>
                            <?php else: ?>
                                <span class="youngo-learning-status"><?php echo youngo_frontend_phrase('Active'); ?></span>
                            <?php endif; ?>
                        </a>

                        <div class="youngo-learning-card__body">
                            <div class="youngo-learning-card__top">
                                <span><?php echo youngo_my_courses_e(isset($course['category_name']) ? $course['category_name'] : youngo_frontend_phrase('Course')); ?></span>
                                <strong><?php echo $progress; ?>%</strong>
                            </div>

                            <h3><a href="<?php echo $course_url; ?>"><?php echo youngo_my_courses_e($title); ?></a></h3>

                            <div class="youngo-learning-progress" aria-label="<?php echo youngo_frontend_phrase('Course progress'); ?>">
                                <div><span style="width: <?php echo $progress; ?>%;"></span></div>
                            </div>

                            <div class="youngo-learning-meta">
                                <?php if (!empty($course['instructor_name'])): ?><span><i class="fa-regular fa-user"></i> <?php echo youngo_my_courses_e($course['instructor_name']); ?></span><?php endif; ?>
                                <span><i class="fa-regular fa-list-alt"></i> <?php echo (int) (isset($course['lesson_count']) ? $course['lesson_count'] : 0) . ' ' . youngo_frontend_phrase('lessons'); ?></span>
                                <?php if (!empty($course['quiz_count'])): ?><span><i class="fa-regular fa-circle-question"></i> <?php echo (int) $course['quiz_count'] . ' ' . youngo_frontend_phrase('quizzes'); ?></span><?php endif; ?>
                                <?php if (!empty($course['duration_label'])): ?><span><i class="fa-regular fa-clock"></i> <?php echo youngo_my_courses_e($course['duration_label']); ?></span><?php endif; ?>
                            </div>

                            <div class="youngo-learning-access <?php echo youngo_my_courses_e($status_class); ?>">
                                <span class="youngo-learning-access__badge"><?php echo youngo_my_courses_e($status_label); ?></span>
                                <span class="youngo-learning-access__source"><?php echo youngo_my_courses_e($access_label); ?></span>
                            </div>

                            <?php if ($access_message !== ''): ?>
                                <p class="youngo-learning-access__message"><?php echo youngo_my_courses_e($access_message); ?></p>
                            <?php endif; ?>

                            <div class="youngo-learning-card__footer">
                                <span class="youngo-learning-expiry">
                                    <?php if (!$is_lifetime && $expiry_date > 0): ?>
                                        <?php echo youngo_frontend_phrase('Access until'); ?> <?php echo youngo_my_courses_e(youngo_my_courses_date($expiry_date)); ?>
                                    <?php else: ?>
                                        <?php echo youngo_frontend_phrase('Lifetime Access'); ?>
                                    <?php endif; ?>
                                </span>

                                <div class="youngo-learning-actions">
                                    <a class="youngo-button youngo-button--small" href="<?php echo $lesson_url; ?>">
                                        <i class="far fa-play-circle"></i>
                                        <?php echo $progress > 0 ? youngo_frontend_phrase('Continue') : youngo_frontend_phrase('Start Now'); ?>
                                    </a>
                                    <a class="youngo-button youngo-button--small youngo-button--secondary" href="<?php echo $course_url; ?>"><?php echo youngo_frontend_phrase('Course details'); ?></a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="youngo-my-courses-empty">
                <div class="youngo-my-courses-empty__icon"><i class="fa-regular fa-compass"></i></div>
                <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('No active courses yet'); ?></p>
                <h2><?php echo youngo_frontend_phrase('Start a YounGo learning path'); ?></h2>
                <p><?php echo youngo_frontend_phrase('When a course is enrolled or school-granted, it will appear here with active lesson access.'); ?></p>
                <div class="youngo-commerce-empty__actions">
                    <a class="youngo-button" href="<?php echo $youngo_my_courses_courses_url; ?>"><?php echo youngo_frontend_phrase('Browse courses'); ?></a>
                    <a class="youngo-button youngo-button--secondary" href="<?php echo $youngo_my_courses_access_url; ?>"><?php echo youngo_frontend_phrase('View My Access'); ?></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
