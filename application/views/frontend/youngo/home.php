<?php
    $youngo_homepage_content = youngo_get_homepage_content(false);
    $youngo_home_sections = youngo_homepage_ordered_sections($youngo_homepage_content);
?>

<?php foreach ($youngo_home_sections as $youngo_home_entry): ?>
    <?php
        $youngo_section = isset($youngo_home_entry['section']) && is_array($youngo_home_entry['section']) ? $youngo_home_entry['section'] : array();
        $youngo_section_content = isset($youngo_section['content']) && is_array($youngo_section['content']) ? $youngo_section['content'] : array();
        if (function_exists('youngo_frontend_homepage_localize_content')) {
            $youngo_section_content = youngo_frontend_homepage_localize_content($youngo_section_content, isset($youngo_frontend_language) ? $youngo_frontend_language : null);
            $youngo_section['content'] = $youngo_section_content;
        }
        $youngo_home_section = isset($youngo_home_entry['key']) ? $youngo_home_entry['key'] : '';
        $youngo_home_section_file = '';

        if (isset($youngo_home_entry['render_type']) && $youngo_home_entry['render_type'] === 'fixed') {
            $youngo_home_section_file = __DIR__ . DIRECTORY_SEPARATOR . 'home_sections' . DIRECTORY_SEPARATOR . $youngo_home_section . '.php';
        } elseif (isset($youngo_home_entry['render_type']) && $youngo_home_entry['render_type'] === 'additional') {
            $youngo_home_section_file = __DIR__ . DIRECTORY_SEPARATOR . 'home_sections' . DIRECTORY_SEPARATOR . 'additional_section.php';
        }
    ?>
    <?php if (file_exists($youngo_home_section_file)): ?>
        <?php include $youngo_home_section_file; ?>
    <?php endif; ?>
<?php endforeach; ?>
