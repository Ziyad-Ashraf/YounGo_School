<?php
if (file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
    $this->load->helper('youngo_frontend_language');
}

if (!function_exists('youngo_blog_e')) {
    function youngo_blog_e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('youngo_blog_excerpt')) {
    function youngo_blog_excerpt($value, $length = 150)
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags(htmlspecialchars_decode_((string) $value))));
        if (strlen($text) <= $length) {
            return $text;
        }

        return rtrim(substr($text, 0, $length - 1)) . '...';
    }
}

if (!function_exists('youngo_blog_thumbnail_url')) {
    function youngo_blog_thumbnail_url($blog)
    {
        $thumbnail = isset($blog['thumbnail']) ? trim((string) $blog['thumbnail']) : '';
        $thumbnail_path = 'uploads/blog/thumbnail/' . $thumbnail;

        if ($thumbnail !== '' && file_exists($thumbnail_path) && is_file($thumbnail_path)) {
            return base_url($thumbnail_path);
        }

        if (file_exists('uploads/blog/thumbnail/placeholder.png') && is_file('uploads/blog/thumbnail/placeholder.png')) {
            return base_url('uploads/blog/thumbnail/placeholder.png');
        }

        return base_url('assets/frontend/youngo/images/demo-family-project.jpg');
    }
}

if (!function_exists('youngo_blog_category_title')) {
    function youngo_blog_category_title($CI, $blog, $language = 'english')
    {
        if (empty($blog['blog_category_id'])) {
            return 'YounGo';
        }

        $category = $CI->crud_model->get_blog_categories($blog['blog_category_id']);
        if ($category && $category->num_rows() > 0) {
            if (method_exists($CI->crud_model, 'youngo_apply_blog_category_translation')) {
                $category_row = $CI->crud_model->youngo_apply_blog_category_translation($category->row_array(), $language);
                return isset($category_row['title']) && trim((string) $category_row['title']) !== '' ? $category_row['title'] : 'YounGo';
            }

            $title = $category->row('title');
            return $title;
        }

        return 'YounGo';
    }
}

if (!function_exists('youngo_blog_rows')) {
    function youngo_blog_rows($query)
    {
        return is_object($query) && method_exists($query, 'result_array') ? $query->result_array() : array();
    }
}

if (!function_exists('youngo_blog_demo_value')) {
    function youngo_blog_demo_value($card, $field, $language)
    {
        if (isset($card[$field]) && is_array($card[$field])) {
            $language_key = $language === 'arabic' ? 'arabic' : 'english';
            return isset($card[$field][$language_key]) ? $card[$field][$language_key] : (isset($card[$field]['english']) ? $card[$field]['english'] : '');
        }

        return isset($card[$field]) ? $card[$field] : '';
    }
}

if (!function_exists('youngo_blog_translation_row')) {
    function youngo_blog_translation_row($CI, $blog_id, $language)
    {
        $blog_id = (int) $blog_id;
        if ($blog_id <= 0 || !isset($CI->db) || !$CI->db->table_exists('youngo_blog_translations')) {
            return array();
        }

        $CI->db->where('blog_id', $blog_id);
        $CI->db->where('language_code', $language === 'arabic' ? 'arabic' : 'english');
        $query = $CI->db->get('youngo_blog_translations');

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }
}

if (!function_exists('youngo_blog_date_label')) {
    function youngo_blog_date_label($timestamp, $language)
    {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return '';
        }

        return $language === 'arabic' ? date('d/m/Y', $timestamp) : date('M d, Y', $timestamp);
    }
}

if (!function_exists('youngo_blog_localized_rows')) {
    function youngo_blog_localized_rows($CI, $blogs, $language)
    {
        $localized = array();
        foreach ($blogs as $blog) {
            $blog_id = isset($blog['blog_id']) ? (int) $blog['blog_id'] : 0;
            $translation = youngo_blog_translation_row($CI, $blog_id, $language);

            if ($language === 'arabic') {
                if (empty($translation) || trim((string) $translation['title']) === '') {
                    continue;
                }

                $blog['title'] = $translation['title'];
                $blog['description'] = isset($translation['description']) ? $translation['description'] : '';
                $blog['youngo_excerpt'] = isset($translation['excerpt']) ? $translation['excerpt'] : '';
                $blog['youngo_translated'] = true;
                $localized[] = $blog;
                continue;
            }

            if (!empty($translation)) {
                if (trim((string) $translation['title']) !== '') {
                    $blog['title'] = $translation['title'];
                }
                if (isset($translation['description']) && trim((string) $translation['description']) !== '') {
                    $blog['description'] = $translation['description'];
                }
                if (isset($translation['excerpt']) && trim((string) $translation['excerpt']) !== '') {
                    $blog['youngo_excerpt'] = $translation['excerpt'];
                }
            }

            $localized[] = $blog;
        }

        return $localized;
    }
}

$youngo_blog_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_blog_home_url = function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_blog_language) : site_url('home');
$youngo_blog_index_url = function_exists('youngo_frontend_blog_url') ? youngo_frontend_blog_url($youngo_blog_language) : site_url('blog');
$youngo_blog_courses_url = function_exists('youngo_frontend_courses_url') ? youngo_frontend_courses_url($youngo_blog_language) : site_url('home/courses');
$youngo_blog_home_label = youngo_frontend_phrase('home', $youngo_blog_language);
$youngo_blog_title = youngo_frontend_phrase('helpful_notes_for_families', $youngo_blog_language);
$youngo_blog_intro = youngo_frontend_phrase('practical_articles_and_learning_tips_for_parents_will_appear_here_soon.', $youngo_blog_language);
$youngo_blog_included_page = isset($included_page) ? (string) $included_page : '';
$youngo_blog_categories = array();
$youngo_blog_posts = array();

if ($youngo_blog_included_page === 'blog_categories.php') {
    $youngo_blog_categories_query = $this->crud_model->get_blog_categories();
    $youngo_blog_categories = $youngo_blog_categories_query ? $youngo_blog_categories_query->result_array() : array();
    if (method_exists($this->crud_model, 'youngo_apply_blog_category_translations')) {
        $youngo_blog_categories = $this->crud_model->youngo_apply_blog_category_translations($youngo_blog_categories, $youngo_blog_language);
    }
} elseif (isset($blogs)) {
    $youngo_blog_posts = youngo_blog_localized_rows($this, youngo_blog_rows($blogs), $youngo_blog_language);
} else {
    $youngo_blog_posts = youngo_blog_localized_rows($this, youngo_blog_rows(isset($latest_blogs) ? $latest_blogs : null), $youngo_blog_language);
}

$youngo_blog_fallback_cards = array(
    array(
        'label' => array('english' => 'Parenting Tips', 'arabic' => 'نصائح للأهل'),
        'title' => array('english' => 'Simple ways to keep kids engaged', 'arabic' => 'طرق بسيطة للحفاظ على تفاعل الأطفال'),
        'description' => array('english' => 'Short ideas for turning everyday moments into useful learning routines.', 'arabic' => 'أفكار قصيرة لتحويل اللحظات اليومية إلى عادات تعلم مفيدة.'),
        'read_time' => array('english' => '4 min read', 'arabic' => '٤ دقائق قراءة'),
        'image' => 'assets/frontend/youngo/images/demo-family-project.jpg',
    ),
    array(
        'label' => array('english' => 'Platform News', 'arabic' => 'أخبار المنصة'),
        'title' => array('english' => 'Creative coding starts with curiosity', 'arabic' => 'تبدأ البرمجة الإبداعية بالفضول'),
        'description' => array('english' => 'A parent-friendly look at how kids can begin building logic and confidence.', 'arabic' => 'نظرة مناسبة للأهل حول كيف يبدأ الأطفال بناء المنطق والثقة.'),
        'read_time' => array('english' => '5 min read', 'arabic' => '٥ دقائق قراءة'),
        'image' => 'assets/frontend/youngo/images/demo-course-coding.jpg',
    ),
    array(
        'label' => array('english' => 'Learning Ideas', 'arabic' => 'أفكار للتعلم'),
        'title' => array('english' => 'Safe science activities at home', 'arabic' => 'أنشطة علوم آمنة في المنزل'),
        'description' => array('english' => 'Easy experiments and guided questions that make discovery feel approachable.', 'arabic' => 'تجارب سهلة وأسئلة موجهة تجعل الاكتشاف قريبا وممتعا.'),
        'read_time' => array('english' => '6 min read', 'arabic' => '٦ دقائق قراءة'),
        'image' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
    ),
    array(
        'label' => array('english' => 'Course Guidance', 'arabic' => 'إرشاد الكورسات'),
        'title' => array('english' => 'Choosing the right first course', 'arabic' => 'اختيار الكورس الأول المناسب'),
        'description' => array('english' => 'A calm checklist for matching your child with a topic, pace, and project style they can enjoy.', 'arabic' => 'قائمة هادئة تساعدك على اختيار موضوع وسرعة ومشروع يناسب طفلك.'),
        'read_time' => array('english' => '3 min read', 'arabic' => '٣ دقائق قراءة'),
        'image' => 'assets/frontend/youngo/images/demo-course-coding.jpg',
    ),
    array(
        'label' => array('english' => 'Creative Practice', 'arabic' => 'تدريب إبداعي'),
        'title' => array('english' => 'Turning screen time into making time', 'arabic' => 'تحويل وقت الشاشة إلى وقت للإبداع'),
        'description' => array('english' => 'Small prompts that help children create, explain, and reflect instead of only watching.', 'arabic' => 'أفكار قصيرة تساعد الأطفال على الإبداع والشرح والتفكير بدلا من المشاهدة فقط.'),
        'read_time' => array('english' => '4 min read', 'arabic' => '٤ دقائق قراءة'),
        'image' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
    ),
    array(
        'label' => array('english' => 'Parent Confidence', 'arabic' => 'ثقة الأهل'),
        'title' => array('english' => 'What progress can look like each week', 'arabic' => 'كيف يبدو التقدم من أسبوع لآخر'),
        'description' => array('english' => 'Simple signs that your child is gaining confidence, vocabulary, and problem-solving habits.', 'arabic' => 'علامات بسيطة توضح أن طفلك يكتسب الثقة والمفردات وعادات حل المشكلات.'),
        'read_time' => array('english' => '5 min read', 'arabic' => '٥ دقائق قراءة'),
        'image' => 'assets/frontend/youngo/images/demo-course-robotics.jpg',
    ),
);

$youngo_blog_stat_count = count($youngo_blog_posts) > 0 ? count($youngo_blog_posts) : count($youngo_blog_fallback_cards);
?>

<section class="youngo-courses-hero">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo youngo_blog_e(youngo_frontend_phrase('breadcrumb', $youngo_blog_language)); ?>">
            <a href="<?php echo youngo_blog_e($youngo_blog_home_url); ?>"><?php echo youngo_blog_e($youngo_blog_home_label); ?></a>
            <span>/</span>
            <span><?php echo youngo_blog_e(youngo_frontend_phrase('blog', $youngo_blog_language)); ?></span>
        </nav>

        <div class="youngo-courses-hero__grid">
            <div>
                <p class="youngo-eyebrow"><?php echo youngo_blog_e(youngo_frontend_phrase('latest_articles', $youngo_blog_language)); ?></p>
                <h1><?php echo youngo_blog_e($youngo_blog_title); ?></h1>
                <p><?php echo youngo_blog_e($youngo_blog_intro); ?></p>
            </div>
            <div class="youngo-courses-hero__stats" aria-label="<?php echo youngo_blog_e(youngo_frontend_phrase('blog', $youngo_blog_language)); ?>">
                <strong><?php echo $youngo_blog_stat_count; ?></strong>
                <span><?php echo youngo_blog_e(youngo_frontend_phrase(count($youngo_blog_posts) === 1 ? 'latest_articles' : 'blogs', $youngo_blog_language)); ?></span>
            </div>
        </div>
    </div>
</section>

<section class="youngo-section youngo-section--blog">
    <div class="youngo-container">
        <?php if ($youngo_blog_included_page === 'blog_categories.php' && !empty($youngo_blog_categories)): ?>
            <div class="youngo-section__heading youngo-section__heading--split">
                <div>
                    <p class="youngo-eyebrow"><?php echo youngo_blog_e(youngo_frontend_phrase('categories', $youngo_blog_language)); ?></p>
                    <h2><?php echo youngo_blog_e(youngo_frontend_phrase('all_categories', $youngo_blog_language)); ?></h2>
                    <p><?php echo youngo_blog_e(youngo_frontend_phrase('we_will_share_family_learning_notes_here_as_the_youngo_library_grows.', $youngo_blog_language)); ?></p>
                </div>
                <a class="youngo-text-link" href="<?php echo youngo_blog_e($youngo_blog_index_url); ?>"><?php echo youngo_blog_e(youngo_frontend_phrase('all_articles', $youngo_blog_language)); ?></a>
            </div>

            <div class="youngo-blog-grid">
                <?php foreach ($youngo_blog_categories as $category): ?>
                    <?php
                    $category_title = isset($category['title']) ? $category['title'] : 'YounGo';
                    $category_subtitle = isset($category['subtitle']) ? $category['subtitle'] : '';
                    $category_slug = isset($category['slug']) ? $category['slug'] : '';
                    ?>
                    <a class="youngo-category-card" href="<?php echo youngo_blog_e(function_exists('youngo_frontend_blog_url') ? youngo_frontend_blog_url($youngo_blog_language, 'category=' . rawurlencode($category_slug)) : site_url('blogs?category=' . rawurlencode($category_slug))); ?>">
                        <span class="youngo-icon-badge"><i class="fa-regular fa-newspaper"></i></span>
                        <strong><?php echo youngo_blog_e($category_title); ?></strong>
                        <?php if ($category_subtitle !== ''): ?>
                            <small><?php echo youngo_blog_e(youngo_blog_excerpt($category_subtitle, 90)); ?></small>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php elseif (!empty($youngo_blog_posts)): ?>
            <div class="youngo-section__heading youngo-section__heading--split">
                <div>
                    <p class="youngo-eyebrow"><?php echo youngo_blog_e(youngo_frontend_phrase('latest_articles', $youngo_blog_language)); ?></p>
                    <h2><?php echo youngo_blog_e(isset($search_string) ? youngo_frontend_phrase('showing_results', $youngo_blog_language) : youngo_frontend_phrase('all_articles', $youngo_blog_language)); ?></h2>
                    <p><?php echo youngo_blog_e(youngo_frontend_phrase('we_will_share_family_learning_notes_here_as_the_youngo_library_grows.', $youngo_blog_language)); ?></p>
                </div>
                <a class="youngo-text-link" href="<?php echo youngo_blog_e($youngo_blog_index_url); ?>"><?php echo youngo_blog_e(youngo_frontend_phrase('all_articles', $youngo_blog_language)); ?></a>
            </div>

            <div class="youngo-blog-grid">
                <?php foreach ($youngo_blog_posts as $blog): ?>
                    <?php
                    $blog_title = isset($blog['title']) ? $blog['title'] : '';
                    $blog_id = isset($blog['blog_id']) ? (int) $blog['blog_id'] : 0;
                    $blog_url = function_exists('youngo_frontend_blog_detail_url') ? youngo_frontend_blog_detail_url($blog_title, $blog_id, $youngo_blog_language) : site_url('blog/details/' . slugify($blog_title) . '/' . $blog_id);
                    $blog_date = !empty($blog['added_date']) ? youngo_blog_date_label((int) $blog['added_date'], $youngo_blog_language) : '';
                    ?>
                    <a class="youngo-blog-card" href="<?php echo youngo_blog_e($blog_url); ?>" style="overflow:hidden;text-decoration:none;color:inherit;">
                        <img loading="lazy" src="<?php echo youngo_blog_e(youngo_blog_thumbnail_url($blog)); ?>" alt="<?php echo youngo_blog_e($blog_title); ?>">
                        <div>
                            <span><?php echo youngo_blog_e(youngo_blog_category_title($this, $blog, $youngo_blog_language)); ?></span>
                            <h3><?php echo youngo_blog_e($blog_title); ?></h3>
                            <p><?php echo youngo_blog_e(isset($blog['youngo_excerpt']) && trim((string) $blog['youngo_excerpt']) !== '' ? $blog['youngo_excerpt'] : youngo_blog_excerpt(isset($blog['description']) ? $blog['description'] : '')); ?></p>
                            <div class="youngo-card-meta">
                                <span><?php echo youngo_blog_e($blog_date); ?></span>
                                <span><?php echo youngo_blog_e(youngo_frontend_phrase('read_more', $youngo_blog_language)); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php $youngo_blog_pagination = $this->pagination->create_links(); ?>
            <?php if ($youngo_blog_pagination !== ''): ?>
                <nav class="youngo-pagination" aria-label="<?php echo youngo_blog_e(youngo_frontend_phrase('blog', $youngo_blog_language)); ?>">
                    <?php echo $youngo_blog_pagination; ?>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <?php
            $youngo_blog_featured_card = reset($youngo_blog_fallback_cards);
            $youngo_blog_secondary_cards = array_slice($youngo_blog_fallback_cards, 1);
            ?>
            <div class="youngo-section__heading youngo-section__heading--split youngo-blog-page-heading">
                <div>
                    <p class="youngo-eyebrow"><?php echo youngo_blog_e(youngo_frontend_phrase('no_blog_posts_yet', $youngo_blog_language)); ?></p>
                    <h2><?php echo youngo_blog_e($youngo_blog_title); ?></h2>
                    <p><?php echo youngo_blog_e($youngo_blog_intro); ?></p>
                </div>
                <a class="youngo-text-link" href="<?php echo youngo_blog_e($youngo_blog_courses_url); ?>"><?php echo youngo_blog_e(youngo_frontend_phrase('browse_courses', $youngo_blog_language)); ?></a>
            </div>

            <div class="youngo-blog-editorial">
                <article class="youngo-blog-featured-card">
                    <img loading="eager" src="<?php echo base_url($youngo_blog_featured_card['image']); ?>" alt="<?php echo youngo_blog_e(youngo_blog_demo_value($youngo_blog_featured_card, 'title', $youngo_blog_language)); ?>">
                    <div>
                        <span><?php echo youngo_blog_e(youngo_blog_demo_value($youngo_blog_featured_card, 'label', $youngo_blog_language)); ?></span>
                        <h3><?php echo youngo_blog_e(youngo_blog_demo_value($youngo_blog_featured_card, 'title', $youngo_blog_language)); ?></h3>
                        <p><?php echo youngo_blog_e(youngo_blog_demo_value($youngo_blog_featured_card, 'description', $youngo_blog_language)); ?></p>
                        <small><?php echo youngo_blog_e(youngo_blog_demo_value($youngo_blog_featured_card, 'read_time', $youngo_blog_language)); ?></small>
                    </div>
                </article>

                <div class="youngo-blog-side-panel">
                    <span><?php echo youngo_blog_e($youngo_blog_language === 'arabic' ? 'دليل سريع للأهل' : 'Quick guide for parents'); ?></span>
                    <strong><?php echo youngo_blog_e($youngo_blog_language === 'arabic' ? 'محتوى يساعدك على اختيار الكورسات ومتابعة التقدم بثقة.' : 'Ideas to help you choose courses and follow progress with confidence.'); ?></strong>
                    <div>
                        <p><?php echo youngo_blog_e($youngo_blog_language === 'arabic' ? '٦ موضوعات' : '6 topics'); ?></p>
                        <p><?php echo youngo_blog_e($youngo_blog_language === 'arabic' ? 'نصائح قصيرة' : 'Short notes'); ?></p>
                        <p><?php echo youngo_blog_e($youngo_blog_language === 'arabic' ? 'للأهل والأطفال' : 'For families'); ?></p>
                    </div>
                </div>
            </div>

            <div class="youngo-blog-magazine-grid">
                <?php foreach ($youngo_blog_secondary_cards as $fallback_card): ?>
                    <article class="youngo-blog-magazine-card">
                        <img loading="eager" src="<?php echo base_url($fallback_card['image']); ?>" alt="<?php echo youngo_blog_e(youngo_blog_demo_value($fallback_card, 'title', $youngo_blog_language)); ?>">
                        <div>
                            <span><?php echo youngo_blog_e(youngo_blog_demo_value($fallback_card, 'label', $youngo_blog_language)); ?></span>
                            <h3><?php echo youngo_blog_e(youngo_blog_demo_value($fallback_card, 'title', $youngo_blog_language)); ?></h3>
                            <p><?php echo youngo_blog_e(youngo_blog_demo_value($fallback_card, 'description', $youngo_blog_language)); ?></p>
                            <small><?php echo youngo_blog_e(youngo_blog_demo_value($fallback_card, 'read_time', $youngo_blog_language)); ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
