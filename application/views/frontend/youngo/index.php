<?php
    if (file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
        $this->load->helper('youngo_frontend_language');
    }

    $youngo_frontend_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
    $youngo_frontend_html_lang = function_exists('youngo_frontend_html_lang') ? youngo_frontend_html_lang($youngo_frontend_language) : getIsoCode('english');
    $youngo_frontend_html_dir = function_exists('youngo_frontend_html_dir') ? youngo_frontend_html_dir($youngo_frontend_language) : 'ltr';
    $youngo_theme_mode = trim((string) $this->session->userdata('theme_mode'));
    $youngo_body_classes = array(
        'youngo-theme',
        'youngo-lang-' . $youngo_frontend_language,
        'youngo-dir-' . $youngo_frontend_html_dir,
    );

    if ($youngo_theme_mode !== '') {
        $youngo_body_classes[] = preg_replace('/[^A-Za-z0-9_-]+/', '-', $youngo_theme_mode);
    }

    $page_name = array_key_exists('page_name', get_defined_vars()) ? $page_name : 'home';
    $page_title = isset($page_title) ? $page_title : get_settings('system_name');
    $page_file = $page_name === null ? '' : $page_name . '.php';

    if ($page_name !== null && ! file_exists(__DIR__ . DIRECTORY_SEPARATOR . $page_file) && strpos($page_name, 'home') === 0) {
        $page_file = 'home.php';
    }
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($youngo_frontend_html_lang, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo htmlspecialchars($youngo_frontend_html_dir, ENT_QUOTES, 'UTF-8'); ?>">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5.0">
        <title><?php echo htmlspecialchars($page_title); ?> | <?php echo get_settings('system_name'); ?></title>
        <link rel="icon" href="<?php echo base_url('uploads/system/' . get_frontend_settings('favicon')); ?>" type="image/x-icon">
        <?php include 'includes_top.php'; ?>
    </head>
<body class="<?php echo htmlspecialchars(implode(' ', array_filter($youngo_body_classes)), ENT_QUOTES, 'UTF-8'); ?>" data-youngo-language="<?php echo htmlspecialchars($youngo_frontend_language, ENT_QUOTES, 'UTF-8'); ?>" data-youngo-dir="<?php echo htmlspecialchars($youngo_frontend_html_dir, ENT_QUOTES, 'UTF-8'); ?>">
        <?php include 'header.php'; ?>

        <main class="youngo-main">
            <?php if ($page_name === null && isset($path) && file_exists($path)): ?>
                <?php include $path; ?>
            <?php elseif ($page_file !== '' && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $page_file)): ?>
                <?php include $page_file; ?>
            <?php else: ?>
                <section class="youngo-placeholder">
                    <div class="youngo-container">
                        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('youngo_demo_page'); ?></p>
                        <h1><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string) $page_name))); ?></h1>
                        <p><?php echo youngo_frontend_phrase('this_page_is_not_part_of_the_current_public_demo_flow.'); ?></p>
                        <a class="youngo-button" href="<?php echo function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_frontend_language) : site_url('home'); ?>"><?php echo youngo_frontend_phrase('home'); ?></a>
                    </div>
                </section>
            <?php endif; ?>
        </main>

        <?php include 'footer.php'; ?>
        <?php include 'includes_bottom.php'; ?>
        <?php echo get_frontend_settings('embed_code'); ?>
    </body>
</html>

