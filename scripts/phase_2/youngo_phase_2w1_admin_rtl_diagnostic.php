<?php

$root = dirname(__DIR__, 2);
$failures = 0;

if (!defined('BASEPATH')) {
    define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
}

if (!defined('APPPATH')) {
    define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);
}

function youngo_phase_2w1_check($label, $condition, $detail = '')
{
    global $failures;

    echo ($condition ? '[PASS] ' : '[FAIL] ') . $label;
    if ($detail !== '') {
        echo ' - ' . $detail;
    }
    echo PHP_EOL;

    if (!$condition) {
        $failures++;
    }
}

function youngo_phase_2w1_read($relative_path)
{
    global $root;

    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
    return is_file($path) ? file_get_contents($path) : false;
}

echo 'YounGo Phase 2W.1 Admin Dashboard RTL Diagnostic' . PHP_EOL;
echo str_repeat('=', 68) . PHP_EOL;

$helper_file = 'application/helpers/youngo_admin_language_helper.php';
$index_file = 'application/views/backend/index.php';
$top_file = 'application/views/backend/includes_top.php';
$rtl_css_file = 'assets/backend/css/rtl.css';
$bootstrap_rtl_file = 'assets/frontend/default-new/css/bootstrap.rtl.min.css';

$helper = youngo_phase_2w1_read($helper_file);
$index = youngo_phase_2w1_read($index_file);
$top = youngo_phase_2w1_read($top_file);
$rtl_css = youngo_phase_2w1_read($rtl_css_file);
$bootstrap_rtl = youngo_phase_2w1_read($bootstrap_rtl_file);

youngo_phase_2w1_check('Admin language helper exists', $helper !== false, $helper_file);
youngo_phase_2w1_check('Backend index.php exists', $index !== false, $index_file);
youngo_phase_2w1_check('Backend includes_top.php exists', $top !== false, $top_file);
youngo_phase_2w1_check('Admin RTL stylesheet exists', $rtl_css !== false, $rtl_css_file);
youngo_phase_2w1_check('Bootstrap RTL build is available for reuse', $bootstrap_rtl !== false, $bootstrap_rtl_file);

if ($helper !== false) {
    require_once $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $helper_file);

    $expected_functions = array(
        'youngo_admin_active_language',
        'youngo_admin_language_dirs_map',
        'youngo_admin_html_dir',
        'youngo_admin_html_lang',
    );

    foreach ($expected_functions as $function) {
        youngo_phase_2w1_check('Expected helper function exists: ' . $function, function_exists($function));
    }

    if (function_exists('youngo_admin_html_dir')) {
        youngo_phase_2w1_check('English resolves to ltr', youngo_admin_html_dir('english') === 'ltr');
        youngo_phase_2w1_check('Arabic resolves to rtl', youngo_admin_html_dir('arabic') === 'rtl');
        youngo_phase_2w1_check('Unknown language falls back to ltr', youngo_admin_html_dir('klingon') === 'ltr');
    }

    if (function_exists('youngo_admin_html_lang') && function_exists('getIsoCode')) {
        youngo_phase_2w1_check('English resolves to en', youngo_admin_html_lang('english') === 'en');
        youngo_phase_2w1_check('Arabic resolves to ar', youngo_admin_html_lang('arabic') === 'ar');
    }
}

if ($index !== false) {
    youngo_phase_2w1_check('index.php sets html lang from helper', strpos($index, '$admin_html_lang') !== false);
    youngo_phase_2w1_check('index.php sets html dir from helper', strpos($index, '$admin_html_dir') !== false);
    youngo_phase_2w1_check('index.php no longer reads the dead text_align setting', strpos($index, "'text_align'") === false);
    youngo_phase_2w1_check('index.php html tag is dynamic', strpos($index, '<html lang="<?php echo html_escape($admin_html_lang);') !== false);
}

if ($top !== false) {
    youngo_phase_2w1_check('includes_top.php swaps to bootstrap.rtl.min.css when rtl', strpos($top, 'bootstrap.rtl.min.css') !== false);
    youngo_phase_2w1_check('includes_top.php conditionally loads assets/backend/css/rtl.css', strpos($top, "assets/backend/css/rtl.css") !== false);
}

echo PHP_EOL . str_repeat('=', 68) . PHP_EOL;
if ($failures === 0) {
    echo 'Result: PASS' . PHP_EOL;
    exit(0);
}

echo "Result: FAIL ({$failures} check(s) failed)" . PHP_EOL;
exit(1);
