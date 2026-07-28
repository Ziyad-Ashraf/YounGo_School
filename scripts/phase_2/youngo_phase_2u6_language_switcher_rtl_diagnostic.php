<?php

$root = dirname(__DIR__, 2);
$failures = 0;

if (!defined('BASEPATH')) {
    define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
}

if (!defined('APPPATH')) {
    define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);
}

function youngo_phase_2u6_5_check($label, $condition, $detail = '')
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

function youngo_phase_2u6_5_read($relative_path)
{
    global $root;

    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
    return is_file($path) ? file_get_contents($path) : false;
}

function youngo_phase_2u6_5_run($command)
{
    global $root;

    $cwd = getcwd();
    chdir($root);
    $output = array();
    $exit_code = 0;
    exec($command . ' 2>&1', $output, $exit_code);
    chdir($cwd);

    return array($exit_code, implode(PHP_EOL, $output));
}

function youngo_phase_2u6_5_php_diagnostic_passes($relative_path)
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($relative_path);
    list($exit_code, $output) = youngo_phase_2u6_5_run($command);

    return $exit_code === 0 && strpos($output, '[FAIL]') === false;
}

function youngo_phase_2u6_5_changed_files()
{
    list($exit_code, $output) = youngo_phase_2u6_5_run('git diff --name-only');

    if ($exit_code !== 0 || trim($output) === '') {
        return array();
    }

    return preg_split('/\R+/', trim(str_replace('\\', '/', $output)));
}

echo 'YounGo Phase 2U.6.5 Language Switcher and RTL Diagnostic' . PHP_EOL;
echo str_repeat('=', 68) . PHP_EOL;

$helper_file = 'application/helpers/youngo_frontend_language_helper.php';
$shell_file = 'application/views/frontend/youngo/index.php';
$header_file = 'application/views/frontend/youngo/header.php';
$css_file = 'assets/frontend/youngo/css/youngo.css';
$routes_file = 'application/config/routes.php';

$helper = youngo_phase_2u6_5_read($helper_file);
$shell = youngo_phase_2u6_5_read($shell_file);
$header = youngo_phase_2u6_5_read($header_file);
$css = youngo_phase_2u6_5_read($css_file);
$routes = youngo_phase_2u6_5_read($routes_file);

youngo_phase_2u6_5_check('YounGo frontend language helper exists', $helper !== false, $helper_file);
youngo_phase_2u6_5_check('YounGo frontend shell exists', $shell !== false, $shell_file);
youngo_phase_2u6_5_check('YounGo frontend header exists', $header !== false, $header_file);
youngo_phase_2u6_5_check('YounGo frontend CSS exists', $css !== false, $css_file);

if ($helper !== false) {
    require_once $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $helper_file);
    $current_uri_function_pos = strpos($helper, 'function youngo_frontend_current_uri_string_with_query');
    $current_uri_ci_pos = $current_uri_function_pos === false ? false : strpos($helper, 'get_instance', $current_uri_function_pos);
    $current_uri_request_pos = $current_uri_function_pos === false ? false : strpos($helper, 'REQUEST_URI', $current_uri_function_pos);
    youngo_phase_2u6_5_check('Current URI switch helper prefers CodeIgniter URI before REQUEST_URI fallback', $current_uri_ci_pos !== false && $current_uri_request_pos !== false && $current_uri_ci_pos < $current_uri_request_pos);
}

$expected_functions = array(
    'youngo_frontend_active_language',
    'youngo_frontend_html_lang',
    'youngo_frontend_html_dir',
    'youngo_frontend_language_switch_path',
    'youngo_frontend_language_switch_url',
);

foreach ($expected_functions as $function) {
    youngo_phase_2u6_5_check('Expected helper function exists: ' . $function, function_exists($function));
}

if (function_exists('youngo_frontend_html_lang') && function_exists('youngo_frontend_html_dir')) {
    youngo_phase_2u6_5_check('English shell helper returns en/ltr', youngo_frontend_html_lang('english') === 'en' && youngo_frontend_html_dir('english') === 'ltr');
    youngo_phase_2u6_5_check('Arabic shell helper returns ar/rtl', youngo_frontend_html_lang('arabic') === 'ar' && youngo_frontend_html_dir('arabic') === 'rtl');
    youngo_phase_2u6_5_check('Unknown shell helper input falls back to en/ltr', youngo_frontend_html_lang('unknown') === 'en' && youngo_frontend_html_dir('unknown') === 'ltr');
}

if (function_exists('youngo_frontend_language_from_uri')) {
    youngo_phase_2u6_5_check('/ar resolves to Arabic language context', youngo_frontend_language_from_uri('ar') === 'arabic');
    youngo_phase_2u6_5_check('/ar/courses resolves to Arabic language context', youngo_frontend_language_from_uri('ar/courses') === 'arabic');
    youngo_phase_2u6_5_check('Unprefixed home/courses resolves to English language context', youngo_frontend_language_from_uri('home/courses') === 'english');
}

if (function_exists('youngo_frontend_language_switch_path')) {
    $switch_tests = array(
        array('arabic courses with query', 'arabic', 'home/courses?category=robotics&page=2', true, 'ar/courses?category=robotics&page=2'),
        array('english courses with query', 'english', 'ar/courses?category=robotics&page=2', true, 'home/courses?category=robotics&page=2'),
        array('arabic course detail', 'arabic', 'home/course/scratch-coding-for-young-creators/1', true, 'ar/course/scratch-coding-for-young-creators/1'),
        array('english course detail', 'english', 'ar/course/scratch-coding-for-young-creators/1', true, 'home/course/scratch-coding-for-young-creators/1'),
        array('arabic root', 'arabic', '', true, 'ar'),
        array('english root', 'english', 'ar', true, ''),
        array('arabic search query', 'arabic', 'home/search?query=Scratch', true, 'ar/search?query=Scratch'),
        array('english search segment query', 'english', 'ar/search/Scratch?query=Scratch', true, 'home/search/Scratch?query=Scratch'),
        array('query can be omitted', 'arabic', 'home/courses?page=2', false, 'ar/courses'),
        array('payment route remains unlocalized', 'arabic', 'home/payment/1?x=y', true, 'home/payment/1?x=y'),
    );

    foreach ($switch_tests as $test) {
        $actual = youngo_frontend_language_switch_path($test[1], $test[2], $test[3]);
        youngo_phase_2u6_5_check('Language switch path: ' . $test[0], $actual === $test[4], 'expected "' . $test[4] . '", got "' . $actual . '"');
        youngo_phase_2u6_5_check('Language switch path does not generate /en: ' . $test[0], strpos('/' . $actual, '/en') !== 0);
    }
}

if ($shell !== false) {
    youngo_phase_2u6_5_check('Shell uses youngo_frontend_html_lang()', strpos($shell, 'youngo_frontend_html_lang') !== false);
    youngo_phase_2u6_5_check('Shell uses youngo_frontend_html_dir()', strpos($shell, 'youngo_frontend_html_dir') !== false);
    youngo_phase_2u6_5_check('Shell has YounGo language body class', strpos($shell, 'youngo-lang-') !== false);
    youngo_phase_2u6_5_check('Shell has YounGo direction body class', strpos($shell, 'youngo-dir-') !== false);
    youngo_phase_2u6_5_check('Shell has data-youngo-language attribute', strpos($shell, 'data-youngo-language') !== false);
    youngo_phase_2u6_5_check('Shell has data-youngo-dir attribute', strpos($shell, 'data-youngo-dir') !== false);
}

if ($header !== false) {
    youngo_phase_2u6_5_check('Header language switcher exists', strpos($header, 'youngo-language-switcher') !== false);
    youngo_phase_2u6_5_check('Header switcher uses helper-generated URLs', substr_count($header, 'youngo_frontend_language_switch_url') >= 2);
    youngo_phase_2u6_5_check('Header marks active language', strpos($header, 'aria-current') !== false && strpos($header, 'is-active') !== false);
    youngo_phase_2u6_5_check('Header switcher does not link /en', strpos($header, '/en') === false && strpos($header, "site_url('en") === false);
    youngo_phase_2u6_5_check('Header does not use arabic_translated as UI language', strpos($header, 'arabic_translated') === false);
}

if ($css !== false) {
    youngo_phase_2u6_5_check('RTL CSS scopes header direction by html dir', strpos($css, 'html[dir="rtl"] .youngo-header') !== false);
    youngo_phase_2u6_5_check('RTL CSS scopes navigation direction by html dir', strpos($css, 'html[dir="rtl"] .youngo-nav') !== false);
    youngo_phase_2u6_5_check('Language switcher CSS keeps switcher LTR', strpos($css, 'html[dir="rtl"] .youngo-language-switcher') !== false);
}

if ($routes !== false) {
    youngo_phase_2u6_5_check('Routes file still has /ar public aliases', strpos($routes, "\$route['ar/courses']") !== false && strpos($routes, "\$route['ar/course/(:any)/(:num)']") !== false);
    youngo_phase_2u6_5_check('Routes file has no /en route aliases', strpos($routes, "\$route['en") === false && strpos($routes, '$route["en') === false);
}

$changed_files = youngo_phase_2u6_5_changed_files();
$changed_files_text = implode("\n", $changed_files);
youngo_phase_2u6_5_check('No route changes were introduced in this phase', !in_array('application/config/routes.php', $changed_files, true));
youngo_phase_2u6_5_check('No admin shell files changed', strpos($changed_files_text, 'application/views/backend/') === false && strpos($changed_files_text, 'application/controllers/Admin.php') === false);
youngo_phase_2u6_5_check('No checkout/payment/cart/coupon files changed', !preg_match('#(checkout|payment|paymob|paypal|stripe|razorpay|paystack|flutterwave|coupon|shopping_cart|update_cart|apply_coupon|remove_coupon)#i', $changed_files_text));

$changed_source = '';
$changed_runtime_source = '';
foreach ($changed_files as $changed_file) {
    if (preg_match('/\.(php|css|js)$/', $changed_file)) {
        $contents = youngo_phase_2u6_5_read($changed_file);
        if ($contents !== false) {
            $changed_source .= "\n" . $contents;
            if (strpos($changed_file, 'application/') === 0 || strpos($changed_file, 'assets/') === 0) {
                $changed_runtime_source .= "\n" . $contents;
            }
        }
    }
}

youngo_phase_2u6_5_check('No session language writes were added', stripos($changed_source, 'set_userdata(\'language') === false && stripos($changed_source, 'set_userdata("language') === false);
youngo_phase_2u6_5_check('No cookie language writes were added', stripos($changed_source, 'setcookie') === false);
youngo_phase_2u6_5_check('No settings language writes were added', stripos($changed_source, 'update(\'settings') === false && stripos($changed_source, 'update("settings') === false);
youngo_phase_2u6_5_check('No phrase writes were added', stripos($changed_source, 'save_phrase') === false && stripos($changed_source, 'insert(\'language') === false && stripos($changed_source, 'insert("language') === false);
youngo_phase_2u6_5_check('No arabic_translated UI language usage was added', strpos(str_replace($helper === false ? '' : $helper, '', $changed_runtime_source), 'arabic_translated') === false);

youngo_phase_2u6_5_check('Frontend content translation diagnostic still passes', youngo_phase_2u6_5_php_diagnostic_passes('scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php'));
youngo_phase_2u6_5_check('Arabic route alias diagnostic still passes', youngo_phase_2u6_5_php_diagnostic_passes('scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php'));

echo str_repeat('-', 68) . PHP_EOL;
if ($failures === 0) {
    echo 'RESULT: PASS' . PHP_EOL;
    exit(0);
}

echo 'RESULT: FAIL (' . $failures . ' checks failed)' . PHP_EOL;
exit(1);
