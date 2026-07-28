<?php
/**
 * DEMO.FIX.2 Blog i18n/content diagnostic.
 *
 * Read-only: source inspection and SELECT-only database checks.
 */

define('BASEPATH', true);
defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');

$root = realpath(__DIR__ . '/../..');
$failures = array();
$warnings = array();
$infos = array();

function yf2_path($relative)
{
    global $root;
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function yf2_add(&$bucket, $message)
{
    $bucket[] = $message;
}

function yf2_read($relative)
{
    $path = yf2_path($relative);
    return is_file($path) ? file_get_contents($path) : false;
}

function yf2_count($mysqli, $table)
{
    $table = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $exists = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    if (!$exists || $exists->num_rows === 0) {
        return 'missing';
    }

    $result = $mysqli->query("SELECT COUNT(*) FROM `$table`");
    if (!$result) {
        return 'error';
    }

    $row = $result->fetch_row();
    return $row ? (int) $row[0] : 0;
}

yf2_add($infos, 'Diagnostic is read-only and does not intentionally write DB/session/cookie/settings data.');

$report = 'docs/qa/youngo_demo_fix_2_blog_i18n_report.md';
is_file(yf2_path($report)) ? yf2_add($infos, "Report exists: $report") : yf2_add($failures, "Missing report: $report");

$requiredFiles = array(
    'application/controllers/Admin.php',
    'application/controllers/Blog.php',
    'application/models/Crud_model.php',
    'application/views/backend/admin/blog_add.php',
    'application/views/backend/admin/blog_edit.php',
    'application/views/frontend/youngo/blogs.php',
    'application/views/frontend/youngo/blog_details.php',
    'application/views/frontend/youngo/contact_us.php',
    'scripts/phase_2/youngo_demo_fix_2_blog_i18n_schema.php',
);

foreach ($requiredFiles as $file) {
    is_file(yf2_path($file)) ? yf2_add($infos, "Found $file") : yf2_add($failures, "Missing $file");
}

$admin = yf2_read('application/controllers/Admin.php');
$blogController = yf2_read('application/controllers/Blog.php');
$crud = yf2_read('application/models/Crud_model.php');
$schemaScript = yf2_read('scripts/phase_2/youngo_demo_fix_2_blog_i18n_schema.php');
$blogAdd = yf2_read('application/views/backend/admin/blog_add.php');
$blogEdit = yf2_read('application/views/backend/admin/blog_edit.php');
$blogView = yf2_read('application/views/frontend/youngo/blogs.php');
$blogDetail = yf2_read('application/views/frontend/youngo/blog_details.php');
$contactView = yf2_read('application/views/frontend/youngo/contact_us.php');

if ($admin !== false) {
    $unsafeFlashPatterns = array(
        "get_" . "phrase('blog_added_successfully'",
        "get_" . "phrase('blog_updated_successfully'",
        "get_" . "phrase('blog_category_added_successfully'",
        "get_" . "phrase('contact_information_updated_successfully'",
        "site_" . "phrase('blog_added_successfully'",
    );
    $unsafe = array();
    foreach ($unsafeFlashPatterns as $pattern) {
        if (strpos($admin, $pattern) !== false) {
            $unsafe[] = $pattern;
        }
    }
    empty($unsafe) ? yf2_add($infos, 'Blog/Contact content flash paths no longer use the known missing phrase keys.') : yf2_add($failures, 'Unsafe content flash phrase patterns found: ' . implode(', ', $unsafe));
} else {
    yf2_add($failures, 'Admin.php could not be read.');
}

if ($blogController !== false && strpos($blogController, 'Blog details') !== false && strpos($blogController, "site_" . "phrase('blog_details'") !== false && strpos($blogController, '$isYoungoTheme') !== false) {
    yf2_add($infos, 'Blog detail page title is YounGo-scoped away from missing legacy phrase insertion.');
} else {
    yf2_add($failures, 'Blog detail phrase-safety markers are missing.');
}

if ($schemaScript !== false && strpos($schemaScript, 'CREATE TABLE IF NOT EXISTS `youngo_blog_translations`') !== false && strpos($schemaScript, "CHECK (`language_code` IN ('english', 'arabic'))") !== false && strpos($schemaScript, 'language table were not touched') !== false) {
    yf2_add($infos, 'Schema helper is reproducible, idempotent, and documents english/arabic language-code boundaries.');
} else {
    yf2_add($failures, 'Schema helper is missing required reproducibility markers.');
}

if ($crud !== false && strpos($crud, 'youngo_blog_translations') !== false && strpos($crud, 'youngo_save_blog_translations_from_post') !== false) {
    yf2_add($infos, 'Crud_model contains minimal YounGo Blog translation persistence.');
} else {
    yf2_add($failures, 'Crud_model Blog translation persistence markers are missing.');
}

if ($blogAdd !== false && strpos($blogAdd, 'name="arabic_title"') !== false && strpos($blogAdd, 'name="arabic_excerpt"') !== false && strpos($blogAdd, 'name="arabic_description"') !== false) {
    yf2_add($infos, 'Blog add form contains minimal Arabic fields.');
} else {
    yf2_add($failures, 'Blog add form is missing minimal Arabic fields.');
}

if ($blogEdit !== false && strpos($blogEdit, 'name="arabic_title"') !== false && strpos($blogEdit, 'youngo_blog_arabic_translation') !== false) {
    yf2_add($infos, 'Blog edit form loads/edits Arabic translation fields.');
} else {
    yf2_add($failures, 'Blog edit form Arabic translation markers are missing.');
}

if ($blogView !== false && strpos($blogView, 'youngo_blog_localized_rows') !== false && strpos($blogView, 'youngo_blog_translation_row') !== false) {
    yf2_add($infos, 'YounGo Blog listing is translation-aware and not fallback-only when Blog rows exist.');
} else {
    yf2_add($failures, 'YounGo Blog listing translation markers are missing.');
}

if ($contactView !== false && !preg_match('/<form\b/i', $contactView) && !preg_match('/\saction\s*=/i', $contactView)) {
    yf2_add($infos, 'YounGo Contact view remains display-only with no public form/action.');
} else {
    yf2_add($failures, 'YounGo Contact view appears to expose a form/action.');
}

if ($blogDetail !== false && !preg_match('/<form\b/i', $blogDetail) && !preg_match('/comment/i', $blogDetail)) {
    yf2_add($infos, 'YounGo Blog detail has no public comment form markers.');
} else {
    yf2_add($warnings, 'Review YounGo Blog detail for comment/form wording.');
}

$youngoFiles = glob(yf2_path('application/views/frontend/youngo') . DIRECTORY_SEPARATOR . '*.php');
$enHits = array();
foreach ($youngoFiles as $file) {
    $contents = file_get_contents($file);
    if (preg_match('/href\s*=\s*["\'][^"\']*\/en(?:\/|["\'?])/i', $contents)) {
        $enHits[] = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $file));
    }
}
empty($enHits) ? yf2_add($infos, 'No /en link patterns found in YounGo frontend views.') : yf2_add($failures, '/en links found: ' . implode(', ', $enHits));

$diff = trim((string) shell_exec('git diff --name-only'));
$changed = $diff === '' ? array() : preg_split('/\r\n|\r|\n/', $diff);
$forbidden = array();
foreach ($changed as $file) {
    if ($file === '') {
        continue;
    }
    if (preg_match('#^application/language/#', $file) || preg_match('/payment|checkout|coupon|paymob/i', $file) || preg_match('#application/views/frontend/default#', $file)) {
        $forbidden[] = $file;
    }
}
empty($forbidden) ? yf2_add($infos, 'No language JSON/default-theme/payment/checkout/coupon/Paymob source changes detected.') : yf2_add($failures, 'Forbidden changed files detected: ' . implode(', ', $forbidden));

require yf2_path('application/config/database.php');
$cfg = isset($db['default']) ? $db['default'] : array();
$mysqli = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);
if ($mysqli->connect_errno) {
    yf2_add($failures, 'DB connection failed: ' . $mysqli->connect_error);
} else {
    $mysqli->set_charset('utf8mb4');
    $counts = array();
    foreach (array('language', 'blogs', 'blog_category', 'blog_comments', 'contact', 'ci_sessions', 'payment', 'youngo_checkout_orders', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_coupon_usages', 'youngo_blog_translations') as $table) {
        $counts[$table] = yf2_count($mysqli, $table);
    }
    yf2_add($infos, 'DB counts: ' . json_encode($counts));

    if ($counts['blogs'] >= 4) {
        yf2_add($infos, 'Blog rows exist for the demo.');
    } else {
        yf2_add($failures, 'Expected at least 4 Blog rows; found ' . $counts['blogs']);
    }

    if ($counts['blog_category'] >= 3) {
        yf2_add($infos, 'Blog categories exist for the demo.');
    } else {
        yf2_add($failures, 'Expected at least 3 Blog categories; found ' . $counts['blog_category']);
    }

    if ($counts['youngo_blog_translations'] !== 'missing') {
        $invalid = $mysqli->query("SELECT COUNT(*) FROM youngo_blog_translations WHERE language_code NOT IN ('english','arabic') OR language_code = 'arabic_translated'");
        $invalidCount = $invalid ? (int) $invalid->fetch_row()[0] : -1;
        $arabic = $mysqli->query("SELECT COUNT(DISTINCT blog_id) FROM youngo_blog_translations WHERE language_code = 'arabic' AND title <> ''");
        $arabicCount = $arabic ? (int) $arabic->fetch_row()[0] : 0;
        $english = $mysqli->query("SELECT COUNT(DISTINCT blog_id) FROM youngo_blog_translations WHERE language_code = 'english' AND title <> ''");
        $englishCount = $english ? (int) $english->fetch_row()[0] : 0;

        $invalidCount === 0 ? yf2_add($infos, 'Blog translation languages are limited to english/arabic.') : yf2_add($failures, 'Invalid Blog translation language rows: ' . $invalidCount);
        $arabicCount >= 4 ? yf2_add($infos, 'Arabic Blog translation coverage exists for demo posts.') : yf2_add($failures, 'Arabic Blog translation coverage is incomplete: ' . $arabicCount);
        $englishCount >= 4 ? yf2_add($infos, 'English Blog translation coverage exists for demo posts.') : yf2_add($warnings, 'English Blog translation coverage is below 4: ' . $englishCount);
    } else {
        yf2_add($failures, 'youngo_blog_translations table is missing.');
    }

    if ($counts['payment'] === 0 && $counts['youngo_checkout_orders'] === 0 && $counts['youngo_course_access'] === 0 && $counts['youngo_user_subscriptions'] === 0 && $counts['youngo_manual_grants'] === 0 && $counts['youngo_coupon_usages'] === 0) {
        yf2_add($infos, 'Protected payment/access/checkout/coupon tables remain clean.');
    } else {
        yf2_add($failures, 'Protected payment/access/checkout/coupon table count changed unexpectedly.');
    }

    $qmarks = $mysqli->query("SELECT COUNT(*) FROM language WHERE arabic LIKE '%???%'");
    if ($qmarks) {
        yf2_add($warnings, 'Known Arabic language-table qmark rows: ' . (int) $qmarks->fetch_row()[0]);
    }
}

echo "DEMO.FIX.2 Blog i18n Diagnostic\n";
foreach ($infos as $message) {
    echo "[INFO] $message\n";
}
foreach ($warnings as $message) {
    echo "[WARN] $message\n";
}
foreach ($failures as $message) {
    echo "[FAIL] $message\n";
}

if (!empty($failures)) {
    echo "RESULT: FAIL\n";
    exit(1);
}

echo empty($warnings) ? "RESULT: PASS\n" : "RESULT: PASS_WITH_WARNINGS\n";
