<?php
    $youngo_section_content = is_array($youngo_section_content) ? $youngo_section_content : array();
    $youngo_posts = isset($youngo_section_content['items']) && is_array($youngo_section_content['items']) ? $youngo_section_content['items'] : array();
    $youngo_post_limit = isset($youngo_section_content['limit']) ? (int) $youngo_section_content['limit'] : 3;
    $youngo_posts = array_slice($youngo_posts, 0, max(1, $youngo_post_limit));
    $youngo_cta = isset($youngo_section_content['cta']) && is_array($youngo_section_content['cta']) ? $youngo_section_content['cta'] : array('label' => 'Read more', 'url' => 'blog');
?>

<section class="youngo-section youngo-section--blog">
    <div class="youngo-container">
        <div class="youngo-section__heading youngo-section__heading--split">
            <div>
                <p class="youngo-eyebrow"><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Latest from YounGo') : 'Latest from YounGo'); ?></p>
                <h2><?php echo youngo_homepage_e(isset($youngo_section_content['title']) ? $youngo_section_content['title'] : 'Helpful notes for families'); ?></h2>
                <p><?php echo youngo_homepage_e(isset($youngo_section_content['subtitle']) ? $youngo_section_content['subtitle'] : 'Tips, updates, and learning ideas for parents.'); ?></p>
            </div>
            <a class="youngo-text-link" href="<?php echo youngo_homepage_link(isset($youngo_cta['url']) ? $youngo_cta['url'] : 'blog'); ?>"><?php echo youngo_homepage_e(isset($youngo_cta['label']) ? $youngo_cta['label'] : 'Read more'); ?></a>
        </div>

        <div class="youngo-blog-grid">
            <?php foreach ($youngo_posts as $youngo_post): ?>
                <?php
                    $youngo_post_image = isset($youngo_post['image']) && is_array($youngo_post['image']) ? $youngo_post['image'] : array('url' => isset($youngo_post['image']) ? 'assets/frontend/youngo/images/' . $youngo_post['image'] : '', 'alt' => '');
                ?>
                <article class="youngo-blog-card">
                    <img src="<?php echo youngo_homepage_image_url(isset($youngo_post_image['url']) ? $youngo_post_image['url'] : '', 'assets/frontend/youngo/images/demo-family-project.jpg'); ?>" alt="<?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text(isset($youngo_post_image['alt']) && $youngo_post_image['alt'] !== '' ? $youngo_post_image['alt'] : 'YounGo blog preview') : (isset($youngo_post_image['alt']) && $youngo_post_image['alt'] !== '' ? $youngo_post_image['alt'] : 'YounGo blog preview')); ?>">
                    <div>
                        <span><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text(isset($youngo_post['label']) ? $youngo_post['label'] : 'YounGo') : (isset($youngo_post['label']) ? $youngo_post['label'] : 'YounGo')); ?></span>
                        <h3><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text(isset($youngo_post['title']) ? $youngo_post['title'] : 'Helpful notes for families') : (isset($youngo_post['title']) ? $youngo_post['title'] : 'Helpful notes for families')); ?></h3>
                        <p><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text(isset($youngo_post['description']) ? $youngo_post['description'] : (isset($youngo_post['summary']) ? $youngo_post['summary'] : 'Learning ideas and updates for parents.')) : (isset($youngo_post['description']) ? $youngo_post['description'] : (isset($youngo_post['summary']) ? $youngo_post['summary'] : 'Learning ideas and updates for parents.'))); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
