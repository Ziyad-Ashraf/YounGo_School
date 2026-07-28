<?php
if (file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
    $this->load->helper('youngo_frontend_language');
}

if (!function_exists('youngo_blog_detail_e')) {
    function youngo_blog_detail_e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('youngo_blog_detail_thumbnail_url')) {
    function youngo_blog_detail_thumbnail_url($blog_details)
    {
        $banner = isset($blog_details['banner']) ? trim((string) $blog_details['banner']) : '';
        $banner_path = 'uploads/blog/banner/' . $banner;

        if ($banner !== '' && file_exists($banner_path) && is_file($banner_path)) {
            return base_url($banner_path);
        }

        $thumbnail = isset($blog_details['thumbnail']) ? trim((string) $blog_details['thumbnail']) : '';
        $thumbnail_path = 'uploads/blog/thumbnail/' . $thumbnail;

        if ($thumbnail !== '' && file_exists($thumbnail_path) && is_file($thumbnail_path)) {
            return base_url($thumbnail_path);
        }

        return base_url('assets/frontend/youngo/images/demo-family-project.jpg');
    }
}

if (!function_exists('youngo_blog_detail_category_title')) {
    function youngo_blog_detail_category_title($CI, $blog_details, $language = 'english')
    {
        if (empty($blog_details['blog_category_id'])) {
            return 'YounGo';
        }

        $category = $CI->crud_model->get_blog_categories($blog_details['blog_category_id']);
        if ($category && $category->num_rows() > 0) {
            $category_row = $category->row_array();
            if (method_exists($CI->crud_model, 'youngo_apply_blog_category_translation')) {
                $category_row = $CI->crud_model->youngo_apply_blog_category_translation($category_row, $language);
            }

            return isset($category_row['title']) && trim((string) $category_row['title']) !== '' ? $category_row['title'] : 'YounGo';
        }

        return 'YounGo';
    }
}

$youngo_blog_detail_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_blog_detail_home_url = function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_blog_detail_language) : site_url('home');
$youngo_blog_detail_blog_url = function_exists('youngo_frontend_blog_url') ? youngo_frontend_blog_url($youngo_blog_detail_language) : site_url('blog');
$blog_details = isset($blog_details) && is_array($blog_details) ? $blog_details : array();
$youngo_blog_detail_title = isset($blog_details['title']) ? $blog_details['title'] : youngo_frontend_phrase('blog', $youngo_blog_detail_language);
$youngo_blog_detail_date = !empty($blog_details['added_date']) ? date('M d, Y', (int) $blog_details['added_date']) : '';
$youngo_blog_detail_author = '';

if (!empty($blog_details['user_id'])) {
    $youngo_blog_detail_user = $this->user_model->get_all_user($blog_details['user_id'])->row_array();
    if (!empty($youngo_blog_detail_user)) {
        $youngo_blog_detail_author = trim((isset($youngo_blog_detail_user['first_name']) ? $youngo_blog_detail_user['first_name'] : '') . ' ' . (isset($youngo_blog_detail_user['last_name']) ? $youngo_blog_detail_user['last_name'] : ''));
    }
}

$youngo_blog_detail_keywords = array();
if (!empty($blog_details['keywords'])) {
    foreach (explode(',', $blog_details['keywords']) as $keyword) {
        $keyword = trim($keyword);
        if ($keyword !== '') {
            $youngo_blog_detail_keywords[] = $keyword;
        }
    }
}
?>

<section class="youngo-courses-hero">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo youngo_blog_detail_e(youngo_frontend_phrase('breadcrumb', $youngo_blog_detail_language)); ?>">
            <a href="<?php echo youngo_blog_detail_e($youngo_blog_detail_home_url); ?>"><?php echo youngo_blog_detail_e(youngo_frontend_phrase('home', $youngo_blog_detail_language)); ?></a>
            <span>/</span>
            <a href="<?php echo youngo_blog_detail_e($youngo_blog_detail_blog_url); ?>"><?php echo youngo_blog_detail_e(youngo_frontend_phrase('blog', $youngo_blog_detail_language)); ?></a>
            <span>/</span>
            <span><?php echo youngo_blog_detail_e(youngo_frontend_phrase('details', $youngo_blog_detail_language)); ?></span>
        </nav>

        <div class="youngo-courses-hero__grid">
            <div>
                <p class="youngo-eyebrow"><?php echo youngo_blog_detail_e(youngo_blog_detail_category_title($this, $blog_details, $youngo_blog_detail_language)); ?></p>
                <h1><?php echo youngo_blog_detail_e($youngo_blog_detail_title); ?></h1>
                <p>
                    <?php if ($youngo_blog_detail_date !== ''): ?>
                        <?php echo youngo_blog_detail_e($youngo_blog_detail_date); ?>
                    <?php endif; ?>
                    <?php if ($youngo_blog_detail_author !== ''): ?>
                        <?php echo $youngo_blog_detail_date !== '' ? ' | ' : ''; ?>
                        <?php echo youngo_blog_detail_e(youngo_frontend_phrase('created_by', $youngo_blog_detail_language) . ' ' . $youngo_blog_detail_author); ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="youngo-courses-hero__stats" aria-label="<?php echo youngo_blog_detail_e(youngo_frontend_phrase('blog', $youngo_blog_detail_language)); ?>">
                <strong><i class="fa-regular fa-newspaper"></i></strong>
                <span><?php echo youngo_blog_detail_e(youngo_frontend_phrase('latest_articles', $youngo_blog_detail_language)); ?></span>
            </div>
        </div>
    </div>
</section>

<section class="youngo-section youngo-section--white">
    <div class="youngo-container youngo-container--narrow">
        <article class="youngo-placeholder__panel">
            <img loading="lazy" src="<?php echo youngo_blog_detail_e(youngo_blog_detail_thumbnail_url($blog_details)); ?>" alt="<?php echo youngo_blog_detail_e($youngo_blog_detail_title); ?>" style="width:100%;max-height:430px;object-fit:cover;border-radius:18px;">

            <div style="display:grid;gap:18px;color:var(--youngo-text);font-size:16px;line-height:1.8;">
                <?php echo isset($blog_details['description']) ? htmlspecialchars_decode_($blog_details['description']) : ''; ?>
            </div>

            <?php if (!empty($youngo_blog_detail_keywords)): ?>
                <div class="youngo-card-meta" style="justify-content:flex-start;flex-wrap:wrap;">
                    <?php foreach ($youngo_blog_detail_keywords as $keyword): ?>
                        <span><?php echo youngo_blog_detail_e($keyword); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="youngo-section__actions">
                <a class="youngo-button youngo-button--secondary" href="<?php echo youngo_blog_detail_e($youngo_blog_detail_blog_url); ?>"><?php echo youngo_blog_detail_e(youngo_frontend_phrase('back_to_blog', $youngo_blog_detail_language)); ?></a>
            </div>
        </article>
    </div>
</section>
