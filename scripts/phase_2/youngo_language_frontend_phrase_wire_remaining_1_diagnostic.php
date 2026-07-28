<?php
/**
 * LANGUAGE.FRONTEND.PHRASE.WIRE.REMAINING.1 diagnostic.
 *
 * Read-only source and DB verification for converting remaining safe public
 * labels to the URI-aware YounGo frontend phrase helper.
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

$failures = array();

function yfpr1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function yfpr1_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function yfpr1_rel($root, $path)
{
    return str_replace('\\', '/', substr($path, strlen($root) + 1));
}

function yfpr1_source($root, $relative)
{
    return file_get_contents($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
}

function yfpr1_scan_legacy_calls($root, $files)
{
    $result = array();
    foreach ($files as $file) {
        $source = yfpr1_source($root, $file);
        preg_match_all('/\b(?:get_phrase|site_phrase)\s*\(/', $source, $matches);
        $count = count($matches[0]);
        if ($count > 0) {
            $result[$file] = $count;
        }
    }

    return $result;
}

function yfpr1_seed_keys($root)
{
    $seedFile = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'phase_2' . DIRECTORY_SEPARATOR . 'youngo_language_frontend_phrase_seed_missing_1.php';
    $source = file_get_contents($seedFile);
    preg_match_all("/array\\('key'\\s*=>\\s*'([^']+)'/", $source, $matches);

    return array_values(array_unique($matches[1]));
}

function yfpr1_connect($db, $active_group)
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

function yfpr1_phrase_row($mysqli, $phrase)
{
    $stmt = $mysqli->prepare('SELECT phrase, english, arabic FROM language WHERE phrase = ? ORDER BY phrase_id ASC LIMIT 1');
    $stmt->bind_param('s', $phrase);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

$convertedFiles = array(
    'application/views/frontend/youngo/404.php',
    'application/views/frontend/youngo/account_disable.php',
    'application/views/frontend/youngo/change_password_from_forgot_password.php',
    'application/views/frontend/youngo/forgot_password.php',
    'application/views/frontend/youngo/my_access.php',
    'application/views/frontend/youngo/my_courses.php',
    'application/views/frontend/youngo/new_login_confirmation.php',
    'application/views/frontend/youngo/reload_my_courses.php',
    'application/views/frontend/youngo/update_user_photo.php',
    'application/views/frontend/youngo/user_credentials.php',
    'application/views/frontend/youngo/user_profile.php',
    'application/views/frontend/youngo/verification_code.php',
    'application/views/frontend/youngo/course_page_preview_modal.php',
    'application/views/frontend/youngo/index.php',
);

$deferredFiles = array(
    'application/views/frontend/youngo/cart_items.php',
    'application/views/frontend/youngo/checkout_disabled.php',
    'application/views/frontend/youngo/checkout_order.php',
    'application/views/frontend/youngo/invoice.php',
    'application/views/frontend/youngo/payment_return_disabled.php',
    'application/views/frontend/youngo/purchase_history.php',
    'application/views/frontend/youngo/shopping_cart.php',
    'application/views/frontend/youngo/shopping_cart_inner_view.php',
);

try {
    $allFrontendFiles = array();
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'youngo'));
    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $allFrontendFiles[] = yfpr1_rel($root, $file->getPathname());
        }
    }
    sort($allFrontendFiles);

    $convertedLegacy = yfpr1_scan_legacy_calls($root, $convertedFiles);
    $allLegacy = yfpr1_scan_legacy_calls($root, $allFrontendFiles);
    $nonDeferredLegacy = array();
    foreach ($allLegacy as $file => $count) {
        if (!in_array($file, $deferredFiles, true)) {
            $nonDeferredLegacy[$file] = $count;
        }
    }

    $convertedSource = '';
    foreach ($convertedFiles as $file) {
        $convertedSource .= "\n" . yfpr1_source($root, $file);
    }

    $forbiddenRoutePattern = '/\b(?:href|action)\s*=\s*["\'][^"\']*(?:paymob|\/payment|checkout\/order|shopping_cart|add_to_cart|handle_cart_items)[^"\']*["\']/i';
    preg_match_all($forbiddenRoutePattern, $convertedSource, $forbiddenRoutes);

    $gitChanged = array_filter(explode("\n", trim(shell_exec('git -C ' . escapeshellarg($root) . ' diff --name-only'))));
    $paymentChanged = array_values(array_filter($gitChanged, function ($path) {
        return preg_match('/paymob|payment|checkout|shopping_cart|cart_items|invoice|purchase_history/i', $path);
    }));

    $seedKeys = yfpr1_seed_keys($root);
    $mysqli = yfpr1_connect($db, $active_group);
    $seeded = array(
        'keys_checked' => count($seedKeys),
        'missing' => 0,
        'blank_english' => 0,
        'blank_arabic' => 0,
        'english_with_arabic_script' => 0,
        'arabic_without_arabic_script' => 0,
    );

    foreach ($seedKeys as $key) {
        $row = yfpr1_phrase_row($mysqli, $key);
        if (!$row) {
            $seeded['missing']++;
            continue;
        }

        $english = (string) $row['english'];
        $arabic = (string) $row['arabic'];

        if (trim($english) === '') {
            $seeded['blank_english']++;
        }
        if (trim($arabic) === '') {
            $seeded['blank_arabic']++;
        }
        if (preg_match('/\p{Arabic}/u', $english)) {
            $seeded['english_with_arabic_script']++;
        }
        if (!preg_match('/\p{Arabic}/u', $arabic)) {
            $seeded['arabic_without_arabic_script']++;
        }
    }

    $indexSource = yfpr1_source($root, 'application/views/frontend/youngo/index.php');
    $homeControllerSource = yfpr1_source($root, 'application/controllers/Home.php');
    $checks = array(
        'converted_file_count' => count($convertedFiles),
        'converted_files_have_no_legacy_get_or_site_phrase' => empty($convertedLegacy),
        'non_deferred_frontend_views_have_no_legacy_get_or_site_phrase' => empty($nonDeferredLegacy),
        'remaining_legacy_calls_are_deferred_only' => !empty($allLegacy) && empty($nonDeferredLegacy),
        'public_page_title_wrapper_exists' => strpos($homeControllerSource, 'private function youngo_frontend_public_phrase') !== false,
        'public_page_titles_use_uri_phrase_wrapper' => strpos($homeControllerSource, "\$page_data['page_title'] = \$this->youngo_frontend_public_phrase('login')") !== false
            && strpos($homeControllerSource, "\$page_data['page_title'] = \$this->youngo_frontend_public_phrase('courses')") !== false
            && strpos($homeControllerSource, "\$page_data['page_title'] = \$this->youngo_frontend_public_phrase('Contact us')") !== false,
        'placeholder_copy_is_phrase_backed' => strpos($indexSource, "youngo_frontend_phrase('youngo_demo_page')") !== false
            && strpos($indexSource, "youngo_frontend_phrase('this_page_is_not_part_of_the_current_public_demo_flow.')") !== false,
        'no_arabic_translated_in_converted_views' => strpos($convertedSource, 'arabic_translated') === false,
        'no_forbidden_payment_checkout_routes_in_converted_views' => count($forbiddenRoutes[0]) === 0,
        'no_payment_checkout_files_changed' => empty($paymentChanged),
        'seed_key_count_is_83' => count($seedKeys) === 83,
        'seeded_keys_resolve_for_english_and_arabic' => $seeded['missing'] === 0 && $seeded['blank_english'] === 0 && $seeded['blank_arabic'] === 0,
        'english_seed_values_do_not_use_arabic_script' => $seeded['english_with_arabic_script'] === 0,
        'arabic_seed_values_use_arabic_script' => $seeded['arabic_without_arabic_script'] === 0,
    );

    yfpr1_print('Converted files', $convertedFiles);
    yfpr1_print('Deferred legacy files', $allLegacy);
    yfpr1_print('Seeded phrase readiness', $seeded);
    yfpr1_print('Diagnostic checks', $checks);

    foreach ($checks as $label => $ok) {
        if (is_bool($ok)) {
            yfpr1_assert($failures, $ok, 'Diagnostic check failed: ' . $label);
        }
    }

    if (!empty($failures)) {
        yfpr1_print('FAILURES', $failures);
        exit(1);
    }

    yfpr1_print('Result', 'PASS: remaining safe public frontend labels are URI-aware; deferred operational/payment surfaces were left unchanged.');
    exit(0);
} catch (Throwable $e) {
    yfpr1_print('Result', 'FAIL: ' . $e->getMessage());
    exit(1);
}
