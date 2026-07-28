<?php
$youngo_instructor_language = isset($youngo_frontend_language) ? $youngo_frontend_language : 'english';
$youngo_instructor_is_arabic = $youngo_instructor_language === 'arabic';
$youngo_instructor_arabic_labels = array(
    'breadcrumb' => 'مسار التنقل', 'home' => 'الرئيسية',
    'instructor_profile' => 'ملف المدرّس', 'profile_unavailable' => 'الملف الشخصي غير متاح',
    'instructor_not_found' => 'لم يتم العثور على المدرّس', 'meet_your_guide' => 'تعرّف على مدرّسك',
    'instructor_statistics' => 'إحصائيات المدرّس', 'learners' => 'متعلمون', 'average_rating' => 'متوسط التقييم',
    'about_the_instructor' => 'عن المدرّس', 'learn_with_them' => 'تعلّم معه', 'featured_courses' => 'الدورات المميزة',
    'no_active_courses' => 'لا توجد دورات نشطة متاحة حاليًا.', 'explore_course' => 'استكشف الدورة', 'courses' => 'دورات'
);
$youngo_instructor_phrase = static function ($key, $english, $arabic) use ($youngo_instructor_language, $youngo_instructor_is_arabic, $youngo_instructor_arabic_labels) {
    if ($youngo_instructor_is_arabic && isset($youngo_instructor_arabic_labels[$key])) {
        return $youngo_instructor_arabic_labels[$key];
    }
    return youngo_frontend_phrase($key, $youngo_instructor_is_arabic ? $arabic : $english, $youngo_instructor_language);
};
$youngo_instructor = $this->user_model->get_all_user($instructor_id)->row_array();
$youngo_instructor_name = trim(($youngo_instructor['first_name'] ?? '') . ' ' . ($youngo_instructor['last_name'] ?? ''));
$youngo_instructor_name = $youngo_instructor_name !== '' ? $youngo_instructor_name : youngo_frontend_phrase('instructor', 'Instructor', $youngo_instructor_language);
$youngo_instructor_courses = $this->db->where('creator', (int) $instructor_id)->where('status', 'active')->order_by('id', 'DESC')->get('course')->result_array();

$youngo_student_ids = array();
if (!empty($youngo_instructor_courses)) {
    $youngo_course_ids = array_column($youngo_instructor_courses, 'id');
    $youngo_student_rows = $this->db->select('user_id')->distinct()->where_in('course_id', $youngo_course_ids)->get('enrol')->result_array();
    $youngo_student_ids = array_column($youngo_student_rows, 'user_id');
}

$youngo_rating_total = 0;
$youngo_rating_count = 0;
if (!empty($youngo_instructor_courses)) {
    $youngo_ratings = $this->db->select('rating')->where('ratable_type', 'course')->where_in('ratable_id', array_column($youngo_instructor_courses, 'id'))->get('rating')->result_array();
    $youngo_rating_count = count($youngo_ratings);
    foreach ($youngo_ratings as $youngo_rating) {
        $youngo_rating_total += (float) ($youngo_rating['rating'] ?? 0);
    }
}
$youngo_rating_average = $youngo_rating_count > 0 ? number_format($youngo_rating_total / $youngo_rating_count, 1) : '0.0';
$youngo_e = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>

<section class="youngo-instructor-page" <?php echo $youngo_instructor_is_arabic ? 'dir="rtl" lang="ar"' : 'lang="en"'; ?>>
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo $youngo_e($youngo_instructor_phrase('breadcrumb', 'Breadcrumb', 'مسار التنقل')); ?>">
            <a href="<?php echo $youngo_e(function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_instructor_language) : site_url('home')); ?>"><?php echo $youngo_e($youngo_instructor_phrase('home', 'Home', 'الرئيسية')); ?></a>
            <span>/</span>
            <a href="<?php echo $youngo_e(function_exists('youngo_frontend_courses_url') ? youngo_frontend_courses_url($youngo_instructor_language) : site_url('home/courses')); ?>"><?php echo $youngo_e($youngo_instructor_phrase('courses', 'Courses', 'الدورات')); ?></a>
            <span>/</span>
            <span><?php echo $youngo_e($youngo_instructor_phrase('instructor_profile', 'Instructor profile', 'ملف المدرّس')); ?></span>
        </nav>

        <?php if (empty($youngo_instructor)): ?>
            <section class="youngo-placeholder">
                <p class="youngo-eyebrow"><?php echo $youngo_e($youngo_instructor_phrase('profile_unavailable', 'Profile unavailable', 'الملف الشخصي غير متاح')); ?></p>
                <h1><?php echo $youngo_e($youngo_instructor_phrase('instructor_not_found', 'Instructor not found', 'لم يتم العثور على المدرّس')); ?></h1>
                <a class="youngo-button" href="<?php echo $youngo_e(function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_instructor_language) : site_url('home')); ?>"><?php echo $youngo_e($youngo_instructor_phrase('home', 'Home', 'الرئيسية')); ?></a>
            </section>
        <?php else: ?>
            <header class="youngo-instructor-hero">
                <div class="youngo-instructor-identity">
                    <img src="<?php echo $youngo_e($this->user_model->get_user_image_url($youngo_instructor['id'])); ?>" alt="<?php echo $youngo_e($youngo_instructor_name); ?>">
                    <div>
                    <p class="youngo-eyebrow"><?php echo $youngo_e($youngo_instructor_phrase('meet_your_guide', 'Meet your guide', 'تعرّف على مدرّسك')); ?></p>
                        <h1><?php echo $youngo_e($youngo_instructor_name); ?></h1>
                        <?php if (!empty($youngo_instructor['title'])): ?><p class="youngo-instructor-title"><?php echo $youngo_e($youngo_instructor['title']); ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="youngo-instructor-stats" aria-label="<?php echo $youngo_e($youngo_instructor_phrase('instructor_statistics', 'Instructor statistics', 'إحصائيات المدرّس')); ?>">
                    <span><strong><?php echo count($youngo_instructor_courses); ?></strong><?php echo $youngo_e($youngo_instructor_phrase('courses', 'Courses', 'دورات')); ?></span>
                    <span><strong><?php echo count($youngo_student_ids); ?></strong><?php echo $youngo_e($youngo_instructor_phrase('learners', 'Learners', 'متعلمون')); ?></span>
                    <span><strong><?php echo $youngo_e($youngo_rating_average); ?></strong><?php echo $youngo_e($youngo_instructor_phrase('average_rating', 'Average rating', 'متوسط التقييم')); ?></span>
                </div>
            </header>

            <?php if (!empty($youngo_instructor['biography'])): ?>
                <section class="youngo-instructor-about">
                    <p class="youngo-eyebrow"><?php echo $youngo_e($youngo_instructor_phrase('about_the_instructor', 'About the instructor', 'عن المدرّس')); ?></p>
                    <div class="youngo-rich-text"><?php echo strip_tags($youngo_instructor['biography'], '<p><br><strong><em><ul><ol><li>'); ?></div>
                </section>
            <?php endif; ?>

            <section class="youngo-instructor-courses" aria-labelledby="youngo-instructor-courses-title">
                <div class="youngo-section__heading">
                    <div><p class="youngo-eyebrow"><?php echo $youngo_e($youngo_instructor_phrase('learn_with_them', 'Learn with them', 'تعلّم معه')); ?></p><h2 id="youngo-instructor-courses-title"><?php echo $youngo_e($youngo_instructor_phrase('featured_courses', 'Featured courses', 'الدورات المميزة')); ?></h2></div>
                    <span><?php echo count($youngo_instructor_courses); ?> <?php echo $youngo_e($youngo_instructor_phrase('courses', 'courses', 'دورات')); ?></span>
                </div>
                <?php if (empty($youngo_instructor_courses)): ?>
                    <p class="youngo-instructor-empty"><?php echo $youngo_e($youngo_instructor_phrase('no_active_courses', 'No active courses are available yet.', 'لا توجد دورات نشطة متاحة حاليًا.')); ?></p>
                <?php else: ?>
                    <div class="youngo-instructor-course-grid">
                        <?php foreach ($youngo_instructor_courses as $youngo_course): $youngo_course = function_exists('youngo_frontend_translate_course_row') ? youngo_frontend_translate_course_row($youngo_course, $youngo_instructor_language) : $youngo_course; ?>
                            <a class="youngo-instructor-course" href="<?php echo $youngo_e(function_exists('youngo_frontend_course_detail_url') ? youngo_frontend_course_detail_url($youngo_course, $youngo_instructor_language) : site_url('home/course/' . rawurlencode(slugify($youngo_course['title'])) . '/' . $youngo_course['id'])); ?>">
                                <img src="<?php echo $youngo_e($this->crud_model->get_course_thumbnail_url($youngo_course['id'])); ?>" alt="<?php echo $youngo_e($youngo_course['title']); ?>">
                                <span><strong><?php echo $youngo_e($youngo_course['title']); ?></strong><small><?php echo $youngo_e($youngo_course['short_description'] ?? ''); ?></small><em><?php echo $youngo_e($youngo_instructor_phrase('explore_course', 'Explore course', 'استكشف الدورة')); ?> <span aria-hidden="true">→</span></em></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</section>
