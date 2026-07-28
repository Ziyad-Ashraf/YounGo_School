<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('youngo_frontend_supported_languages')) {
    function youngo_frontend_supported_languages()
    {
        return array(
            'english' => array(
                'language_code' => 'english',
                'short_code' => 'en',
                'uri_prefix' => 'en',
                'html_lang' => 'en',
                'html_dir' => 'ltr',
            ),
            'arabic' => array(
                'language_code' => 'arabic',
                'short_code' => 'ar',
                'uri_prefix' => '',
                'compatibility_uri_prefix' => 'ar',
                'html_lang' => 'ar',
                'html_dir' => 'rtl',
            ),
        );
    }
}

if (!function_exists('youngo_frontend_normalize_language_code')) {
    function youngo_frontend_normalize_language_code($language_code)
    {
        $language_code = strtolower(trim((string) $language_code));

        if ($language_code === 'en' || $language_code === 'eng' || $language_code === 'english') {
            return 'english';
        }

        if ($language_code === 'ar' || $language_code === 'ara' || $language_code === 'arabic' || $language_code === 'arabic_translated') {
            return 'arabic';
        }

        return 'english';
    }
}

if (!function_exists('youngo_frontend_language_from_uri')) {
    function youngo_frontend_language_from_uri($uri_string = null)
    {
        $path = youngo_frontend_uri_path($uri_string);
        $segments = youngo_frontend_uri_segments($path);

        if (!empty($segments) && $segments[0] === 'en' && youngo_frontend_is_localizable_uri($path)) {
            return 'english';
        }

        if (!empty($segments) && $segments[0] === 'ar' && youngo_frontend_is_localizable_uri($path)) {
            return 'arabic';
        }

        if (youngo_frontend_is_localizable_uri($path)) {
            return 'arabic';
        }

        return 'english';
    }
}

if (!function_exists('youngo_frontend_active_language')) {
    function youngo_frontend_active_language($uri_string = null)
    {
        return youngo_frontend_language_from_uri($uri_string);
    }
}

if (!function_exists('youngo_frontend_is_arabic_uri')) {
    function youngo_frontend_is_arabic_uri($uri_string = null)
    {
        return youngo_frontend_language_from_uri($uri_string) === 'arabic';
    }
}

if (!function_exists('youngo_frontend_strip_language_prefix')) {
    function youngo_frontend_strip_language_prefix($uri_string)
    {
        $parts = youngo_frontend_split_uri_query($uri_string);
        $path = youngo_frontend_normalize_uri_path($parts['path']);
        $segments = $path === '' ? array() : explode('/', $path);

        if (!empty($segments) && in_array(strtolower($segments[0]), array('ar', 'en'), true)) {
            array_shift($segments);
        }

        $path = implode('/', $segments);
        return youngo_frontend_join_uri_query($path, $parts['query']);
    }
}

if (!function_exists('youngo_frontend_add_language_prefix')) {
    function youngo_frontend_add_language_prefix($uri_string, $language_code)
    {
        $language_code = youngo_frontend_normalize_language_code($language_code);
        $parts = youngo_frontend_split_uri_query(youngo_frontend_strip_language_prefix($uri_string));
        $path = $parts['path'];

        if ($language_code === 'english') {
            $path = $path === '' ? 'en' : 'en/' . $path;
        }

        return youngo_frontend_join_uri_query($path, $parts['query']);
    }
}

if (!function_exists('youngo_frontend_is_localizable_uri')) {
    function youngo_frontend_is_localizable_uri($uri_string)
    {
        $path = youngo_frontend_strip_language_prefix(youngo_frontend_uri_path($uri_string));
        $path = strtolower(youngo_frontend_normalize_uri_path($path));

        if ($path === '') {
            return true;
        }

        $excluded_prefixes = youngo_frontend_non_localizable_uri_prefixes();
        foreach ($excluded_prefixes as $prefix) {
            if ($path === $prefix || strpos($path, $prefix . '/') === 0) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('youngo_frontend_language_url')) {
    function youngo_frontend_language_url($target_language, $uri_string = null, $preserve_query = true)
    {
        $uri_string = $uri_string === null ? youngo_frontend_current_uri_string() : $uri_string;
        $parts = youngo_frontend_split_uri_query($uri_string);
        $path = $parts['path'];
        $query = $preserve_query ? $parts['query'] : '';

        if (!youngo_frontend_is_localizable_uri($path)) {
            return youngo_frontend_join_uri_query(youngo_frontend_normalize_uri_path($path), $query);
        }

        $target_language = youngo_frontend_normalize_language_code($target_language);
        return youngo_frontend_add_language_prefix(youngo_frontend_join_uri_query($path, $query), $target_language);
    }
}

if (!function_exists('youngo_frontend_current_uri_string_with_query')) {
    function youngo_frontend_current_uri_string_with_query()
    {
        if (function_exists('get_instance')) {
            $CI = &get_instance();
            if (isset($CI->uri) && method_exists($CI->uri, 'uri_string')) {
                $uri = youngo_frontend_normalize_uri_path($CI->uri->uri_string());

                if (!empty($_SERVER['QUERY_STRING']) && strpos($uri, '?') === false) {
                    $uri .= '?' . $_SERVER['QUERY_STRING'];
                }

                return $uri;
            }
        }

        if (!empty($_SERVER['REQUEST_URI'])) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

            if ($path !== null && $path !== false) {
                $uri = youngo_frontend_normalize_uri_path($path);

                if ($query !== null && $query !== false && $query !== '') {
                    $uri .= '?' . $query;
                }

                return $uri;
            }
        }

        $uri = youngo_frontend_current_uri_string();

        if (!empty($_SERVER['QUERY_STRING']) && strpos($uri, '?') === false) {
            $uri .= '?' . $_SERVER['QUERY_STRING'];
        }

        return $uri;
    }
}

if (!function_exists('youngo_frontend_public_route_equivalent_path')) {
    function youngo_frontend_public_route_equivalent_path($uri_string, $target_language)
    {
        $target_language = youngo_frontend_normalize_language_code($target_language);
        $path = youngo_frontend_normalize_uri_path(youngo_frontend_strip_language_prefix($uri_string));
        $segments = $path === '' ? array() : explode('/', $path);
        $normalized_segments = array_map('strtolower', $segments);

        if ($path === '' || $normalized_segments === array('home')) {
            return $target_language === 'arabic' ? '' : 'en';
        }

        if (isset($normalized_segments[0], $normalized_segments[1]) && $normalized_segments[0] === 'home' && $normalized_segments[1] === 'courses') {
            $tail = array_slice($segments, 2);
            $base = $target_language === 'arabic' ? 'home/courses' : 'en/home/courses';

            return $tail ? $base . '/' . implode('/', $tail) : $base;
        }

        if (isset($normalized_segments[0]) && $normalized_segments[0] === 'courses') {
            $tail = array_slice($segments, 1);
            $base = $target_language === 'arabic' ? 'home/courses' : 'en/home/courses';

            return $tail ? $base . '/' . implode('/', $tail) : $base;
        }

        if (isset($normalized_segments[0], $normalized_segments[1]) && $normalized_segments[0] === 'home' && $normalized_segments[1] === 'course') {
            $tail = array_slice($segments, 2);
            $base = $target_language === 'arabic' ? 'home/course' : 'en/home/course';

            return $tail ? $base . '/' . implode('/', $tail) : $base;
        }

        if (isset($normalized_segments[0], $normalized_segments[1]) && $normalized_segments[0] === 'home' && $normalized_segments[1] === 'instructor_page') {
            $tail = array_slice($segments, 2);
            $base = $target_language === 'arabic' ? 'home/instructor_page' : 'en/home/instructor_page';

            return $tail ? $base . '/' . implode('/', $tail) : $base;
        }

        if (isset($normalized_segments[0]) && $normalized_segments[0] === 'course') {
            $tail = array_slice($segments, 1);
            $base = $target_language === 'arabic' ? 'home/course' : 'en/home/course';

            return $tail ? $base . '/' . implode('/', $tail) : $base;
        }

        if (isset($normalized_segments[0], $normalized_segments[1]) && $normalized_segments[0] === 'home' && $normalized_segments[1] === 'search') {
            $tail = array_slice($segments, 2);
            $base = $target_language === 'arabic' ? 'home/search' : 'en/home/search';

            return $tail ? $base . '/' . implode('/', $tail) : $base;
        }

        if (isset($normalized_segments[0]) && $normalized_segments[0] === 'search') {
            $tail = array_slice($segments, 1);
            $base = $target_language === 'arabic' ? 'home/search' : 'en/home/search';

            return $tail ? $base . '/' . implode('/', $tail) : $base;
        }

        if ($path === 'home/my_courses' || $path === 'my-courses') {
            return $target_language === 'arabic' ? 'home/my_courses' : 'en/home/my_courses';
        }

        if ($path === 'home/my_access' || $path === 'my-access') {
            return $target_language === 'arabic' ? 'home/my_access' : 'en/home/my_access';
        }

        if ($path === 'home/my_wishlist' || $path === 'wishlist') {
            return $target_language === 'arabic' ? 'home/my_wishlist' : 'en/home/my_wishlist';
        }

        if ($path === 'subscriptions' || $path === 'home/subscriptions' || $path === 'pricing') {
            return $target_language === 'arabic' ? 'subscriptions' : 'en/subscriptions';
        }

        if ($path === 'blog' || $path === 'blogs' || $path === 'home/blog' || $path === 'blog/categories') {
            return $target_language === 'arabic' ? 'home/blog' : 'en/home/blog';
        }

        if (isset($normalized_segments[0], $normalized_segments[1]) && $normalized_segments[0] === 'blog' && $normalized_segments[1] === 'details') {
            $tail = array_slice($segments, 2);
            $base = $target_language === 'arabic' ? 'blog/details' : 'en/blog/details';

            return $tail ? $base . '/' . implode('/', $tail) : $base;
        }

        if ($path === 'home/contact_us' || $path === 'home/contact' || $path === 'contact') {
            return $target_language === 'arabic' ? 'home/contact' : 'en/home/contact';
        }

        if ($path === 'login') {
            return $target_language === 'arabic' ? 'login' : 'en/login';
        }

        if ($path === 'login/forgot_password_request') {
            return $target_language === 'arabic' ? 'login/forgot_password_request' : 'en/login/forgot_password_request';
        }

        if ($path === 'sign_up' || $path === 'sign-up') {
            return $target_language === 'arabic' ? 'sign_up' : 'en/sign-up';
        }

        return null;
    }
}

if (!function_exists('youngo_frontend_language_switch_path')) {
    function youngo_frontend_language_switch_path($target_language, $uri_string = null, $preserve_query = true)
    {
        $target_language = youngo_frontend_normalize_language_code($target_language);
        $uri_string = $uri_string === null ? youngo_frontend_current_uri_string_with_query() : $uri_string;
        $parts = youngo_frontend_split_uri_query($uri_string);
        $query = $preserve_query ? $parts['query'] : '';
        $path = youngo_frontend_normalize_uri_path($parts['path']);

        if (!youngo_frontend_is_localizable_uri($path)) {
            return youngo_frontend_join_uri_query($path, $query);
        }

        $equivalent_path = youngo_frontend_public_route_equivalent_path($path, $target_language);

        if ($equivalent_path !== null) {
            return youngo_frontend_join_uri_query($equivalent_path, $query);
        }

        return youngo_frontend_language_url($target_language, youngo_frontend_join_uri_query($path, $query), true);
    }
}

if (!function_exists('youngo_frontend_language_switch_url')) {
    function youngo_frontend_language_switch_url($target_language, $uri_string = null, $preserve_query = true)
    {
        $path = youngo_frontend_language_switch_path($target_language, $uri_string, $preserve_query);

        if (function_exists('site_url')) {
            return site_url($path);
        }

        return $path;
    }
}

if (!function_exists('youngo_frontend_public_path')) {
    function youngo_frontend_public_path($uri_string = '', $language_code = null, $query_string = '')
    {
        $language_code = $language_code === null ? youngo_frontend_active_language() : youngo_frontend_normalize_language_code($language_code);
        $parts = youngo_frontend_split_uri_query($uri_string);
        $query = $query_string !== '' ? ltrim((string) $query_string, '?') : $parts['query'];
        $path = youngo_frontend_normalize_uri_path($parts['path']);

        if (!youngo_frontend_is_localizable_uri($path)) {
            return youngo_frontend_join_uri_query($path, $query);
        }

        $equivalent_path = youngo_frontend_public_route_equivalent_path($path, $language_code);
        if ($equivalent_path === null) {
            $equivalent_path = youngo_frontend_add_language_prefix($path, $language_code);
        }

        return youngo_frontend_join_uri_query($equivalent_path, $query);
    }
}

if (!function_exists('youngo_frontend_public_url')) {
    function youngo_frontend_public_url($uri_string = '', $language_code = null, $query_string = '')
    {
        $path = youngo_frontend_public_path($uri_string, $language_code, $query_string);

        if (function_exists('site_url')) {
            return site_url($path);
        }

        return '/' . ltrim((string) $path, '/');
    }
}

if (!function_exists('youngo_frontend_home_url')) {
    function youngo_frontend_home_url($language_code = null)
    {
        return youngo_frontend_public_url('', $language_code);
    }
}

if (!function_exists('youngo_frontend_courses_path')) {
    function youngo_frontend_courses_path($language_code = null, $query_string = '')
    {
        return youngo_frontend_public_path('home/courses', $language_code, $query_string);
    }
}

if (!function_exists('youngo_frontend_courses_url')) {
    function youngo_frontend_courses_url($language_code = null, $query_string = '')
    {
        return youngo_frontend_public_url('home/courses', $language_code, $query_string);
    }
}

if (!function_exists('youngo_frontend_search_path')) {
    function youngo_frontend_search_path($language_code = null, $query_string = '')
    {
        return youngo_frontend_public_path('home/search', $language_code, $query_string);
    }
}

if (!function_exists('youngo_frontend_search_url')) {
    function youngo_frontend_search_url($language_code = null, $query_string = '')
    {
        return youngo_frontend_public_url('home/search', $language_code, $query_string);
    }
}

if (!function_exists('youngo_frontend_blog_url')) {
    function youngo_frontend_blog_url($language_code = null, $query_string = '')
    {
        return youngo_frontend_public_url('blog', $language_code, $query_string);
    }
}

if (!function_exists('youngo_frontend_blog_detail_url')) {
    function youngo_frontend_blog_detail_url($title, $blog_id, $language_code = null)
    {
        $blog_id = (int) $blog_id;
        $slug = function_exists('slugify') ? slugify($title) : preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower(trim((string) $title)));

        return youngo_frontend_public_url('blog/details/' . rawurlencode($slug) . '/' . $blog_id, $language_code);
    }
}

if (!function_exists('youngo_frontend_contact_url')) {
    function youngo_frontend_contact_url($language_code = null)
    {
        return youngo_frontend_public_url('home/contact', $language_code);
    }
}

if (!function_exists('youngo_frontend_login_url')) {
    function youngo_frontend_login_url($language_code = null)
    {
        return youngo_frontend_public_url('login', $language_code);
    }
}

if (!function_exists('youngo_frontend_forgot_password_url')) {
    function youngo_frontend_forgot_password_url($language_code = null)
    {
        return youngo_frontend_public_url('login/forgot_password_request', $language_code);
    }
}

if (!function_exists('youngo_frontend_sign_up_url')) {
    function youngo_frontend_sign_up_url($language_code = null)
    {
        return youngo_frontend_public_url('sign_up', $language_code);
    }
}

if (!function_exists('youngo_frontend_my_courses_url')) {
    function youngo_frontend_my_courses_url($language_code = null)
    {
        return youngo_frontend_public_url('home/my_courses', $language_code);
    }
}

if (!function_exists('youngo_frontend_my_access_url')) {
    function youngo_frontend_my_access_url($language_code = null)
    {
        return youngo_frontend_public_url('home/my_access', $language_code);
    }
}

if (!function_exists('youngo_frontend_wishlist_url')) {
    function youngo_frontend_wishlist_url($language_code = null)
    {
        return youngo_frontend_public_url('home/my_wishlist', $language_code);
    }
}

if (!function_exists('youngo_frontend_subscriptions_url')) {
    function youngo_frontend_subscriptions_url($language_code = null)
    {
        return youngo_frontend_public_url('subscriptions', $language_code);
    }
}

if (!function_exists('youngo_frontend_html_lang')) {
    function youngo_frontend_html_lang($language_code = null)
    {
        $language_code = $language_code === null ? youngo_frontend_active_language() : youngo_frontend_normalize_language_code($language_code);
        $languages = youngo_frontend_supported_languages();

        return isset($languages[$language_code]['html_lang']) ? $languages[$language_code]['html_lang'] : $languages['english']['html_lang'];
    }
}

if (!function_exists('youngo_frontend_html_dir')) {
    function youngo_frontend_html_dir($language_code = null)
    {
        $language_code = $language_code === null ? youngo_frontend_active_language() : youngo_frontend_normalize_language_code($language_code);
        $languages = youngo_frontend_supported_languages();

        return isset($languages[$language_code]['html_dir']) ? $languages[$language_code]['html_dir'] : $languages['english']['html_dir'];
    }
}

if (!function_exists('youngo_frontend_local_phrase_map')) {
    function youngo_frontend_local_phrase_map()
    {
        return array(
            'english' => array(
                'home' => 'Home',
                'courses' => 'Courses',
                'subscriptions' => 'Subscriptions',
                'blog' => 'Blog',
                'contact' => 'Contact',
                'login' => 'Login',
                'sign_up' => 'Sign up',
                'primary_navigation' => 'Primary navigation',
                'language_switcher' => 'Language switcher',
                'footer_navigation' => 'Footer navigation',
                'course_discovery' => 'Course discovery',
                'explore_youngo_courses' => 'Explore YounGo courses',
                'find_structured,_friendly_learning_paths_for_curious_kids_and_the_families_supporting_them.' => 'Find structured, friendly learning paths for curious kids and the families supporting them.',
                'course_results' => 'Course results',
                'course_available' => 'Course available',
                'courses_available' => 'Courses available',
                'subscription_plans' => 'Subscription plans',
                'family_access_plans' => 'Family access plans',
                'choose_a_learning_plan_for_consistent_youngo_access._online_access_requests_are_not_available_yet.' => 'Choose a learning plan for consistent YounGo access. Online access requests are not available yet.',
                'plan_options' => 'Plan options',
                'plan_available' => 'Plan available',
                'plans_available' => 'Plans available',
                'featured_plan' => 'Featured plan',
                'coming_soon' => 'Coming soon',
                'no_subscription_plans_available_yet' => 'No subscription plans available yet',
                'subscription_plans_will_appear_here_after_they_are_approved_and_made_purchasable.' => 'Subscription plans will appear here after they are approved and made purchasable.',
                'talk_to_us_about_subscriptions' => 'Talk to us about subscriptions',
                'contact_us_to_choose_the_right_starting_point_before_subscriptions_open_online.' => 'Contact us to choose the right starting point before subscriptions open online.',
                'course_pagination' => 'Course pagination',
                'filters' => 'Filters',
                'course_filters' => 'Course filters',
                'reset_filters' => 'Reset filters',
                'reset' => 'Reset',
                'find_a_course' => 'Find a course',
                'search_by_keyword' => 'Search by keyword',
                'search_courses' => 'Search courses',
                'search' => 'Search',
                'categories' => 'Categories',
                'all_categories' => 'All categories',
                'price' => 'Price',
                'all' => 'All',
                'free' => 'Free',
                'paid' => 'Paid',
                'level' => 'Level',
                'beginner' => 'Beginner',
                'intermediate' => 'Intermediate',
                'advanced' => 'Advanced',
                'language' => 'Language',
                'ratings' => 'Ratings',
                'apply_filters' => 'Apply filters',
                'course_catalog' => 'Course catalog',
                'showing_results' => 'Showing results',
                'clear_all_filters' => 'Clear all filters',
                'course_layout' => 'Course layout',
                'grid_view' => 'Grid view',
                'list_view' => 'List view',
                'sort_by' => 'Sort by',
                'newly_published' => 'Newly published',
                'highest_rating' => 'Highest rating',
                'lowest_price' => 'Lowest price',
                'highest_price' => 'Highest price',
                'discounted' => 'Discounted',
                'ages' => 'Ages',
                'lessons' => 'Lessons',
                'hours' => 'Hours',
                'minutes' => 'Minutes',
                'new' => 'New',
                'subscription_not_available_yet' => 'Subscription not available yet',
                'subscription_access' => 'Subscription access',
                'subscribe_to_unlock_this_course' => 'Subscribe to a YounGo plan to unlock this course and all available courses.',
                'included_with_subscription' => 'Included with an active YounGo subscription.',
                'view_subscription_plans' => 'View subscription plans',
                'access_managed_by_school' => 'Access managed by school',
                'subscription_checkout_is_not_available_yet' => 'Subscription access is not available yet',
                'access_for_this_course_is_managed_by_your_school/admin.' => 'Access for this course is managed by your school/admin.',
                'course_not_found' => 'Course not found',
                'try_adjusting_your_search_or_clearing_a_few_filters_to_see_more_courses.' => 'Try adjusting your search or clearing a few filters to see more courses.',
                'view_details' => 'View details',
                'course_details' => 'Course details',
                'start_now' => 'Start now',
                'enroll_now' => 'Enroll now',
                'continue_learning' => 'Continue learning',
                'purchase_history' => 'Purchase history',
                'my_wishlist' => 'My wishlist',
                'my_courses' => 'My courses',
                'my_access' => 'My access',
                'faq' => 'FAQ',
                'blogs' => 'Blogs',
                'latest_articles' => 'Latest articles',
                'helpful_notes_for_families' => 'Helpful notes for families',
                'no_blog_posts_yet' => 'No blog posts yet',
                'practical_articles_and_learning_tips_for_parents_will_appear_here_soon.' => 'Practical articles and learning tips for parents will appear here soon.',
                'contact_us' => 'Contact us',
                'get_in_touch' => 'Get in touch',
                'have_a_question_about_youngo_programs?_we_would_be_happy_to_hear_from_you.' => 'Have a question about YounGo programs? We would be happy to hear from you.',
                'send_us_a_message' => 'Send us a message',
                'email' => 'Email',
                'phone' => 'Phone',
                'address' => 'Address',
                'working_hours' => 'Working hours',
                'follow_us' => 'Follow us',
                'message' => 'Message',
                'name' => 'Name',
                'subject' => 'Subject',
                'back_to_blog' => 'Back to blog',
                'read_more' => 'Read more',
                'contact_us_by_email' => 'Contact us by email',
                'message_preview' => 'Message preview',
                'this_demo_page_displays_contact_details_without_submitting_forms.' => 'This demo page displays contact details without submitting forms.',
                'we_will_share_family_learning_notes_here_as_the_youngo_library_grows.' => 'We will share family learning notes here as the YounGo library grows.',
                'all_articles' => 'All articles',
                'popular_articles' => 'Popular articles',
                'breadcrumb' => 'Breadcrumb',
                'details' => 'Details',
                'guided_learning_for_curious_kids' => 'Guided learning for curious kids',
                'starts' => 'Starts',
                'course_actions' => 'Course actions',
                'preview_this_course' => 'Preview this course',
                'course_description' => 'Course description',
                'learning_goals' => 'Learning goals',
                'what_will_i_learn?' => 'What will I learn?',
                'before_class' => 'Before class',
                'no_curriculum_sections_are_available_yet.' => 'No curriculum sections are available yet.',
                'meet_your_guide' => 'Meet your guide',
                'course_guide' => 'Course guide',
                'trusted_guide' => 'Trusted guide',
                'view_profile' => 'View profile',
                'family_and_learner_feedback' => 'Family and learner feedback',
                'frequently_asked_questions' => 'Frequently asked questions',
                'more_details' => 'More details',
                'additional_information' => 'Additional information',
                'watch_video' => 'Watch video',
                'course_confidence' => 'Course confidence',
                'a_structured_learning_path_with_clear_lessons,_instructor_guidance,_and_progress-friendly_activities.' => 'A structured learning path with clear lessons, instructor guidance, and progress-friendly activities.',
                'share_on_facebook' => 'Share on Facebook',
                'share_on_twitter' => 'Share on Twitter',
                'share_on_whatsapp' => 'Share on WhatsApp',
                'share_on_linkedin' => 'Share on LinkedIn',
                'keep_exploring' => 'Keep exploring',
                'related_courses' => 'Related courses',
                'access_locked' => 'Access locked',
                'write_a_review' => 'Write a review',
                'rating' => 'Rating',
                '1_star_rating' => '1 star rating',
                '2_star_rating' => '2 star rating',
                '3_star_rating' => '3 star rating',
                '4_star_rating' => '4 star rating',
                '5_star_rating' => '5 star rating',
                'review' => 'Review',
                'write_your_comment' => 'Write your comment',
                'submit' => 'Submit',
                'no_reviews_yet.' => 'No reviews yet.',
                'stars' => 'stars',
                'edit' => 'Edit',
                'remove_review' => 'Remove review',
                'remove' => 'Remove',
                'created_by' => 'Created by',
                'reviews' => 'Reviews',
                'enrolled' => 'Enrolled',
                'updated' => 'Updated',
                'course_access' => 'Course access',
                'browse_courses' => 'Browse courses',
                'lectures' => 'Lectures',
                'lifetime' => 'Lifetime',
                'months' => 'Months',
                'yes' => 'Yes',
                'overview' => 'Overview',
                'requirements' => 'Requirements',
                'curriculum' => 'Curriculum',
                'lessons_inside_this_course' => 'Lessons inside this course',
                'preview' => 'Preview',
                'instructor' => 'Instructor',
                'apply_to_become_an_instructor' => 'Apply to become an instructor',
                'structured_lessons' => 'Structured lessons',
                'follow' => 'Follow',
                'unfollow' => 'Unfollow',
                'questions' => 'Questions',
                'close' => 'Close',
                'saved_courses' => 'Saved courses',
                'keep_favorite_youngo_courses_in_one_place_then_return_when_your_child_is_ready_to_start.' => 'Keep favorite YounGo courses in one place, then return when your child is ready to start.',
                'wishlist_summary' => 'Wishlist summary',
                'saved_courses_stay_here_so_you_can_compare_learning_paths_before_access_is_granted.' => 'Saved courses stay here so you can compare learning paths before access is granted.',
                'sign_in_required' => 'Sign in required',
                'log_in_to_view_your_wishlist' => 'Log in to view your wishlist',
                'wishlist_items_are_saved_to_your_learner_account_so_they_stay_available_across_visits.' => 'Wishlist items are saved to your learner account so they stay available across visits.',
                'log_in' => 'Log in',
                'wishlist' => 'Wishlist',
                'courses_you_saved' => 'Courses you saved',
                'explore_more_courses' => 'Explore more courses',
                'remove_from_wishlist' => 'Remove from wishlist',
                'course_added_to_wishlist' => 'Course added to wishlist',
                'course_removed_from_wishlist' => 'Course removed from wishlist',
                'no_saved_courses_yet' => 'No saved courses yet',
                'build_your_child\'s_shortlist' => 'Build your child\'s shortlist',
                'save_interesting_courses_while_browsing,_then_compare_options_before_enrolling.' => 'Save interesting courses while browsing, then compare options before enrolling.',
                'or_continue_with' => 'Or continue with',
                'or_sign_up_with' => 'Or sign up with',
                'account_navigation' => 'Account navigation',
                'signed_in_as' => 'Signed in as',
                'profile' => 'Profile',
                'profile_photo' => 'Profile photo',
                'english' => 'English',
                'expiry_period' => 'Expiry period',
                'certificate' => 'Certificate',
                'sign_in_to_track_access' => 'Sign in to track access',
                'use_a_student_account_to_keep_course_access_and_progress_in_one_place.' => 'Use a student account to keep course access and progress in one place.',
                'admin_access' => 'Admin access',
                'you_can_preview_this_course_as_an_admin.' => 'You can preview this course as an admin.',
                'instructor_access' => 'Instructor access',
                'you_are_assigned_to_this_course_and_can_preview_the_learning_flow.' => 'You are assigned to this course and can preview the learning flow.',
                'your_active_subscription_includes_this_course.' => 'Your active subscription includes this course.',
                'manual_grant_access' => 'Manual grant access',
                'your_school/admin_has_granted_access_to_this_course.' => 'Your school/admin has granted access to this course.',
                'purchased_access' => 'Purchased access',
                'your_account_has_active_purchased_access_to_this_course.' => 'Your account has active purchased access to this course.',
                'access_active' => 'Access active',
                'you_can_continue_this_course_from_your_account.' => 'You can continue this course from your account.',
                'previous_access_has_expired._progress_remains_saved_in_your_account.' => 'Previous access has expired. Progress remains saved in your account.',
                'this_course_is_currently_locked_for_this_account.' => 'This course is currently locked for this account.',
                'subscription_course' => 'Subscription course',
                'your_access_period_is_almost_finished._continue_learning_while_it_is_active.' => 'Your access period is almost finished. Continue learning while it is active.',
                'ready_to_talk_about_the_right_learning_path?' => 'Ready to talk about the right learning path?',
                'send_us_an_email_and_the_youngo_team_will_help_you_choose_a_good_starting_point.' => 'Send us an email and the YounGo team will help you choose a good starting point.',
                'safe_learning_doorway' => 'Safe learning doorway',
                'a_safe_place_to_keep_learning' => 'A safe place to keep learning',
                'pick_up_the_next_lesson_with_confidence.' => 'Pick up the next lesson with confidence.',
                'youngo_keeps_guided_lessons,_creative_projects,_and_progress_moments_together_for_curious_kids_and_the_families_cheering_them_on.' => 'YounGo keeps guided lessons, creative projects, and progress moments together for curious kids and the families cheering them on.',
                'today' => 'Today',
                'creative_project_ready' => 'Creative project ready',
                'parent_view' => 'Parent view',
                'progress_feels_clear' => 'Progress feels clear',
                'guided' => 'Guided',
                'lessons_with_structure' => 'Lessons with structure',
                'creative' => 'Creative',
                'projects_kids_remember' => 'Projects kids remember',
                'calm' => 'Calm',
                'space_parents_can_trust' => 'Space parents can trust',
                'welcome_back' => 'Welcome back',
                'log_in_to_youngo' => 'Log in to YounGo',
                'continue_a_safe,_joyful_learning_journey_built_for_curious_kids_and_confident_parents.' => 'Continue a safe, joyful learning journey built for curious kids and confident parents.',
                'email_address' => 'Email address',
                'enter_your_email' => 'Enter your email',
                'password' => 'Password',
                'show' => 'Show',
                'hide' => 'Hide',
                'show_password' => 'Show password',
                'hide_password' => 'Hide password',
                'forgot_password?' => 'Forgot password?',
                'new_to_youngo?' => 'New to YounGo?',
                'account_help' => 'Account help',
                'forgot_password' => 'Forgot password',
                'enter_your_email_and_we_will_send_the_next_step_to_help_secure_your_account.' => 'Enter your email and we will send the next step to help secure your account.',
                'we_will_use_your_email_to_find_your_account.' => 'We will use your email to find your account.',
                'your_email' => 'Your email',
                'send_request' => 'Send request',
                'back_to_login' => 'Back to login',
                'account_security' => 'Account security',
                'change_password' => 'Change password',
                'change_your_password_to_secure_your_account' => 'Change your password to secure your account',
                'new_password' => 'New password',
                'enter_a_new_password' => 'Enter a new password',
                'confirm_your_new_password' => 'Confirm your new password',
                'retype_your_new_password' => 'Retype your new password',
                'continue' => 'Continue',
                'email_verification' => 'Email verification',
                'enter_your_verification_code_here' => 'Enter your verification code here',
                'verification_code' => 'Verification code',
                'enter_your_verification_code' => 'Enter your verification code',
                'resend_mail' => 'Resend mail',
                'login_confirmation' => 'Login confirmation',
                'let_us_know_that_this_email_address_belongs_to_you' => 'Let us know that this email address belongs to you',
                'enter_the_code_from_the_email_sent_to' => 'Enter the code from the email sent to',
                'enter_the_verification_code' => 'Enter the verification code',
                'new_device_verification_code' => 'New device verification code',
                'resend_verification_code' => 'Resend verification code',
                'sending' => 'Sending',
                'sent' => 'Sent',
                'please_try_again' => 'Please try again',
                'youngo_learning_benefits' => 'YounGo learning benefits',
                'start_with_confidence' => 'Start with confidence',
                'a_joyful_learning_path_for_growing_minds.' => 'A joyful learning path for growing minds.',
                'families_come_to_youngo_for_guided_lessons,_creative_practice,_and_a_calm_space_designed_around_kids_learning_well.' => 'Families come to YounGo for guided lessons, creative practice, and a calm space designed around kids learning well.',
                'step_1' => 'Step 1',
                'choose_a_guided_lesson' => 'Choose a guided lesson',
                'step_2' => 'Step 2',
                'build,_practice,_and_grow' => 'Build, practice, and grow',
                'safe_learning_space' => 'Safe learning space',
                'a_friendly_environment_for_young_learners.' => 'A friendly environment for young learners.',
                'guided_discovery' => 'Guided discovery',
                'lessons_help_kids_move_with_structure.' => 'Lessons help kids move with structure.',
                'creative_confidence' => 'Creative confidence',
                'projects_turn_practice_into_progress.' => 'Projects turn practice into progress.',
                'create_your_account' => 'Create your account',
                'join_youngo' => 'Join YounGo',
                'start_with_guided_lessons,_creative_projects,_and_progress_moments_families_can_feel_good_about.' => 'Start with guided lessons, creative projects, and progress moments families can feel good about.',
                'first_name' => 'First name',
                'last_name' => 'Last name',
                'enter_your_first_name' => 'Enter your first name',
                'enter_your_last_name' => 'Enter your last name',
                'create_password' => 'Create password',
                'enter_your_phone_number' => 'Enter your phone number',
                'document' => 'Document',
                'provide_some_documents_about_your_qualifications' => 'Provide some documents about your qualifications',
                'already_have_an_account?' => 'Already have an account?',
                'account' => 'Account',
                'logout' => 'Logout',
            ),
            'arabic' => array(
                'home' => 'الرئيسية',
                'courses' => 'الكورسات',
                'blog' => 'المدونة',
                'contact' => 'تواصل معنا',
                'login' => 'تسجيل الدخول',
                'sign_up' => 'إنشاء حساب',
                'primary_navigation' => 'التنقل الرئيسي',
                'language_switcher' => 'مبدل اللغة',
                'footer_navigation' => 'روابط التذييل',
                'course_discovery' => 'اكتشاف الكورسات',
                'explore_youngo_courses' => 'استكشف كورسات YounGo',
                'find_structured,_friendly_learning_paths_for_curious_kids_and_the_families_supporting_them.' => 'مسارات تعليمية منظمة وودودة للأطفال الفضوليين وللأسر الداعمة لهم.',
                'course_results' => 'نتائج الكورسات',
                'course_available' => 'كورس متاح',
                'courses_available' => 'كورسات متاحة',
                'course_pagination' => 'تصفح الكورسات',
                'filters' => 'الفلاتر',
                'course_filters' => 'فلاتر الكورسات',
                'reset_filters' => 'إعادة ضبط الفلاتر',
                'reset' => 'إعادة ضبط',
                'find_a_course' => 'ابحث عن كورس',
                'search_by_keyword' => 'البحث بالكلمة المفتاحية',
                'search_courses' => 'ابحث في الكورسات',
                'search' => 'بحث',
                'categories' => 'التصنيفات',
                'all_categories' => 'كل التصنيفات',
                'price' => 'السعر',
                'all' => 'الكل',
                'free' => 'مجاني',
                'paid' => 'مدفوع',
                'level' => 'المستوى',
                'beginner' => 'مبتدئ',
                'intermediate' => 'متوسط',
                'advanced' => 'متقدم',
                'language' => 'اللغة',
                'ratings' => 'التقييمات',
                'apply_filters' => 'تطبيق الفلاتر',
                'course_catalog' => 'قائمة الكورسات',
                'showing_results' => 'عرض النتائج',
                'clear_all_filters' => 'مسح كل الفلاتر',
                'course_layout' => 'طريقة عرض الكورسات',
                'grid_view' => 'عرض شبكي',
                'list_view' => 'عرض قائمة',
                'sort_by' => 'ترتيب حسب',
                'newly_published' => 'الأحدث نشرًا',
                'highest_rating' => 'الأعلى تقييمًا',
                'lowest_price' => 'الأقل سعرًا',
                'highest_price' => 'الأعلى سعرًا',
                'discounted' => 'عليه خصم',
                'ages' => 'الأعمار',
                'lessons' => 'دروس',
                'hours' => 'ساعات',
                'minutes' => 'دقائق',
                'new' => 'جديد',
                'subscription_not_available_yet' => 'الاشتراك غير متاح حاليًا',
                'subscription_access' => 'وصول بالاشتراك',
                'subscribe_to_unlock_this_course' => 'اشترك في إحدى خطط YounGo لفتح هذا الكورس وجميع الكورسات المتاحة.',
                'included_with_subscription' => 'هذا الكورس متاح ضمن اشتراك YounGo نشط.',
                'view_subscription_plans' => 'عرض خطط الاشتراك',
                'access_managed_by_school' => 'الوصول يتم من خلال المدرسة',
                'subscription_checkout_is_not_available_yet' => 'الاشتراك غير متاح حاليًا',
                'access_for_this_course_is_managed_by_your_school/admin.' => 'الوصول لهذا الكورس يتم من خلال إدارة المدرسة.',
                'course_not_found' => 'لم يتم العثور على كورسات',
                'try_adjusting_your_search_or_clearing_a_few_filters_to_see_more_courses.' => 'جرب تعديل البحث أو إزالة بعض الفلاتر لعرض المزيد من الكورسات.',
                'view_details' => 'عرض التفاصيل',
                'course_details' => 'تفاصيل الكورس',
                'start_now' => 'ابدأ الآن',
                'enroll_now' => 'سجل الآن',
                'continue_learning' => 'استكمل التعلم',
                'purchase_history' => 'سجل المشتريات',
                'my_wishlist' => 'قائمتي المفضلة',
                'my_courses' => 'كورساتي',
                'my_access' => 'صلاحيات الوصول',
                'faq' => 'الأسئلة الشائعة',
                'blogs' => 'المدونة',
                'latest_articles' => 'أحدث المقالات',
                'helpful_notes_for_families' => 'مقالات مفيدة للأسر',
                'no_blog_posts_yet' => 'لا توجد مقالات بعد',
                'practical_articles_and_learning_tips_for_parents_will_appear_here_soon.' => 'ستظهر هنا قريبا مقالات ونصائح عملية تساعد الأسر على متابعة رحلة تعلم الأطفال.',
                'contact_us' => 'تواصل معنا',
                'get_in_touch' => 'تواصل معنا',
                'have_a_question_about_youngo_programs?_we_would_be_happy_to_hear_from_you.' => 'لديك سؤال عن برامج YounGo؟ يسعدنا التواصل معك.',
                'send_us_a_message' => 'أرسل لنا رسالة',
                'email' => 'البريد الإلكتروني',
                'phone' => 'الهاتف',
                'address' => 'العنوان',
                'working_hours' => 'ساعات العمل',
                'follow_us' => 'تابعنا',
                'message' => 'الرسالة',
                'name' => 'الاسم',
                'subject' => 'الموضوع',
                'back_to_blog' => 'العودة إلى المدونة',
                'read_more' => 'اقرأ المزيد',
                'contact_us_by_email' => 'تواصل معنا عبر البريد الإلكتروني',
                'message_preview' => 'معاينة الرسالة',
                'this_demo_page_displays_contact_details_without_submitting_forms.' => 'تعرض هذه الصفحة التجريبية بيانات التواصل دون إرسال نماذج.',
                'we_will_share_family_learning_notes_here_as_the_youngo_library_grows.' => 'سنشارك هنا ملاحظات تعليمية للأسر مع نمو مكتبة YounGo.',
                'all_articles' => 'كل المقالات',
                'popular_articles' => 'مقالات شائعة',
                'breadcrumb' => 'مسار الصفحة',
                'details' => 'التفاصيل',
                'guided_learning_for_curious_kids' => 'تعلم موجه للأطفال الفضوليين',
                'starts' => 'يبدأ',
                'course_actions' => 'إجراءات الكورس',
                'preview_this_course' => 'معاينة هذا الكورس',
                'course_description' => 'وصف الكورس',
                'learning_goals' => 'أهداف التعلم',
                'what_will_i_learn?' => 'ماذا سيتعلم طفلي؟',
                'before_class' => 'قبل الحصة',
                'no_curriculum_sections_are_available_yet.' => 'لا توجد أقسام للمنهج متاحة بعد.',
                'meet_your_guide' => 'تعرف على المرشد',
                'course_guide' => 'مرشد الكورس',
                'trusted_guide' => 'مرشد موثوق',
                'view_profile' => 'عرض الملف الشخصي',
                'family_and_learner_feedback' => 'آراء الأسر والمتعلمين',
                'frequently_asked_questions' => 'الأسئلة الشائعة',
                'more_details' => 'تفاصيل إضافية',
                'additional_information' => 'معلومات إضافية',
                'watch_video' => 'مشاهدة الفيديو',
                'course_confidence' => 'ثقة في الكورس',
                'a_structured_learning_path_with_clear_lessons,_instructor_guidance,_and_progress-friendly_activities.' => 'مسار تعلم منظم بدروس واضحة وإرشاد من المدرب وأنشطة تساعد على متابعة التقدم.',
                'share_on_facebook' => 'مشاركة على فيسبوك',
                'share_on_twitter' => 'مشاركة على تويتر',
                'share_on_whatsapp' => 'مشاركة على واتساب',
                'share_on_linkedin' => 'مشاركة على لينكدإن',
                'keep_exploring' => 'تابع الاستكشاف',
                'related_courses' => 'كورسات ذات صلة',
                'access_locked' => 'الوصول مغلق',
                'write_a_review' => 'اكتب تقييما',
                'rating' => 'التقييم',
                '1_star_rating' => 'تقييم نجمة واحدة',
                '2_star_rating' => 'تقييم نجمتين',
                '3_star_rating' => 'تقييم ثلاث نجوم',
                '4_star_rating' => 'تقييم أربع نجوم',
                '5_star_rating' => 'تقييم خمس نجوم',
                'review' => 'التقييم',
                'write_your_comment' => 'اكتب تعليقك',
                'submit' => 'إرسال',
                'no_reviews_yet.' => 'لا توجد تقييمات بعد.',
                'stars' => 'نجوم',
                'edit' => 'تعديل',
                'remove_review' => 'إزالة التقييم',
                'remove' => 'إزالة',
                'created_by' => 'إعداد',
                'reviews' => 'التقييمات',
                'enrolled' => 'مشترك',
                'updated' => 'آخر تحديث',
                'course_access' => 'الوصول للكورس',
                'browse_courses' => 'تصفح الكورسات',
                'lectures' => 'محاضرات',
                'lifetime' => 'مدى الحياة',
                'months' => 'أشهر',
                'yes' => 'نعم',
                'overview' => 'نظرة عامة',
                'requirements' => 'المتطلبات',
                'curriculum' => 'المنهج',
                'lessons_inside_this_course' => 'الدروس داخل هذا الكورس',
                'preview' => 'معاينة',
                'instructor' => 'المدرب',
                'apply_to_become_an_instructor' => 'تقدم لتصبح مدربا',
                'structured_lessons' => 'دروس منظمة',
                'follow' => 'متابعة',
                'unfollow' => 'إلغاء المتابعة',
                'questions' => 'الأسئلة',
                'close' => 'إغلاق',
                'saved_courses' => 'الكورسات المحفوظة',
                'keep_favorite_youngo_courses_in_one_place_then_return_when_your_child_is_ready_to_start.' => 'احتفظ بالكورسات المفضلة في مكان واحد، ثم عد إليها عندما يكون طفلك جاهزا للبدء.',
                'wishlist_summary' => 'ملخص المفضلة',
                'saved_courses_stay_here_so_you_can_compare_learning_paths_before_access_is_granted.' => 'تظهر الكورسات المحفوظة هنا لتتمكن من مقارنة مسارات التعلم قبل تفعيل الوصول.',
                'sign_in_required' => 'تسجيل الدخول مطلوب',
                'log_in_to_view_your_wishlist' => 'سجل الدخول لعرض قائمتك المفضلة',
                'wishlist_items_are_saved_to_your_learner_account_so_they_stay_available_across_visits.' => 'يتم حفظ عناصر المفضلة في حساب المتعلم لتبقى متاحة في الزيارات القادمة.',
                'log_in' => 'تسجيل الدخول',
                'wishlist' => 'المفضلة',
                'courses_you_saved' => 'الكورسات التي حفظتها',
                'explore_more_courses' => 'استكشف المزيد من الكورسات',
                'remove_from_wishlist' => 'إزالة من المفضلة',
                'course_added_to_wishlist' => 'تمت إضافة الكورس إلى المفضلة',
                'course_removed_from_wishlist' => 'تمت إزالة الكورس من المفضلة',
                'no_saved_courses_yet' => 'لا توجد كورسات محفوظة بعد',
                'build_your_child\'s_shortlist' => 'كوّن قائمة مختصرة لطفلك',
                'save_interesting_courses_while_browsing,_then_compare_options_before_enrolling.' => 'احفظ الكورسات التي تهمك أثناء التصفح، ثم قارن الخيارات قبل التسجيل.',
                'or_continue_with' => 'أو تابع باستخدام',
                'or_sign_up_with' => 'أو سجل باستخدام',
                'account_navigation' => 'تنقل الحساب',
                'signed_in_as' => 'تم تسجيل الدخول باسم',
                'profile' => 'الملف الشخصي',
                'profile_photo' => 'صورة الملف الشخصي',
                'english' => 'الإنجليزية',
                'expiry_period' => 'مدة الوصول',
                'certificate' => 'الشهادة',
                'sign_in_to_track_access' => 'سجل الدخول لمتابعة الوصول',
                'use_a_student_account_to_keep_course_access_and_progress_in_one_place.' => 'استخدم حساب الطالب لحفظ الوصول والتقدم في مكان واحد.',
                'admin_access' => 'وصول المسؤول',
                'you_can_preview_this_course_as_an_admin.' => 'يمكنك معاينة هذا الكورس كمسؤول.',
                'instructor_access' => 'وصول المدرب',
                'you_are_assigned_to_this_course_and_can_preview_the_learning_flow.' => 'أنت معين على هذا الكورس ويمكنك معاينة مسار التعلم.',
                'your_active_subscription_includes_this_course.' => 'اشتراكك النشط يشمل هذا الكورس.',
                'manual_grant_access' => 'وصول مضاف يدويا',
                'your_school/admin_has_granted_access_to_this_course.' => 'منحتك الإدارة وصولا لهذا الكورس.',
                'purchased_access' => 'وصول مشتريات',
                'your_account_has_active_purchased_access_to_this_course.' => 'يحتوي حسابك على وصول شراء نشط لهذا الكورس.',
                'access_active' => 'الوصول نشط',
                'you_can_continue_this_course_from_your_account.' => 'يمكنك متابعة هذا الكورس من حسابك.',
                'previous_access_has_expired._progress_remains_saved_in_your_account.' => 'انتهت مدة الوصول السابقة، ويظل التقدم محفوظا في حسابك.',
                'this_course_is_currently_locked_for_this_account.' => 'هذا الكورس مغلق حاليا لهذا الحساب.',
                'subscription_course' => 'كورس بالاشتراك',
                'your_access_period_is_almost_finished._continue_learning_while_it_is_active.' => 'مدة الوصول أوشكت على الانتهاء. تابع التعلم أثناء تفعيلها.',
                'ready_to_talk_about_the_right_learning_path?' => 'هل تحتاج مساعدة في اختيار مسار التعلم المناسب؟',
                'send_us_an_email_and_the_youngo_team_will_help_you_choose_a_good_starting_point.' => 'راسلنا عبر البريد الإلكتروني وسيساعدك فريق YounGo في اختيار نقطة بداية مناسبة.',
                'safe_learning_doorway' => 'بوابة تعلم آمنة',
                'a_safe_place_to_keep_learning' => 'مساحة آمنة لمواصلة التعلم',
                'pick_up_the_next_lesson_with_confidence.' => 'تابع الدرس التالي بثقة.',
                'youngo_keeps_guided_lessons,_creative_projects,_and_progress_moments_together_for_curious_kids_and_the_families_cheering_them_on.' => 'يجمع YounGo الدروس الموجهة والمشروعات الإبداعية ولحظات التقدم للأطفال الفضوليين والأسر الداعمة لهم.',
                'today' => 'اليوم',
                'creative_project_ready' => 'مشروع إبداعي جاهز',
                'parent_view' => 'رؤية الأهل',
                'progress_feels_clear' => 'التقدم واضح',
                'guided' => 'موجه',
                'lessons_with_structure' => 'دروس منظمة',
                'creative' => 'إبداعي',
                'projects_kids_remember' => 'مشروعات يتذكرها الأطفال',
                'calm' => 'هادئ',
                'space_parents_can_trust' => 'مساحة يثق بها الأهل',
                'welcome_back' => 'مرحبا بعودتك',
                'log_in_to_youngo' => 'تسجيل الدخول إلى YounGo',
                'continue_a_safe,_joyful_learning_journey_built_for_curious_kids_and_confident_parents.' => 'تابع رحلة تعلم آمنة وممتعة مصممة للأطفال الفضوليين والأسر الواثقة.',
                'email_address' => 'البريد الإلكتروني',
                'enter_your_email' => 'أدخل بريدك الإلكتروني',
                'password' => 'كلمة المرور',
                'show' => 'إظهار',
                'hide' => 'إخفاء',
                'show_password' => 'إظهار كلمة المرور',
                'hide_password' => 'إخفاء كلمة المرور',
                'forgot_password?' => 'هل نسيت كلمة المرور؟',
                'new_to_youngo?' => 'جديد في YounGo؟',
                'account_help' => 'مساعدة الحساب',
                'forgot_password' => 'نسيت كلمة المرور',
                'enter_your_email_and_we_will_send_the_next_step_to_help_secure_your_account.' => 'أدخل بريدك الإلكتروني وسنرسل الخطوة التالية لمساعدتك في تأمين حسابك.',
                'we_will_use_your_email_to_find_your_account.' => 'سنستخدم بريدك الإلكتروني للعثور على حسابك.',
                'your_email' => 'بريدك الإلكتروني',
                'send_request' => 'إرسال الطلب',
                'back_to_login' => 'العودة إلى تسجيل الدخول',
                'account_security' => 'أمان الحساب',
                'change_password' => 'تغيير كلمة المرور',
                'change_your_password_to_secure_your_account' => 'غيّر كلمة المرور لتأمين حسابك',
                'new_password' => 'كلمة المرور الجديدة',
                'enter_a_new_password' => 'أدخل كلمة مرور جديدة',
                'confirm_your_new_password' => 'أكد كلمة المرور الجديدة',
                'retype_your_new_password' => 'أعد كتابة كلمة المرور الجديدة',
                'continue' => 'متابعة',
                'email_verification' => 'تأكيد البريد الإلكتروني',
                'enter_your_verification_code_here' => 'أدخل رمز التحقق هنا',
                'verification_code' => 'رمز التحقق',
                'enter_your_verification_code' => 'أدخل رمز التحقق',
                'resend_mail' => 'إعادة إرسال البريد',
                'login_confirmation' => 'تأكيد تسجيل الدخول',
                'let_us_know_that_this_email_address_belongs_to_you' => 'ساعدنا على التأكد من أن هذا البريد الإلكتروني يخصك',
                'enter_the_code_from_the_email_sent_to' => 'أدخل الرمز من البريد المرسل إلى',
                'enter_the_verification_code' => 'أدخل رمز التحقق',
                'new_device_verification_code' => 'رمز تحقق لجهاز جديد',
                'resend_verification_code' => 'إعادة إرسال رمز التحقق',
                'sending' => 'جار الإرسال',
                'sent' => 'تم الإرسال',
                'please_try_again' => 'يرجى المحاولة مرة أخرى',
                'youngo_learning_benefits' => 'مزايا التعلم في YounGo',
                'start_with_confidence' => 'ابدأ بثقة',
                'a_joyful_learning_path_for_growing_minds.' => 'مسار تعلم ممتع لعقول تنمو.',
                'families_come_to_youngo_for_guided_lessons,_creative_practice,_and_a_calm_space_designed_around_kids_learning_well.' => 'تأتي الأسر إلى YounGo من أجل دروس موجهة وتدريب إبداعي ومساحة هادئة مصممة لتعلم الأطفال بشكل جيد.',
                'step_1' => 'الخطوة 1',
                'choose_a_guided_lesson' => 'اختر درسا موجها',
                'step_2' => 'الخطوة 2',
                'build,_practice,_and_grow' => 'اصنع وتدرب وانم',
                'safe_learning_space' => 'مساحة تعلم آمنة',
                'a_friendly_environment_for_young_learners.' => 'بيئة ودودة للمتعلمين الصغار.',
                'guided_discovery' => 'اكتشاف موجه',
                'lessons_help_kids_move_with_structure.' => 'تساعد الدروس الأطفال على التقدم بتنظيم.',
                'creative_confidence' => 'ثقة إبداعية',
                'projects_turn_practice_into_progress.' => 'تحول المشروعات التدريب إلى تقدم.',
                'create_your_account' => 'أنشئ حسابك',
                'join_youngo' => 'انضم إلى YounGo',
                'start_with_guided_lessons,_creative_projects,_and_progress_moments_families_can_feel_good_about.' => 'ابدأ بدروس موجهة ومشروعات إبداعية ولحظات تقدم تشعر الأسر بالاطمئنان تجاهها.',
                'first_name' => 'الاسم الأول',
                'last_name' => 'اسم العائلة',
                'enter_your_first_name' => 'أدخل الاسم الأول',
                'enter_your_last_name' => 'أدخل اسم العائلة',
                'create_password' => 'إنشاء كلمة مرور',
                'enter_your_phone_number' => 'أدخل رقم هاتفك',
                'document' => 'المستند',
                'provide_some_documents_about_your_qualifications' => 'أرفق بعض المستندات عن مؤهلاتك',
                'already_have_an_account?' => 'لديك حساب بالفعل؟',
                'account' => 'الحساب',
                'logout' => 'تسجيل الخروج',
            ),
        );
    }
}

if (!function_exists('youngo_frontend_phrase_value_is_clean')) {
    function youngo_frontend_phrase_value_is_clean($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }

        if (preg_match('/\?{3,}/u', $value)) {
            return false;
        }

        $question_count = substr_count($value, '?');
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        if ($question_count >= 2 && $length > 0 && ($question_count / $length) >= 0.2) {
            return false;
        }

        if (preg_match('/(?:\x{00D8}|\x{00D9})/u', $value)) {
            return false;
        }

        if (preg_match('/(?:�|Ã|Â|Ø|Ù|Ð|Ñ)/u', $value)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('youngo_frontend_phrase_value_has_arabic')) {
    function youngo_frontend_phrase_value_has_arabic($value)
    {
        return preg_match('/\p{Arabic}/u', (string) $value) === 1;
    }
}

if (!function_exists('youngo_frontend_local_phrase_value')) {
    function youngo_frontend_local_phrase_value($phrase_key, $language_code)
    {
        $maps = youngo_frontend_local_phrase_map();
        $language_code = youngo_frontend_normalize_language_code($language_code);
        $language_code = $language_code === 'arabic' ? 'arabic' : 'english';
        $phrase_key = trim((string) $phrase_key);
        $lookup_key = strtolower($phrase_key);

        foreach (array($phrase_key, $lookup_key) as $candidate) {
            if (isset($maps[$language_code][$candidate]) && youngo_frontend_phrase_value_is_clean($maps[$language_code][$candidate])) {
                return $maps[$language_code][$candidate];
            }
        }

        return null;
    }
}

if (!function_exists('youngo_frontend_local_text_map')) {
    function youngo_frontend_local_text_map()
    {
        return array(
            'Safe creative learning for kids' => 'تعلم إبداعي آمن للأطفال',
            'Safe, creative learning for kids' => 'تعلم إبداعي آمن للأطفال',
            'A brighter way for children to learn, create, and grow' => 'طريقة أكثر إشراقا ليتعلم الأطفال ويبدعوا وينموا',
            'YounGo brings guided online courses, creative projects, and parent-friendly structure into one calm learning space built for curious young minds.' => 'تقدم YounGo كورسات أونلاين موجهة ومشروعات إبداعية وتنظيما مناسبا للأهل داخل مساحة تعلم هادئة للأطفال الفضوليين.',
            'Explore courses' => 'استكشف الكورسات',
            'Explore Courses' => 'استكشف الكورسات',
            'Talk to us' => 'تواصل معنا',
            'Talk to Us' => 'تواصل معنا',
            'Parent-trusted topics' => 'موضوعات يثق بها الأهل',
            'Guided creative projects' => 'مشروعات إبداعية موجهة',
            'Flexible after-school learning' => 'تعلم مرن بعد المدرسة',
            'learning paths' => 'مسارات تعلم',
            'age-friendly range' => 'فئات عمرية مناسبة',
            'focused on kids' => 'مخصص للأطفال',
            'Today' => 'اليوم',
            'Build a project' => 'ابن مشروعا',
            'For families' => 'للأسر',
            'Clear paths' => 'مسارات واضحة',
            'YounGo highlights' => 'أبرز مزايا YounGo',
            'YounGo learning stats' => 'إحصاءات تعلم YounGo',
            'Learning paths' => 'مسارات التعلم',
            'Explore learning paths' => 'استكشف مسارات التعلم',
            'Help your child choose a topic that feels exciting today and useful tomorrow.' => 'ساعد طفلك على اختيار موضوع ممتع اليوم ومفيد غدا.',
            'View all learning paths' => 'عرض كل مسارات التعلم',
            'View all courses' => 'عرض كل الكورسات',
            'View all Courses' => 'عرض كل الكورسات',
            'Explore by category' => 'استكشف حسب التصنيف',
            'Find the right starting point for your child\'s interests.' => 'اعثر على نقطة بداية مناسبة لاهتمامات طفلك.',
            'Explore a focused learning path.' => 'استكشف مسار تعلم مركز.',
            'Learning path' => 'مسار تعلم',
            'course' => 'كورس',
            'courses' => 'كورسات',
            'Featured courses' => 'كورسات مميزة',
            'Featured courses for curious learners' => 'كورسات مميزة للمتعلمين الفضوليين',
            'Course journeys with clear outcomes, warm visuals, and practical skill-building for young learners.' => 'رحلات تعلم بنتائج واضحة وواجهة ودودة وبناء مهارات عملي للمتعلمين الصغار.',
            'Popular courses for young learners' => 'كورسات شائعة للمتعلمين الصغار',
            'Start with calm, structured learning experiences that feel playful and useful.' => 'ابدأ بتجارب تعلم هادئة ومنظمة وممتعة ومفيدة.',
            'YounGo Course' => 'كورس YounGo',
            'A calm, structured learning experience for young learners.' => 'تجربة تعلم هادئة ومنظمة للمتعلمين الصغار.',
            'Free preview' => 'معاينة مجانية',
            'Quizzes' => 'اختبارات',
            'Expiry period' => 'مدة الوصول',
            'Certificate' => 'الشهادة',
            'New course' => 'كورس جديد',
            'View details' => 'عرض التفاصيل',
            'See all courses' => 'عرض كل الكورسات',
            'See All Courses' => 'عرض كل الكورسات',
            'Why families choose YounGo' => 'لماذا تختار الأسر YounGo',
            'Trusted learning that still feels joyful' => 'تعلم موثوق يظل ممتعا',
            'A premium learning space that balances child-friendly creativity with the structure parents expect.' => 'مساحة تعلم مميزة توازن بين الإبداع المناسب للأطفال والتنظيم الذي يتوقعه الأهل.',
            'Built to feel safe for parents, friendly for kids, and practical for everyday learning.' => 'مصممة لتكون آمنة للأهل وودودة للأطفال وعملية للتعلم اليومي.',
            'Parent view' => 'نظرة الأهل',
            'Clear courses, calm navigation, and child-friendly topics.' => 'كورسات واضحة وتصفح هادئ وموضوعات مناسبة للأطفال.',
            'YounGo is designed to make course discovery feel reassuring before a family ever opens a lesson.' => 'صممت YounGo لتجعل اكتشاف الكورسات مطمئنا قبل أن تفتح الأسرة أي درس.',
            'Safe' => 'آمن',
            'Guide' => 'إرشاد',
            'Make' => 'اصنع',
            'Grow' => 'انمو',
            'A focused place to learn' => 'مكان مركز للتعلم',
            'YounGo keeps the experience centered on useful topics, calm navigation, and age-aware learning moments.' => 'تحافظ YounGo على تجربة تركز على موضوعات مفيدة وتصفح هادئ ولحظات تعلم مناسبة للعمر.',
            'Guided steps, not random browsing' => 'خطوات موجهة وليست تصفحا عشوائيا',
            'Courses are shaped as clear journeys so kids know what to do next and parents can understand the path.' => 'تصمم الكورسات كرحلات واضحة ليعرف الأطفال الخطوة التالية ويفهم الأهل المسار.',
            'Creative work with real outcomes' => 'عمل إبداعي بنتائج واضحة',
            'Projects, prompts, and practice activities help children turn curiosity into visible progress.' => 'تساعد المشروعات والأنشطة التطبيقية الأطفال على تحويل الفضول إلى تقدم ملموس.',
            'Confidence families can see' => 'ثقة تلاحظها الأسرة',
            'Friendly pacing and achievable challenges help kids feel capable while building useful habits.' => 'إيقاع ودود وتحديات قابلة للتحقيق تساعد الأطفال على الشعور بالقدرة وبناء عادات مفيدة.',
            'About YounGo' => 'عن YounGo',
            'Built for the way kids discover new skills' => 'مصممة لطريقة اكتشاف الأطفال للمهارات الجديدة',
            'Learning that feels friendly, safe, and exciting' => 'تعلم ودود وآمن وممتع',
            'YounGo exists to make online learning feel warmer, clearer, and more meaningful for families. Children get playful lessons and creative challenges. Parents get a platform that feels organized, trustworthy, and easy to understand.' => 'توجد YounGo لتجعل التعلم أونلاين أكثر دفئا ووضوحا ومعنى للأسر. يحصل الأطفال على دروس ممتعة وتحديات إبداعية، ويحصل الأهل على منصة منظمة وموثوقة وسهلة الفهم.',
            'YounGo helps children explore new skills through a modern learning experience built for curiosity, confidence, and steady growth.' => 'تساعد YounGo الأطفال على استكشاف مهارات جديدة من خلال تجربة تعلم حديثة مبنية على الفضول والثقة والنمو المتدرج.',
            'Short lessons designed for busy family routines' => 'دروس قصيرة تناسب يوم الأسرة المزدحم',
            'Creative projects that help kids show what they learned' => 'مشروعات إبداعية تساعد الأطفال على إظهار ما تعلموه',
            'Friendly course paths parents can explain in seconds' => 'مسارات كورسات ودودة يستطيع الأهل شرحها بسرعة',
            'short focused lessons' => 'دروس قصيرة ومركزة',
            'watch, make, reflect' => 'شاهد، اصنع، فكر',
            '15 min' => '15 دقيقة',
            '3 steps' => '3 خطوات',
            'Testimonials' => 'آراء الأسر',
            'What parents want from YounGo' => 'ما يريده الأهل من YounGo',
            'Happy parents and kids' => 'أهل وأطفال سعداء',
            'Family-focused learning should feel clear, safe, and exciting from the very first visit.' => 'يجب أن يكون التعلم الموجه للأسرة واضحا وآمنا وممتعا منذ الزيارة الأولى.',
            'What families notice when learning feels safe, structured, and joyful.' => 'ما تلاحظه الأسر عندما يكون التعلم آمنا ومنظما وممتعا.',
            'parent confidence' => 'ثقة الأهل',
            'The course cards make it easy to choose something my child will actually enjoy, and the tone feels safe for families.' => 'تجعل بطاقات الكورسات اختيار ما سيستمتع به طفلي أسهل، والأسلوب يبدو آمنا للأسر.',
            'It feels playful without becoming messy. I can see the learning path before my child starts.' => 'تبدو التجربة ممتعة دون فوضى. أستطيع رؤية مسار التعلم قبل أن يبدأ طفلي.',
            'Parent of a young learner' => 'ولي أمر لمتعلم صغير',
            'Parent reviewer' => 'ولي أمر مراجع',
            'YounGo parent' => 'ولي أمر من YounGo',
            'Parent' => 'ولي أمر',
            'Parent questions' => 'أسئلة الأهل',
            'Questions parents ask before starting' => 'أسئلة يطرحها الأهل قبل البدء',
            'Questions parents often ask' => 'أسئلة شائعة من الأهل',
            'Clear answers about safety, course fit, and how YounGo supports learning at home.' => 'إجابات واضحة عن الأمان وملاءمة الكورس وكيف تدعم YounGo التعلم في المنزل.',
            'Quick answers before your child starts learning.' => 'إجابات سريعة قبل أن يبدأ طفلك التعلم.',
            'Need help choosing?' => 'تحتاج مساعدة في الاختيار؟',
            'Send a message and the YounGo team can guide families to a good starting point.' => 'أرسل رسالة ويمكن لفريق YounGo توجيه الأسر إلى نقطة بداية مناسبة.',
            'Contact support' => 'تواصل مع الدعم',
            'Visit support' => 'زيارة الدعم',
            'How does YounGo work?' => 'كيف تعمل YounGo؟',
            'YounGo provides safe, structured learning for kids.' => 'تقدم YounGo تعلما آمنا ومنظما للأطفال.',
            'Is YounGo designed specifically for children?' => 'هل صممت YounGo خصيصا للأطفال؟',
            'Yes. The homepage, course discovery, and content tone are shaped around young learners and parent trust.' => 'نعم. الصفحة الرئيسية واكتشاف الكورسات وأسلوب المحتوى مصممة حول المتعلمين الصغار وثقة الأهل.',
            'What can my child learn on YounGo?' => 'ماذا يمكن لطفلي أن يتعلم على YounGo؟',
            'YounGo can present coding, science, art, reading, math, and life-skill courses using the existing LMS course catalog.' => 'يمكن لـ YounGo عرض كورسات في البرمجة والعلوم والفن والقراءة والرياضيات ومهارات الحياة باستخدام كتالوج الكورسات الحالي.',
            'Can we start with a small learning commitment?' => 'هل يمكننا البدء بالتزام تعليمي بسيط؟',
            'Yes. The experience is designed around short, focused lessons and clear course paths that fit after-school routines.' => 'نعم. صممت التجربة حول دروس قصيرة ومركزة ومسارات كورسات واضحة تناسب ما بعد المدرسة.',
            'Who manages the homepage content?' => 'من يدير محتوى الصفحة الرئيسية؟',
            'Admins can manage text, images, CTAs, selected courses, selected categories, visibility, and order from the YounGo homepage manager.' => 'يمكن للمسؤولين إدارة النصوص والصور وأزرار الدعوة والكورسات والتصنيفات المختارة والظهور والترتيب من مدير صفحة YounGo الرئيسية.',
            'Start learning' => 'ابدأ التعلم',
            'Ready to start your child\'s learning journey?' => 'هل أنت مستعد لبدء رحلة تعلم طفلك؟',
            'Give your child a learning space that feels safe, creative, and worth returning to' => 'امنح طفلك مساحة تعلم آمنة وإبداعية تستحق العودة إليها',
            'Explore safe, creative, and engaging courses built for young learners and the families supporting them.' => 'استكشف كورسات آمنة وإبداعية وجذابة مصممة للمتعلمين الصغار وللأسر الداعمة لهم.',
            'Explore YounGo courses and help your child start with a topic that matches their curiosity.' => 'استكشف كورسات YounGo وساعد طفلك على البدء بموضوع يناسب فضوله.',
            'Contact us' => 'تواصل معنا',
            'Contact Us' => 'تواصل معنا',
            'Read more' => 'اقرأ المزيد',
            'Latest from YounGo' => 'أحدث أخبار YounGo',
            'Helpful notes for families' => 'ملاحظات مفيدة للأسر',
            'Tips, updates, and learning ideas for parents.' => 'نصائح وتحديثات وأفكار تعلم للأهل.',
            'Parenting Tips' => 'نصائح للأهل',
            'Simple ways to keep kids engaged' => 'طرق بسيطة للحفاظ على تفاعل الأطفال',
            'Short ideas for turning everyday moments into useful learning routines.' => 'أفكار قصيرة لتحويل اللحظات اليومية إلى عادات تعلم مفيدة.',
            'Platform News' => 'أخبار المنصة',
            'Creative coding starts with curiosity' => 'تبدأ البرمجة الإبداعية بالفضول',
            'A parent-friendly look at how kids can begin building logic and confidence.' => 'نظرة مناسبة للأهل حول كيف يبدأ الأطفال بناء المنطق والثقة.',
            'Learning Ideas' => 'أفكار للتعلم',
            'Safe science activities at home' => 'أنشطة علوم آمنة في المنزل',
            'Easy experiments and guided questions that make discovery feel approachable.' => 'تجارب سهلة وأسئلة موجهة تجعل الاكتشاف قريبا وممتعا.',
            'Learning ideas and updates for parents.' => 'أفكار وتحديثات تعليمية للأهل.',
            'YounGo blog preview' => 'معاينة مدونة YounGo',
            'Safe, joyful online learning for curious kids and confident families.' => 'تعلم أونلاين آمن وممتع للأطفال الفضوليين والأسر الواثقة.',
        );
    }
}

if (!function_exists('youngo_frontend_text')) {
    function youngo_frontend_text($text, $language_code = null)
    {
        $text = (string) $text;
        $language_code = $language_code === null ? youngo_frontend_active_language() : youngo_frontend_normalize_language_code($language_code);
        if ($language_code !== 'arabic') {
            return $text;
        }

        if (preg_match('/^Ages\s+(.+)$/i', $text, $age_matches)) {
            return 'الأعمار ' . $age_matches[1];
        }

        if (preg_match('/^([0-9]+)\s+courses?$/i', $text, $course_matches)) {
            return $course_matches[1] . ' ' . ((int) $course_matches[1] === 1 ? 'كورس' : 'كورسات');
        }

        if (preg_match('/^([0-9.]+)\s+rating$/i', $text, $rating_matches)) {
            return $rating_matches[1] . ' تقييم';
        }

        $map = youngo_frontend_local_text_map();
        return isset($map[$text]) ? $map[$text] : $text;
    }
}

if (!function_exists('youngo_frontend_homepage_localize_content')) {
    function youngo_frontend_homepage_localize_content($value, $language_code = null, $field_key = '')
    {
        if (is_array($value)) {
            $localized = array();
            foreach ($value as $key => $item) {
                $localized[$key] = youngo_frontend_homepage_localize_content($item, $language_code, (string) $key);
            }
            return $localized;
        }

        if (!is_string($value)) {
            return $value;
        }

        if (in_array($field_key, array('url', 'image', 'icon_key', 'source_type'), true)) {
            return $value;
        }

        if (preg_match('#^(?:assets/|https?://|/home/|home/|/blog|blog|[a-z0-9_-]+\.(?:png|jpe?g|webp|svg|gif))#i', $value)) {
            return $value;
        }

        return youngo_frontend_text($value, $language_code);
    }
}

if (!function_exists('youngo_frontend_phrase_language_code')) {
    function youngo_frontend_phrase_language_code($language_code = null)
    {
        if ($language_code === null || trim((string) $language_code) === '') {
            return youngo_frontend_active_language();
        }

        $language_code = strtolower(trim((string) $language_code));

        if ($language_code === 'en' || $language_code === 'eng' || $language_code === 'english') {
            return 'english';
        }

        if ($language_code === 'ar' || $language_code === 'ara' || $language_code === 'arabic') {
            return 'arabic';
        }

        return null;
    }
}

if (!function_exists('youngo_frontend_phrase_normalize_key')) {
    function youngo_frontend_phrase_normalize_key($phrase_key)
    {
        $phrase_key = strtolower(preg_replace('/\s+/', '_', trim((string) $phrase_key)));
        $phrase_key = preg_replace('/_+/', '_', $phrase_key);

        return trim($phrase_key, '_');
    }
}

if (!function_exists('youngo_frontend_phrase_db_row')) {
    function youngo_frontend_phrase_db_row($phrase_key)
    {
        static $phrase_cache = array();
        static $language_table_ready = null;

        $phrase_key = trim((string) $phrase_key);
        $normalized_key = youngo_frontend_phrase_normalize_key($phrase_key);
        if ($normalized_key === '') {
            return null;
        }

        if (array_key_exists($normalized_key, $phrase_cache)) {
            return $phrase_cache[$normalized_key];
        }

        $phrase_cache[$normalized_key] = null;

        if (!function_exists('get_instance')) {
            return null;
        }

        $CI = &get_instance();
        if (!isset($CI->db)) {
            return null;
        }

        if ($language_table_ready === null) {
            $language_table_ready = $CI->db->table_exists('language')
                && $CI->db->field_exists('english', 'language')
                && $CI->db->field_exists('arabic', 'language');
        }

        if (!$language_table_ready) {
            return null;
        }

        $candidate_keys = array_values(array_unique(array_filter(array($phrase_key, $normalized_key), 'strlen')));
        $query = $CI->db
            ->select('phrase, english, arabic')
            ->from('language')
            ->where_in('phrase', $candidate_keys)
            ->limit(count($candidate_keys))
            ->get();

        if (!$query || $query->num_rows() < 1) {
            return null;
        }

        $rows = $query->result_array();
        foreach ($rows as $row) {
            if (isset($row['phrase']) && $row['phrase'] === $phrase_key) {
                $phrase_cache[$normalized_key] = $row;
                return $row;
            }
        }

        foreach ($rows as $row) {
            if (isset($row['phrase']) && $row['phrase'] === $normalized_key) {
                $phrase_cache[$normalized_key] = $row;
                return $row;
            }
        }

        return null;
    }
}

if (!function_exists('youngo_frontend_account_arabic_phrase')) {
    function youngo_frontend_account_arabic_phrase($phrase_key)
    {
        static $labels = array(
            'home' => 'الرئيسية', 'courses' => 'الدورات', 'subscriptions' => 'الاشتراكات',
            'blog' => 'المدونة', 'contact' => 'تواصل معنا', 'breadcrumb' => 'مسار التنقل',
            'account_navigation' => 'قائمة الحساب', 'signed_in_as' => 'مسجل الدخول باسم',
            'my_courses' => 'كورساتي', 'my_access' => 'الوصول الخاص بي', 'my_wishlist' => 'المفضلة',
            'purchase_history' => 'سجل المشتريات', 'profile' => 'الملف الشخصي',
            'profile_photo' => 'الصورة الشخصية', 'account' => 'الحساب', 'logout' => 'تسجيل الخروج',
            'learner_space' => 'مساحة المتعلم', 'my_courses' => 'كورساتي',
            'continue_courses_from_enrolments_school-granted_access_and_youngo_course_access_in_one_place.' => 'تابع دوراتك ووصولك التعليمي من مكان واحد.',
            'student_profile' => 'ملف المتعلم', 'learning_summary' => 'ملخص التعلم',
            'active_courses' => 'الدورات النشطة', 'in_progress' => 'قيد التقدم', 'completed' => 'مكتملة',
            'subscriptions' => 'الاشتراكات', 'subscription_active' => 'الاشتراك نشط',
            'your_subscription_access_is_active' => 'اشتراكك نشط', 'view_my_access' => 'عرض الوصول الخاص بي',
            'continue_learning' => 'تابع التعلم', 'your_active_learning_access' => 'وصولك التعليمي النشط',
            'explore_more_courses' => 'استكشف دورات أخرى', 'active' => 'نشط', 'course' => 'دورة',
            'course_progress' => 'تقدم الدورة', 'lessons' => 'دروس', 'quizzes' => 'اختبارات',
            'access_active' => 'الوصول نشط', 'access' => 'الوصول', 'access_until' => 'الوصول حتى',
            'lifetime_access' => 'وصول مدى الحياة', 'continue' => 'متابعة التعلم',
            'start_now' => 'ابدأ الآن', 'course_details' => 'تفاصيل الدورة',
            'no_active_courses_yet' => 'لا توجد دورات نشطة بعد', 'start_a_youngo_learning_path' => 'ابدأ مسارك التعليمي مع YounGo',
            'when_a_course_is_enrolled_or_school-granted_it_will_appear_here_with_active_lesson_access.' => 'ستظهر هنا الدورات المسجل بها أو الممنوحة من المدرسة مع إمكانية الوصول إلى الدروس.',
            'browse_courses' => 'تصفح الدورات', 'access_summary' => 'ملخص الوصول',
            'learning_access_for' => 'الوصول التعليمي لـ', 'browse_included_courses' => 'تصفح الدورات المتاحة',
            'access_counts' => 'إحصائيات الوصول', 'school_grants' => 'منح المدرسة', 'enrolled' => 'مسجل بها',
            'subscription_status' => 'حالة الاشتراك', 'subscription_access' => 'وصول الاشتراك',
            'subscription_plan' => 'خطة الاشتراك', 'days' => 'يوم', 'until' => 'حتى',
            'checkout_and_renewal_actions_are_not_available_yet.' => 'إجراءات الدفع والتجديد غير متاحة حاليًا.',
            'no_active_subscription' => 'لا يوجد اشتراك نشط', 'subscription_inactive' => 'الاشتراك غير نشط',
            'no_subscription_yet' => 'لا يوجد اشتراك بعد', 'no_active_subscription_access' => 'لا يوجد وصول لاشتراك نشط',
            'active_course_access' => 'الوصول إلى الدورات النشطة', 'open_my_courses' => 'فتح كورساتي',
            'no_active_course_access' => 'لا يوجد وصول إلى دورات نشطة', 'courses_will_appear_after_access_is_granted' => 'ستظهر الدورات بعد منح الوصول',
            'profile_info' => 'بيانات الملف الشخصي', 'learner_account' => 'حساب المتعلم',
            'keep_learner_details_current_while_youngo_continues_using_the_existing_academy_lms_account_system.' => 'حافظ على تحديث بيانات المتعلم.',
            'first_name' => 'الاسم الأول', 'last_name' => 'اسم العائلة', 'short_title_about_yourself' => 'نبذة قصيرة عنك',
            'skills' => 'المهارات', 'biography' => 'نبذة عنك', 'twitter_link' => 'رابط تويتر',
            'facebook_link' => 'رابط فيسبوك', 'linkedin_link' => 'رابط لينكدإن', 'save_changes' => 'حفظ التغييرات',
            'change_profile_photo' => 'تغيير الصورة الشخصية', 'upload_photo' => 'رفع الصورة',
            'account_security' => 'أمان الحساب', 'change_password' => 'تغيير كلمة المرور',
            'current_password' => 'كلمة المرور الحالية', 'new_password' => 'كلمة المرور الجديدة',
            'confirm_password' => 'تأكيد كلمة المرور', 'update_password' => 'تحديث كلمة المرور',
            'profile' => 'الملف الشخصي', 'account' => 'الحساب', 'no_saved_courses_yet' => 'لا توجد دورات محفوظة بعد'
        );
        $key = youngo_frontend_phrase_normalize_key($phrase_key);
        return isset($labels[$key]) ? $labels[$key] : null;
    }
}

if (!function_exists('youngo_frontend_phrase')) {
    function youngo_frontend_phrase($phrase_key, $fallback = '', $language_code = null)
    {
        $phrase_key = trim((string) $phrase_key);
        if ($phrase_key === '') {
            return '';
        }

        if ($language_code === null && $fallback !== '' && youngo_frontend_phrase_language_code($fallback) !== null) {
            $language_code = $fallback;
            $fallback = '';
        }

        $language_code = youngo_frontend_phrase_language_code($language_code);
        if ($language_code === null) {
            $language_code = 'english';
        }

        if ($language_code === 'arabic' && function_exists('youngo_frontend_account_arabic_phrase')) {
            $account_phrase = youngo_frontend_account_arabic_phrase($phrase_key);
            if ($account_phrase !== null) {
                return $account_phrase;
            }
        }

        if (preg_match('/^Ages\s+(.+)$/i', $phrase_key, $age_matches)) {
            return youngo_frontend_phrase('ages', 'Ages', $language_code) . ' ' . $age_matches[1];
        }

        $local_requested_value = null;
        $phrase = youngo_frontend_phrase_db_row($phrase_key);
        if (is_array($phrase)) {
            if (isset($phrase[$language_code]) && youngo_frontend_phrase_value_is_clean($phrase[$language_code])) {
                if ($language_code === 'arabic' && !youngo_frontend_phrase_value_has_arabic($phrase[$language_code])) {
                    $local_requested_value = youngo_frontend_local_phrase_value($phrase_key, $language_code);
                    if ($local_requested_value !== null && youngo_frontend_phrase_value_has_arabic($local_requested_value)) {
                        return $local_requested_value;
                    }
                }

                return $phrase[$language_code];
            }

            if (isset($phrase['english']) && youngo_frontend_phrase_value_is_clean($phrase['english'])) {
                return $phrase['english'];
            }
        }

        if ($local_requested_value === null) {
            $local_requested_value = youngo_frontend_local_phrase_value($phrase_key, $language_code);
        }
        if ($local_requested_value !== null) {
            return $local_requested_value;
        }

        $local_english_value = youngo_frontend_local_phrase_value($phrase_key, 'english');
        if ($local_english_value !== null) {
            return $local_english_value;
        }

        if (trim((string) $fallback) !== '') {
            return (string) $fallback;
        }

        return ucfirst(str_replace('_', ' ', youngo_frontend_phrase_normalize_key($phrase_key)));
    }
}

if (!function_exists('youngo_frontend_phrase_e')) {
    function youngo_frontend_phrase_e($phrase_key, $fallback = '', $language_code = null)
    {
        return htmlspecialchars(youngo_frontend_phrase($phrase_key, $fallback, $language_code), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('youngo_frontend_non_localizable_uri_prefixes')) {
    function youngo_frontend_non_localizable_uri_prefixes()
    {
        return array(
            'admin',
            'addons',
            'login/admin',
            'login/validate_login',
            'login/new_login_confirmation',
            'login/fb_validate_login',
            'login/register',
            'login/logout',
            'login/forgot_password',
            'login/change_password',
            'login/resend_verification_code',
            'login/verify_email_address',
            'login/check_recaptcha_with_ajax',
            'payment',
            'paymob',
            'paypal',
            'stripe',
            'razorpay',
            'paystack',
            'flutterwave',
            'home/payment',
            'home/paypal',
            'home/stripe',
            'home/paymob',
            'home/razorpay',
            'home/paystack',
            'home/flutterwave',
            'home/course_payment',
            'home/shopping_cart',
            'home/update_cart',
            'home/apply_coupon',
            'home/remove_coupon',
            'home/checkout',
            'home/confirm_payment',
            'home/webhook',
            'home/handle_cart_items',
            'home/refreshwishlist',
            'home/togglewishlistitems',
            'home/rate_course',
            'home/contact_us/submit',
            'home/subscribe_to_our_newsletter',
            'home/update_profile',
            'home/account_disable',
            'home/check_course_progress',
            'api',
            'cron',
        );
    }
}

if (!function_exists('youngo_frontend_current_uri_string')) {
    function youngo_frontend_current_uri_string()
    {
        if (function_exists('get_instance')) {
            $CI = &get_instance();
            if (isset($CI->uri) && method_exists($CI->uri, 'uri_string')) {
                return $CI->uri->uri_string();
            }
        }

        if (!empty($_SERVER['REQUEST_URI'])) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            return $path === null ? '' : $path;
        }

        return '';
    }
}

if (!function_exists('youngo_frontend_split_uri_query')) {
    function youngo_frontend_split_uri_query($uri_string)
    {
        $uri_string = trim((string) $uri_string);
        $query = '';

        if (strpos($uri_string, '?') !== false) {
            $pieces = explode('?', $uri_string, 2);
            $uri_string = $pieces[0];
            $query = isset($pieces[1]) ? $pieces[1] : '';
        }

        return array(
            'path' => youngo_frontend_normalize_uri_path($uri_string),
            'query' => $query,
        );
    }
}

if (!function_exists('youngo_frontend_uri_path')) {
    function youngo_frontend_uri_path($uri_string = null)
    {
        $uri_string = $uri_string === null ? youngo_frontend_current_uri_string() : $uri_string;
        $path = parse_url((string) $uri_string, PHP_URL_PATH);

        return youngo_frontend_normalize_uri_path($path === null ? $uri_string : $path);
    }
}

if (!function_exists('youngo_frontend_normalize_uri_path')) {
    function youngo_frontend_normalize_uri_path($uri_string)
    {
        $path = str_replace('\\', '/', trim((string) $uri_string));
        $path = preg_replace('#/+#', '/', $path);
        $path = trim($path, '/');

        if ($path === 'index.php') {
            return '';
        }

        if (strpos($path, 'index.php/') === 0) {
            $path = substr($path, strlen('index.php/'));
        }

        return trim($path, '/');
    }
}

if (!function_exists('youngo_frontend_uri_segments')) {
    function youngo_frontend_uri_segments($uri_string)
    {
        $path = youngo_frontend_normalize_uri_path($uri_string);
        if ($path === '') {
            return array();
        }

        return explode('/', strtolower($path));
    }
}

if (!function_exists('youngo_frontend_join_uri_query')) {
    function youngo_frontend_join_uri_query($path, $query)
    {
        $path = youngo_frontend_normalize_uri_path($path);
        $query = ltrim((string) $query, '?');

        return $query === '' ? $path : $path . '?' . $query;
    }
}
