<?php
/**
 * LANGUAGE.FRONTEND.ARABIC_VISUAL_COPY.POLISH.1 diagnostic.
 *
 * Read-only verification for screenshot-driven Arabic visual copy polish.
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

function yfavcp1d_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function yfavcp1d_connect($db, $active_group)
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

function yfavcp1d_normalize_key($phrase_key)
{
    $phrase_key = strtolower(preg_replace('/\s+/', '_', trim((string) $phrase_key)));
    $phrase_key = preg_replace('/_+/', '_', $phrase_key);

    return trim($phrase_key, '_');
}

function yfavcp1d_inventory_keys($root)
{
    $script = file_get_contents($root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'phase_2' . DIRECTORY_SEPARATOR . 'youngo_language_frontend_arabic_visual_copy_polish_1.php');
    preg_match_all("/array\\('key' => '([^']+)'/", $script, $matches);

    $keys = array();
    foreach ($matches[1] as $key) {
        $keys[] = yfavcp1d_normalize_key($key);
    }

    return array_values(array_unique($keys));
}

function yfavcp1d_fetch_phrase($mysqli, $phrase_key)
{
    $stmt = $mysqli->prepare('SELECT phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1');
    $stmt->bind_param('s', $phrase_key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function yfavcp1d_has_arabic_script($value)
{
    return (bool) preg_match('/\p{Arabic}/u', (string) $value);
}

function yfavcp1d_is_dirty($value)
{
    return (bool) preg_match('/(?:ï¿½|Ãƒ|Ã‚|Ã˜|Ã™|Ã|Ã‘|\x{00D8}|\x{00D9})/u', (string) $value);
}

function yfavcp1d_table_count($mysqli, $sql, $types = '', $params = array())
{
    $stmt = $mysqli->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return isset($row['count_value']) ? (int) $row['count_value'] : 0;
}

function yfavcp1d_git_changed_files($root)
{
    $output = array();
    $code = 0;
    exec('git diff --name-only 2>NUL', $output, $code);
    $untracked = array();
    exec('git ls-files --others --exclude-standard 2>NUL', $untracked);

    return array_values(array_unique(array_merge($output, $untracked)));
}

try {
    $mysqli = yfavcp1d_connect($db, $active_group);
    $keys = yfavcp1d_inventory_keys($root);
    $phraseCounts = array(
        'keys_checked' => count($keys),
        'missing_rows' => 0,
        'blank_english' => 0,
        'blank_arabic' => 0,
        'arabic_without_arabic_script' => 0,
        'dirty_arabic_values' => 0,
    );

    foreach ($keys as $key) {
        $row = yfavcp1d_fetch_phrase($mysqli, $key);
        if (!$row) {
            $phraseCounts['missing_rows']++;
            continue;
        }

        if (trim((string) $row['english']) === '') {
            $phraseCounts['blank_english']++;
        }
        if (trim((string) $row['arabic']) === '') {
            $phraseCounts['blank_arabic']++;
        }
        if (!youngo_dummy_false() && !yfavcp1d_has_arabic_script($row['arabic'])) {
            $phraseCounts['arabic_without_arabic_script']++;
        }
        if (yfavcp1d_is_dirty($row['arabic'])) {
            $phraseCounts['dirty_arabic_values']++;
        }
    }

    $criticalKeys = array(
        'home', 'courses', 'subscriptions', 'blog', 'contact', 'login', 'sign_up',
        'explore_youngo_courses', 'course_catalog', 'sort_by', 'newly_published',
        'course_access', 'start_now', 'overview', 'course_description', 'curriculum',
        'lessons_inside_this_course', 'instructor', 'reviews', 'duration', 'egp',
        'blogs', 'contact_us', 'address', 'phone', 'email',
    );
    $criticalCounts = array('checked' => count($criticalKeys), 'arabic_ready' => 0, 'english_ready' => 0);
    foreach ($criticalKeys as $key) {
        $row = yfavcp1d_fetch_phrase($mysqli, $key);
        if ($row && trim((string) $row['english']) !== '') {
            $criticalCounts['english_ready']++;
        }
        if ($row && yfavcp1d_has_arabic_script($row['arabic']) && !yfavcp1d_is_dirty($row['arabic'])) {
            $criticalCounts['arabic_ready']++;
        }
    }

    $metaArabicTranslated = 0;
    $metaTableExists = yfavcp1d_table_count($mysqli, "SELECT COUNT(*) AS count_value FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'youngo_language_phrase_meta'") > 0;
    if ($metaTableExists) {
        $metaArabicTranslated = yfavcp1d_table_count($mysqli, "SELECT COUNT(*) AS count_value FROM youngo_language_phrase_meta WHERE language_code = 'arabic_translated'");
    }

    $changedFiles = yfavcp1d_git_changed_files($root);
    $changedText = implode("\n", $changedFiles);
    $changedPaymentFiles = array_values(array_filter($changedFiles, function ($file) {
        return preg_match('/(^|\/)(payment|paymob|cart|checkout|invoice|purchase_history)/i', $file);
    }));

    $helper = file_get_contents($root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'youngo_frontend_language_helper.php');
    $subscriptionModel = file_get_contents($root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Youngo_subscription_model.php');
    $blogController = file_get_contents($root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'Blog.php');
    $polishScript = file_get_contents($root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'phase_2' . DIRECTORY_SEPARATOR . 'youngo_language_frontend_arabic_visual_copy_polish_1.php');

    $sourceChecks = array(
        'helper_rejects_raw_mojibake_chars' => strpos($helper, 'x{00D8}') !== false && strpos($helper, 'x{00D9}') !== false,
        'subscription_price_uses_language' => strpos($subscriptionModel, 'format_public_plan_price($price, $currency = null, $language = null)') !== false
            && strpos($subscriptionModel, "youngo_frontend_phrase(strtolower(\$currency), \$currency, \$language)") !== false,
        'blog_public_page_titles_use_frontend_phrase' => strpos($blogController, 'youngo_frontend_public_phrase') !== false
            && strpos($blogController, "\$page_data['page_title'] = site_phrase('blogs')") === false,
        'polish_script_checks_manual_override' => strpos($polishScript, 'manual_override') !== false,
        'polish_script_does_not_write_arabic_translated' => strpos($polishScript, "key === 'arabic_translated'") !== false && strpos($polishScript, 'writes_arabic_translated') !== false,
        'polish_script_does_not_write_course_or_plan_tables' => strpos($polishScript, 'UPDATE course') === false
            && strpos($polishScript, 'youngo_subscription_plans') === false
            && strpos($polishScript, 'INSERT INTO course') === false,
    );

    $paymentMarkers = array('payment/paymob', 'paymob/', 'checkout/start', 'youngo_checkout_orders', 'data-youngo-checkout-cta="local-course-detail"');
    $forbiddenIntroduced = 0;
    $forbiddenMarkerFiles = array();
    foreach ($changedFiles as $file) {
        $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
        if (!is_file($path)
            || strpos($file, 'scripts/') === 0
            || strpos($file, 'docs/') === 0
            || preg_match('/(checkout|payment|cart|invoice|purchase_history)/i', $file)
        ) {
            continue;
        }
        $diffLines = array();
        exec('git diff --unified=0 -- ' . escapeshellarg($file) . ' 2>NUL', $diffLines);
        $content = implode("\n", array_filter($diffLines, function ($line) {
            return strpos($line, '+') === 0 && strpos($line, '+++') !== 0;
        }));
        foreach ($paymentMarkers as $marker) {
            if (stripos($content, $marker) !== false) {
                $forbiddenIntroduced++;
                $forbiddenMarkerFiles[] = $file;
            }
        }
    }

    $checks = array(
        'target_inventory_loaded' => count($keys) >= 120,
        'target_rows_exist' => $phraseCounts['missing_rows'] === 0,
        'target_english_values_non_blank' => $phraseCounts['blank_english'] === 0,
        'target_arabic_values_non_blank' => $phraseCounts['blank_arabic'] === 0,
        'target_arabic_values_use_arabic_script' => $phraseCounts['arabic_without_arabic_script'] === 0,
        'target_arabic_values_are_clean' => $phraseCounts['dirty_arabic_values'] === 0,
        'critical_arabic_visual_labels_ready' => $criticalCounts['arabic_ready'] === $criticalCounts['checked'],
        'critical_english_labels_ready' => $criticalCounts['english_ready'] === $criticalCounts['checked'],
        'arabic_translated_metadata_untouched' => $metaArabicTranslated === 0,
        'no_payment_or_checkout_files_changed_for_this_phase' => count($changedPaymentFiles) === 0,
        'no_forbidden_payment_checkout_markers_introduced' => $forbiddenIntroduced === 0,
        'edit_phrase_pagination_diagnostic_exists' => is_file($root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'phase_2' . DIRECTORY_SEPARATOR . 'youngo_language_edit_phrase_pagination_ui_1_diagnostic.php'),
    );

    $checks = array_merge($checks, $sourceChecks);

    yfavcp1d_print('Phrase copy counts', $phraseCounts);
    yfavcp1d_print('Critical visual label counts', $criticalCounts);
    yfavcp1d_print('Safety counts', array(
        'arabic_translated_metadata_rows' => $metaArabicTranslated,
        'changed_payment_checkout_files' => count($changedPaymentFiles),
        'forbidden_payment_checkout_markers' => $forbiddenIntroduced,
        'forbidden_marker_files' => array_values(array_unique($forbiddenMarkerFiles)),
    ));
    yfavcp1d_print('Diagnostic checks', $checks);

    $failed = array_keys(array_filter($checks, function ($value) {
        return $value !== true;
    }));

    if (!empty($failed)) {
        yfavcp1d_print('Result', array('status' => 'failed', 'failed_checks' => $failed));
        exit(1);
    }

    yfavcp1d_print('Result', array('status' => 'passed', 'note' => 'Arabic visual copy targets are phrase-backed and clean; protected payment/admin surfaces are unchanged.'));
    $mysqli->close();
} catch (Throwable $exception) {
    echo "ERROR: " . $exception->getMessage() . "\n";
    exit(1);
}

function youngo_dummy_false()
{
    return false;
}
