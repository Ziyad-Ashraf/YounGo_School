<?php
$courses = isset($learner_course_access_items) && is_array($learner_course_access_items) ? $learner_course_access_items : array();

if (!function_exists('youngo_reload_my_courses_e')) {
    function youngo_reload_my_courses_e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>

<?php if (count($courses) > 0): ?>
    <?php foreach ($courses as $course): ?>
        <?php
        $course_id = isset($course['course_id']) ? (int) $course['course_id'] : (isset($course['id']) ? (int) $course['id'] : 0);
        $title = isset($course['title']) ? $course['title'] : youngo_frontend_phrase('Course');
        $course_url = isset($course['course_url']) ? $course['course_url'] : site_url('home/course/' . rawurlencode(slugify($title)) . '/' . $course_id);
        $lesson_url = isset($course['lesson_url']) ? $course['lesson_url'] : site_url('home/lesson/' . slugify($title) . '/' . $course_id);
        $progress = isset($course['progress']) ? (int) $course['progress'] : 0;
        ?>
        <article class="youngo-learning-card">
            <a class="youngo-learning-card__media" href="<?php echo $course_url; ?>" aria-label="<?php echo youngo_reload_my_courses_e($title); ?>">
                <img loading="lazy" src="<?php echo $this->crud_model->get_course_thumbnail_url($course_id); ?>" alt="<?php echo youngo_reload_my_courses_e($title); ?>">
                <span class="youngo-learning-status"><?php echo youngo_frontend_phrase('Active'); ?></span>
            </a>

            <div class="youngo-learning-card__body">
                <div class="youngo-learning-card__top">
                    <span><?php echo youngo_reload_my_courses_e(isset($course['category_name']) ? $course['category_name'] : youngo_frontend_phrase('Course')); ?></span>
                    <strong><?php echo $progress; ?>%</strong>
                </div>

                <h3><a href="<?php echo $course_url; ?>"><?php echo youngo_reload_my_courses_e($title); ?></a></h3>

                <div class="youngo-learning-access <?php echo youngo_reload_my_courses_e(isset($course['access_status_class']) ? $course['access_status_class'] : 'is-active'); ?>">
                    <span class="youngo-learning-access__badge"><?php echo youngo_reload_my_courses_e(isset($course['access_status_label']) ? $course['access_status_label'] : youngo_frontend_phrase('Access active')); ?></span>
                    <span class="youngo-learning-access__source"><?php echo youngo_reload_my_courses_e(isset($course['access_label']) ? $course['access_label'] : youngo_frontend_phrase('Access')); ?></span>
                </div>

                <div class="youngo-learning-card__footer">
                    <span class="youngo-learning-expiry">
                        <?php if (!empty($course['expiry_date'])): ?>
                            <?php echo youngo_frontend_phrase('Access until'); ?> <?php echo date('M d, Y', (int) $course['expiry_date']); ?>
                        <?php else: ?>
                            <?php echo youngo_frontend_phrase('Lifetime Access'); ?>
                        <?php endif; ?>
                    </span>

                    <div class="youngo-learning-actions">
                        <a class="youngo-button youngo-button--small" href="<?php echo $lesson_url; ?>"><?php echo $progress > 0 ? youngo_frontend_phrase('Continue') : youngo_frontend_phrase('Start Now'); ?></a>
                        <a class="youngo-button youngo-button--small youngo-button--secondary" href="<?php echo $course_url; ?>"><?php echo youngo_frontend_phrase('Course details'); ?></a>
                    </div>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
<?php else: ?>
    <div class="youngo-my-courses-empty">
        <div class="youngo-my-courses-empty__icon"><i class="fa-regular fa-compass"></i></div>
        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('No matching courses'); ?></p>
        <h2><?php echo youngo_frontend_phrase('No active access matched this filter'); ?></h2>
        <p><?php echo youngo_frontend_phrase('Try another search or browse all YounGo courses.'); ?></p>
    </div>
<?php endif; ?>
