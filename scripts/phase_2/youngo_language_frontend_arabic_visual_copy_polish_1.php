<?php
/**
 * LANGUAGE.FRONTEND.ARABIC_VISUAL_COPY.POLISH.1
 *
 * Applies targeted Arabic public-frontend phrase copy polish for screenshot
 * findings. It updates Arabic values only for approved high-visibility phrase
 * keys, skips manual overrides, and never writes arabic_translated.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(__DIR__, 2);
chdir($root);

defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);

require $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

function yfavcp1_inventory()
{
    return array(
        array('key' => 'home', 'english' => 'Home', 'arabic' => 'الرئيسية', 'area' => 'header_footer'),
        array('key' => 'courses', 'english' => 'Courses', 'arabic' => 'الدورات', 'area' => 'header_footer'),
        array('key' => 'course', 'english' => 'Course', 'arabic' => 'دورة', 'area' => 'courses'),
        array('key' => 'subscriptions', 'english' => 'Subscriptions', 'arabic' => 'الاشتراكات', 'area' => 'header_footer'),
        array('key' => 'blog', 'english' => 'Blog', 'arabic' => 'المدونة', 'area' => 'header_footer'),
        array('key' => 'blogs', 'english' => 'Blogs', 'arabic' => 'المدونة', 'area' => 'blog'),
        array('key' => 'blog_details', 'english' => 'Blog details', 'arabic' => 'تفاصيل المقال', 'area' => 'blog'),
        array('key' => 'search_result', 'english' => 'Search result', 'arabic' => 'نتائج البحث', 'area' => 'blog'),
        array('key' => 'contact', 'english' => 'Contact', 'arabic' => 'تواصل معنا', 'area' => 'header_footer'),
        array('key' => 'contact_us', 'english' => 'Contact us', 'arabic' => 'تواصل معنا', 'area' => 'contact'),
        array('key' => 'login', 'english' => 'Login', 'arabic' => 'تسجيل الدخول', 'area' => 'header_footer'),
        array('key' => 'sign_up', 'english' => 'Sign up', 'arabic' => 'إنشاء حساب', 'area' => 'header_footer'),
        array('key' => 'primary_navigation', 'english' => 'Primary navigation', 'arabic' => 'التنقل الرئيسي', 'area' => 'header_footer'),
        array('key' => 'language_switcher', 'english' => 'Language switcher', 'arabic' => 'مبدل اللغة', 'area' => 'header_footer'),
        array('key' => 'footer_navigation', 'english' => 'Footer navigation', 'arabic' => 'روابط التذييل', 'area' => 'header_footer'),
        array('key' => 'safe,_joyful_online_learning_for_curious_kids_and_confident_families.', 'english' => 'Safe, joyful online learning for curious kids and confident families.', 'arabic' => 'تعلم آمن وممتع عبر الإنترنت للأطفال الفضوليين والعائلات الواثقة.', 'area' => 'header_footer'),

        array('key' => 'course_discovery', 'english' => 'Course discovery', 'arabic' => 'اكتشاف الدورات', 'area' => 'courses_listing'),
        array('key' => 'explore_youngo_courses', 'english' => 'Explore YounGo courses', 'arabic' => 'استكشف دورات YounGo', 'area' => 'courses_listing'),
        array('key' => 'find_structured,_friendly_learning_paths_for_curious_kids_and_the_families_supporting_them.', 'english' => 'Find structured, friendly learning paths for curious kids and the families supporting them.', 'arabic' => 'اعثر على مسارات تعلم منظمة وودودة للأطفال الفضوليين والعائلات التي تدعمهم.', 'area' => 'courses_listing'),
        array('key' => 'course_results', 'english' => 'Course results', 'arabic' => 'نتائج الدورات', 'area' => 'courses_listing'),
        array('key' => 'course_available', 'english' => 'Course available', 'arabic' => 'دورة متاحة', 'area' => 'courses_listing'),
        array('key' => 'courses_available', 'english' => 'Courses available', 'arabic' => 'دورات متاحة', 'area' => 'courses_listing'),
        array('key' => 'course_catalog', 'english' => 'Course catalog', 'arabic' => 'كتالوج الدورات', 'area' => 'courses_listing'),
        array('key' => 'showing_results', 'english' => 'Showing results', 'arabic' => 'عرض النتائج', 'area' => 'courses_listing'),
        array('key' => 'sort_by', 'english' => 'Sort by', 'arabic' => 'ترتيب حسب', 'area' => 'courses_listing'),
        array('key' => 'newly_published', 'english' => 'Newly published', 'arabic' => 'الأحدث نشرًا', 'area' => 'courses_listing'),
        array('key' => 'highest_rating', 'english' => 'Highest rating', 'arabic' => 'الأعلى تقييمًا', 'area' => 'courses_listing'),
        array('key' => 'lowest_price', 'english' => 'Lowest price', 'arabic' => 'الأقل سعرًا', 'area' => 'courses_listing'),
        array('key' => 'highest_price', 'english' => 'Highest price', 'arabic' => 'الأعلى سعرًا', 'area' => 'courses_listing'),
        array('key' => 'discounted', 'english' => 'Discounted', 'arabic' => 'عليها خصم', 'area' => 'courses_listing'),
        array('key' => 'filters', 'english' => 'Filters', 'arabic' => 'الفلاتر', 'area' => 'courses_listing'),
        array('key' => 'course_filters', 'english' => 'Course filters', 'arabic' => 'فلاتر الدورات', 'area' => 'courses_listing'),
        array('key' => 'find_a_course', 'english' => 'Find a course', 'arabic' => 'ابحث عن دورة', 'area' => 'courses_listing'),
        array('key' => 'search_by_keyword', 'english' => 'Search by keyword', 'arabic' => 'البحث بالكلمة المفتاحية', 'area' => 'courses_listing'),
        array('key' => 'search_courses', 'english' => 'Search courses', 'arabic' => 'ابحث في الدورات', 'area' => 'courses_listing'),
        array('key' => 'search', 'english' => 'Search', 'arabic' => 'بحث', 'area' => 'courses_listing'),
        array('key' => 'categories', 'english' => 'Categories', 'arabic' => 'التصنيفات', 'area' => 'courses_listing'),
        array('key' => 'all_categories', 'english' => 'All categories', 'arabic' => 'كل التصنيفات', 'area' => 'courses_listing'),
        array('key' => 'price', 'english' => 'Price', 'arabic' => 'السعر', 'area' => 'courses_listing'),
        array('key' => 'all', 'english' => 'All', 'arabic' => 'الكل', 'area' => 'courses_listing'),
        array('key' => 'free', 'english' => 'Free', 'arabic' => 'مجاني', 'area' => 'courses_listing'),
        array('key' => 'paid', 'english' => 'Paid', 'arabic' => 'مدفوع', 'area' => 'courses_listing'),
        array('key' => 'level', 'english' => 'Level', 'arabic' => 'المستوى', 'area' => 'courses_listing'),
        array('key' => 'beginner', 'english' => 'Beginner', 'arabic' => 'مبتدئ', 'area' => 'courses_listing'),
        array('key' => 'intermediate', 'english' => 'Intermediate', 'arabic' => 'متوسط', 'area' => 'courses_listing'),
        array('key' => 'advanced', 'english' => 'Advanced', 'arabic' => 'متقدم', 'area' => 'courses_listing'),
        array('key' => 'language', 'english' => 'Language', 'arabic' => 'اللغة', 'area' => 'courses_listing'),
        array('key' => 'ratings', 'english' => 'Ratings', 'arabic' => 'التقييمات', 'area' => 'courses_listing'),
        array('key' => 'apply_filters', 'english' => 'Apply filters', 'arabic' => 'تطبيق الفلاتر', 'area' => 'courses_listing'),
        array('key' => 'reset', 'english' => 'Reset', 'arabic' => 'إعادة ضبط', 'area' => 'courses_listing'),
        array('key' => 'reset_filters', 'english' => 'Reset filters', 'arabic' => 'إعادة ضبط الفلاتر', 'area' => 'courses_listing'),
        array('key' => 'clear_all_filters', 'english' => 'Clear all filters', 'arabic' => 'مسح كل الفلاتر', 'area' => 'courses_listing'),
        array('key' => 'course_layout', 'english' => 'Course layout', 'arabic' => 'طريقة عرض الدورات', 'area' => 'courses_listing'),
        array('key' => 'grid_view', 'english' => 'Grid view', 'arabic' => 'عرض شبكي', 'area' => 'courses_listing'),
        array('key' => 'list_view', 'english' => 'List view', 'arabic' => 'عرض قائمة', 'area' => 'courses_listing'),
        array('key' => 'course_pagination', 'english' => 'Course pagination', 'arabic' => 'ترقيم صفحات الدورات', 'area' => 'courses_listing'),
        array('key' => 'lessons', 'english' => 'Lessons', 'arabic' => 'الدروس', 'area' => 'courses_listing'),
        array('key' => 'new', 'english' => 'New', 'arabic' => 'جديد', 'area' => 'courses_listing'),
        array('key' => 'subscription_not_available_yet', 'english' => 'Subscription not available yet', 'arabic' => 'الاشتراك غير متاح بعد', 'area' => 'courses_listing'),
        array('key' => 'access_managed_by_school', 'english' => 'Access managed by school', 'arabic' => 'الوصول تديره المدرسة', 'area' => 'courses_listing'),
        array('key' => 'subscription_access', 'english' => 'Subscription access', 'arabic' => 'وصول الاشتراك', 'area' => 'courses_listing'),
        array('key' => 'access_locked', 'english' => 'Access locked', 'arabic' => 'الوصول مغلق', 'area' => 'courses_listing'),
        array('key' => 'view_details', 'english' => 'View details', 'arabic' => 'عرض التفاصيل', 'area' => 'courses_listing'),
        array('key' => 'browse_courses', 'english' => 'Browse courses', 'arabic' => 'تصفح الدورات', 'area' => 'courses_listing'),
        array('key' => 'course_not_found', 'english' => 'Course not found', 'arabic' => 'لم يتم العثور على الدورة', 'area' => 'courses_listing'),
        array('key' => 'try_adjusting_your_search_or_clearing_a_few_filters_to_see_more_courses.', 'english' => 'Try adjusting your search or clearing a few filters to see more courses.', 'arabic' => 'جرّب تعديل البحث أو مسح بعض الفلاتر لرؤية مزيد من الدورات.', 'area' => 'courses_listing'),

        array('key' => 'breadcrumb', 'english' => 'Breadcrumb', 'arabic' => 'مسار الصفحة', 'area' => 'course_detail'),
        array('key' => 'details', 'english' => 'Details', 'arabic' => 'التفاصيل', 'area' => 'course_detail'),
        array('key' => 'guided_learning_for_curious_kids', 'english' => 'Guided learning for curious kids', 'arabic' => 'تعلم موجّه للأطفال الفضوليين', 'area' => 'course_detail'),
        array('key' => 'created_by', 'english' => 'Created by', 'arabic' => 'بواسطة', 'area' => 'course_detail'),
        array('key' => 'reviews', 'english' => 'Reviews', 'arabic' => 'التقييمات', 'area' => 'course_detail'),
        array('key' => 'enrolled', 'english' => 'Enrolled', 'arabic' => 'مشترك', 'area' => 'course_detail'),
        array('key' => 'updated', 'english' => 'Updated', 'arabic' => 'تم التحديث', 'area' => 'course_detail'),
        array('key' => 'starts', 'english' => 'Starts', 'arabic' => 'يبدأ', 'area' => 'course_detail'),
        array('key' => 'course_actions', 'english' => 'Course actions', 'arabic' => 'إجراءات الدورة', 'area' => 'course_detail'),
        array('key' => 'preview_this_course', 'english' => 'Preview this course', 'arabic' => 'معاينة هذه الدورة', 'area' => 'course_detail'),
        array('key' => 'course_access', 'english' => 'Course access', 'arabic' => 'الوصول إلى الدورة', 'area' => 'course_detail'),
        array('key' => 'start_now', 'english' => 'Start now', 'arabic' => 'ابدأ الآن', 'area' => 'course_detail'),
        array('key' => 'join_with_your_account_and_keep_course_access_in_one_place.', 'english' => 'Join with your account and keep course access in one place.', 'arabic' => 'سجّل بحسابك واحتفظ بوصول الدورة في مكان واحد.', 'area' => 'course_detail'),
        array('key' => 'lectures', 'english' => 'Lectures', 'arabic' => 'المحاضرات', 'area' => 'course_detail'),
        array('key' => 'quizzes', 'english' => 'Quizzes', 'arabic' => 'الاختبارات', 'area' => 'course_detail'),
        array('key' => 'expiry_period', 'english' => 'Expiry period', 'arabic' => 'مدة الوصول', 'area' => 'course_detail'),
        array('key' => 'lifetime', 'english' => 'Lifetime', 'arabic' => 'مدى الحياة', 'area' => 'course_detail'),
        array('key' => 'months', 'english' => 'Months', 'arabic' => 'أشهر', 'area' => 'course_detail'),
        array('key' => 'certificate', 'english' => 'Certificate', 'arabic' => 'شهادة', 'area' => 'course_detail'),
        array('key' => 'yes', 'english' => 'Yes', 'arabic' => 'نعم', 'area' => 'course_detail'),
        array('key' => 'overview', 'english' => 'Overview', 'arabic' => 'نظرة عامة', 'area' => 'course_detail'),
        array('key' => 'course_description', 'english' => 'Course description', 'arabic' => 'وصف الدورة', 'area' => 'course_detail'),
        array('key' => 'learning_goals', 'english' => 'Learning goals', 'arabic' => 'أهداف التعلم', 'area' => 'course_detail'),
        array('key' => 'what_will_i_learn?', 'english' => 'What will I learn?', 'arabic' => 'ماذا سأتعلّم؟', 'area' => 'course_detail'),
        array('key' => 'before_class', 'english' => 'Before class', 'arabic' => 'قبل الدرس', 'area' => 'course_detail'),
        array('key' => 'requirements', 'english' => 'Requirements', 'arabic' => 'المتطلبات', 'area' => 'course_detail'),
        array('key' => 'curriculum', 'english' => 'Curriculum', 'arabic' => 'المنهج', 'area' => 'course_detail'),
        array('key' => 'lessons_inside_this_course', 'english' => 'Lessons inside this course', 'arabic' => 'الدروس داخل هذه الدورة', 'area' => 'course_detail'),
        array('key' => 'preview', 'english' => 'Preview', 'arabic' => 'معاينة', 'area' => 'course_detail'),
        array('key' => 'instructor', 'english' => 'Instructor', 'arabic' => 'المدرب', 'area' => 'course_detail'),
        array('key' => 'meet_your_guide', 'english' => 'Meet your guide', 'arabic' => 'تعرف على مرشدك', 'area' => 'course_detail'),
        array('key' => 'course_guide', 'english' => 'Course guide', 'arabic' => 'مرشد الدورة', 'area' => 'course_detail'),
        array('key' => 'trusted_guide', 'english' => 'Trusted guide', 'arabic' => 'مرشد موثوق', 'area' => 'course_detail'),
        array('key' => 'structured_lessons', 'english' => 'Structured lessons', 'arabic' => 'دروس منظمة', 'area' => 'course_detail'),
        array('key' => 'follow', 'english' => 'Follow', 'arabic' => 'متابعة', 'area' => 'course_detail'),
        array('key' => 'unfollow', 'english' => 'Unfollow', 'arabic' => 'إلغاء المتابعة', 'area' => 'course_detail'),
        array('key' => 'view_profile', 'english' => 'View profile', 'arabic' => 'عرض الملف الشخصي', 'area' => 'course_detail'),
        array('key' => 'family_and_learner_feedback', 'english' => 'Family and learner feedback', 'arabic' => 'آراء العائلات والمتعلمين', 'area' => 'course_detail'),
        array('key' => 'no_reviews_yet.', 'english' => 'No reviews yet.', 'arabic' => 'لا توجد تقييمات بعد.', 'area' => 'course_detail'),
        array('key' => 'questions', 'english' => 'Questions', 'arabic' => 'الأسئلة', 'area' => 'course_detail'),
        array('key' => 'frequently_asked_questions', 'english' => 'Frequently asked questions', 'arabic' => 'الأسئلة الشائعة', 'area' => 'course_detail'),
        array('key' => 'more_details', 'english' => 'More details', 'arabic' => 'مزيد من التفاصيل', 'area' => 'course_detail'),
        array('key' => 'additional_information', 'english' => 'Additional information', 'arabic' => 'معلومات إضافية', 'area' => 'course_detail'),
        array('key' => 'watch_video', 'english' => 'Watch video', 'arabic' => 'شاهد الفيديو', 'area' => 'course_detail'),
        array('key' => 'course_confidence', 'english' => 'Course confidence', 'arabic' => 'الثقة في الدورة', 'area' => 'course_detail'),
        array('key' => 'a_structured_learning_path_with_clear_lessons,_instructor_guidance,_and_progress-friendly_activities.', 'english' => 'A structured learning path with clear lessons, instructor guidance, and progress-friendly activities.', 'arabic' => 'مسار تعلم منظم بدروس واضحة وإرشاد من المدرب وأنشطة تساعد على التقدم.', 'area' => 'course_detail'),
        array('key' => 'keep_exploring', 'english' => 'Keep exploring', 'arabic' => 'تابع الاستكشاف', 'area' => 'course_detail'),
        array('key' => 'related_courses', 'english' => 'Related courses', 'arabic' => 'دورات ذات صلة', 'area' => 'course_detail'),
        array('key' => 'close', 'english' => 'Close', 'arabic' => 'إغلاق', 'area' => 'course_detail'),

        array('key' => 'duration', 'english' => 'Duration', 'arabic' => 'المدة', 'area' => 'subscriptions'),
        array('key' => 'day', 'english' => 'day', 'arabic' => 'يوم', 'area' => 'subscriptions'),
        array('key' => 'days', 'english' => 'Days', 'arabic' => 'أيام', 'area' => 'subscriptions'),
        array('key' => 'egp', 'english' => 'EGP', 'arabic' => 'جنيه مصري', 'area' => 'subscriptions'),
        array('key' => 'family_access_plans', 'english' => 'Family access plans', 'arabic' => 'خطط وصول العائلة', 'area' => 'subscriptions'),
        array('key' => 'subscription_plans', 'english' => 'Subscription plans', 'arabic' => 'خطط الاشتراك', 'area' => 'subscriptions'),
        array('key' => 'choose_a_learning_plan_for_consistent_youngo_access._online_access_requests_are_not_available_yet.', 'english' => 'Choose a learning plan for consistent YounGo access. Online access requests are not available yet.', 'arabic' => 'اختر خطة تعلم لوصول مستمر إلى YounGo. طلبات الوصول عبر الإنترنت غير متاحة بعد.', 'area' => 'subscriptions'),
        array('key' => 'plan_options', 'english' => 'Plan options', 'arabic' => 'خيارات الخطط', 'area' => 'subscriptions'),
        array('key' => 'plan_available', 'english' => 'Plan available', 'arabic' => 'خطة متاحة', 'area' => 'subscriptions'),
        array('key' => 'plans_available', 'english' => 'Plans available', 'arabic' => 'خطط متاحة', 'area' => 'subscriptions'),
        array('key' => 'featured_plan', 'english' => 'Featured plan', 'arabic' => 'خطة مميزة', 'area' => 'subscriptions'),
        array('key' => 'coming_soon', 'english' => 'Coming soon', 'arabic' => 'قريبًا', 'area' => 'subscriptions'),
        array('key' => 'no_subscription_plans_available_yet', 'english' => 'No subscription plans available yet', 'arabic' => 'لا توجد خطط اشتراك متاحة بعد', 'area' => 'subscriptions'),
        array('key' => 'subscription_plans_will_appear_here_after_they_are_approved_and_made_purchasable.', 'english' => 'Subscription plans will appear here after they are approved and made purchasable.', 'arabic' => 'ستظهر خطط الاشتراك هنا بعد اعتمادها وإتاحتها للشراء.', 'area' => 'subscriptions'),
        array('key' => 'talk_to_us_about_subscriptions', 'english' => 'Talk to us about subscriptions', 'arabic' => 'تواصل معنا بخصوص الاشتراكات', 'area' => 'subscriptions'),
        array('key' => 'contact_us_to_choose_the_right_starting_point_before_subscriptions_open_online.', 'english' => 'Contact us to choose the right starting point before subscriptions open online.', 'arabic' => 'تواصل معنا لاختيار نقطة البداية المناسبة قبل فتح الاشتراكات عبر الإنترنت.', 'area' => 'subscriptions'),

        array('key' => 'latest_articles', 'english' => 'Latest articles', 'arabic' => 'أحدث المقالات', 'area' => 'blog'),
        array('key' => 'helpful_notes_for_families', 'english' => 'Helpful notes for families', 'arabic' => 'ملاحظات مفيدة للعائلات', 'area' => 'blog'),
        array('key' => 'practical_articles_and_learning_tips_for_parents_will_appear_here_soon.', 'english' => 'Practical articles and learning tips for parents will appear here soon.', 'arabic' => 'ستظهر هنا قريبًا مقالات عملية ونصائح تعلم للأهل.', 'area' => 'blog'),
        array('key' => 'all_articles', 'english' => 'All articles', 'arabic' => 'كل المقالات', 'area' => 'blog'),
        array('key' => 'no_blog_posts_yet', 'english' => 'No blog posts yet', 'arabic' => 'لا توجد مقالات بعد', 'area' => 'blog'),
        array('key' => 'read_more', 'english' => 'Read more', 'arabic' => 'اقرأ المزيد', 'area' => 'blog'),
        array('key' => 'we_will_share_family_learning_notes_here_as_the_youngo_library_grows.', 'english' => 'We will share family learning notes here as the YounGo library grows.', 'arabic' => 'سنشارك هنا ملاحظات تعلم للعائلات مع نمو مكتبة YounGo.', 'area' => 'blog'),

        array('key' => 'get_in_touch', 'english' => 'Get in touch', 'arabic' => 'تواصل معنا', 'area' => 'contact'),
        array('key' => 'have_a_question_about_youngo_programs?_we_would_be_happy_to_hear_from_you.', 'english' => 'Have a question about YounGo programs? We would be happy to hear from you.', 'arabic' => 'هل لديك سؤال عن برامج YounGo؟ يسعدنا أن نسمع منك.', 'area' => 'contact'),
        array('key' => 'email', 'english' => 'Email', 'arabic' => 'البريد الإلكتروني', 'area' => 'contact'),
        array('key' => 'phone', 'english' => 'Phone', 'arabic' => 'الهاتف', 'area' => 'contact'),
        array('key' => 'address', 'english' => 'Address', 'arabic' => 'العنوان', 'area' => 'contact'),
        array('key' => 'working_hours', 'english' => 'Working hours', 'arabic' => 'ساعات العمل', 'area' => 'contact'),
        array('key' => 'follow_us', 'english' => 'Follow us', 'arabic' => 'تابعنا', 'area' => 'contact'),
        array('key' => 'contact_us_by_email', 'english' => 'Contact us by email', 'arabic' => 'تواصل معنا عبر البريد الإلكتروني', 'area' => 'contact'),
        array('key' => 'ready_to_talk_about_the_right_learning_path?', 'english' => 'Ready to talk about the right learning path?', 'arabic' => 'هل أنت مستعد للحديث عن مسار التعلم المناسب؟', 'area' => 'contact'),
        array('key' => 'send_us_an_email_and_the_youngo_team_will_help_you_choose_a_good_starting_point.', 'english' => 'Send us an email and the YounGo team will help you choose a good starting point.', 'arabic' => 'أرسل لنا بريدًا إلكترونيًا وسيساعدك فريق YounGo في اختيار نقطة بداية مناسبة.', 'area' => 'contact'),
    );
}

function yfavcp1_connect($db, $active_group)
{
    if (!isset($db[$active_group])) {
        throw new RuntimeException('Active database group was not found.');
    }

    $config = $db[$active_group];
    $mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
    if ($mysqli->connect_errno) {
        throw new RuntimeException('DB connection failed without exposing credentials.');
    }
    $mysqli->set_charset('utf8mb4');

    return $mysqli;
}

function yfavcp1_normalize_key($phrase_key)
{
    $phrase_key = strtolower(preg_replace('/\s+/', '_', trim((string) $phrase_key)));
    $phrase_key = preg_replace('/_+/', '_', $phrase_key);

    return trim($phrase_key, '_');
}

function yfavcp1_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $exists = !empty($row['table_count']);
    $stmt->close();

    return $exists;
}

function yfavcp1_has_manual_override($mysqli, $phrase_key)
{
    static $metaExists = null;

    if ($metaExists === null) {
        $metaExists = yfavcp1_table_exists($mysqli, 'youngo_language_phrase_meta');
    }

    if (!$metaExists) {
        return false;
    }

    $stmt = $mysqli->prepare("SELECT id FROM youngo_language_phrase_meta WHERE phrase_key = ? AND language_code = 'arabic' AND (source = 'manual_override' OR manually_overridden_at IS NOT NULL) LIMIT 1");
    $stmt->bind_param('s', $phrase_key);
    $stmt->execute();
    $has = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $has;
}

function yfavcp1_find_phrase($mysqli, $phrase_key)
{
    $normalized = yfavcp1_normalize_key($phrase_key);
    foreach (array($phrase_key, $normalized) as $candidate) {
        $stmt = $mysqli->prepare('SELECT phrase_id, phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1');
        $stmt->bind_param('s', $candidate);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            return $row;
        }
    }

    return null;
}

function yfavcp1_arabic_needs_polish($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return true;
    }

    if (!preg_match('/\p{Arabic}/u', $value)) {
        return true;
    }

    return (bool) preg_match('/(?:ï¿½|Ãƒ|Ã‚|Ã˜|Ã™|Ã|Ã‘|\x{00D8}|\x{00D9})/u', $value);
}

function yfavcp1_insert_phrase($mysqli, $phrase_key, $english, $arabic)
{
    $stmt = $mysqli->prepare('INSERT INTO language (phrase, english, arabic) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $phrase_key, $english, $arabic);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function yfavcp1_update_arabic($mysqli, $phrase_id, $arabic)
{
    $stmt = $mysqli->prepare('UPDATE language SET arabic = ? WHERE phrase_id = ?');
    $phrase_id = (int) $phrase_id;
    $stmt->bind_param('si', $arabic, $phrase_id);
    $ok = $stmt->execute();
    $changed = $stmt->affected_rows > 0;
    $stmt->close();

    return $ok && $changed;
}

function yfavcp1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

try {
    $mysqli = yfavcp1_connect($db, $active_group);
    $inventory = yfavcp1_inventory();
    $counts = array(
        'keys_considered' => count($inventory),
        'inserted_rows' => 0,
        'arabic_values_updated' => 0,
        'existing_arabic_preserved' => 0,
        'manual_overrides_preserved' => 0,
        'skipped_protected_keys' => 0,
        'errors' => 0,
        'writes_arabic_translated' => false,
    );
    $byArea = array();

    $mysqli->begin_transaction();

    foreach ($inventory as $item) {
        $key = yfavcp1_normalize_key($item['key']);
        $byArea[$item['area']] = isset($byArea[$item['area']]) ? $byArea[$item['area']] + 1 : 1;

        if ($key === '' || $key === 'arabic_translated' || stripos($key, 'paymob') !== false || preg_match('/(^|_)checkout(_|$)|(^|_)payment(_|$)|(^|_)order(_|$)|(^|_)enrol(_|$)|(^|_)grant(_|$)/', $key)) {
            $counts['skipped_protected_keys']++;
            continue;
        }

        $row = yfavcp1_find_phrase($mysqli, $key);
        if (!$row) {
            if (yfavcp1_insert_phrase($mysqli, $key, $item['english'], $item['arabic'])) {
                $counts['inserted_rows']++;
            } else {
                $counts['errors']++;
            }
            continue;
        }

        if (yfavcp1_has_manual_override($mysqli, $row['phrase'])) {
            $counts['manual_overrides_preserved']++;
            continue;
        }

        if (yfavcp1_arabic_needs_polish($row['arabic'])) {
            if (yfavcp1_update_arabic($mysqli, (int) $row['phrase_id'], $item['arabic'])) {
                $counts['arabic_values_updated']++;
            }
        } else {
            $counts['existing_arabic_preserved']++;
        }
    }

    if ($counts['errors'] > 0 || $counts['writes_arabic_translated']) {
        $mysqli->rollback();
        yfavcp1_print('Result', array('status' => 'failed', 'counts' => $counts));
        exit(1);
    }

    $mysqli->commit();

    yfavcp1_print('Arabic visual copy polish counts', $counts);
    yfavcp1_print('Inventory by area', $byArea);
    yfavcp1_print('Result', array('status' => 'passed', 'note' => 'Targeted Arabic phrase values polished without arabic_translated or payment changes.'));
    $mysqli->close();
} catch (Throwable $exception) {
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->rollback();
    }
    echo "ERROR: " . $exception->getMessage() . "\n";
    exit(1);
}
