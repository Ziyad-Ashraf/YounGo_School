<?php
/**
 * Phase 2U.3 English translation seed.
 *
 * Idempotently creates English translation rows from existing canonical
 * course/category/section/lesson content. It does not modify canonical tables.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(dirname(__DIR__));
define('ENVIRONMENT', 'development');
define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config/database.php';

$config = $db['default'];
$mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_errno) {
    echo "Database connection failed without exposing credentials.\n";
    exit(2);
}
$mysqli->set_charset('utf8');

function u3_seed_section($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
}

function u3_seed_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $result = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return $result && $result->num_rows > 0;
}

function u3_seed_column_exists($mysqli, $table, $column)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }

    $result = $mysqli->query("SHOW COLUMNS FROM `{$table}` LIKE '" . $mysqli->real_escape_string($column) . "'");
    return $result && $result->num_rows > 0;
}

function u3_seed_count($mysqli, $table)
{
    if (!u3_seed_table_exists($mysqli, $table)) {
        return null;
    }

    $result = $mysqli->query("SELECT COUNT(*) AS c FROM `{$table}`");
    $row = $result ? $result->fetch_assoc() : array('c' => null);
    return isset($row['c']) ? (int) $row['c'] : null;
}

function u3_seed_insert_select_missing($mysqli, $sql)
{
    if (!$mysqli->query($sql)) {
        throw new Exception('Seed query failed without exposing credentials: ' . $mysqli->error);
    }

    return $mysqli->affected_rows;
}

$required_tables = array(
    'youngo_course_translations',
    'youngo_category_translations',
    'youngo_section_translations',
    'youngo_lesson_translations',
);
foreach ($required_tables as $table) {
    if (!u3_seed_table_exists($mysqli, $table)) {
        u3_seed_section('Result', array(
            'status' => 'FAIL',
            'reason' => "{$table} does not exist. Apply Phase 2U.3 schema first.",
        ));
        exit(1);
    }
}

$now = time();
$before = array(
    'course' => u3_seed_count($mysqli, 'course'),
    'category' => u3_seed_count($mysqli, 'category'),
    'section' => u3_seed_count($mysqli, 'section'),
    'lesson' => u3_seed_count($mysqli, 'lesson'),
    'youngo_course_translations' => u3_seed_count($mysqli, 'youngo_course_translations'),
    'youngo_category_translations' => u3_seed_count($mysqli, 'youngo_category_translations'),
    'youngo_section_translations' => u3_seed_count($mysqli, 'youngo_section_translations'),
    'youngo_lesson_translations' => u3_seed_count($mysqli, 'youngo_lesson_translations'),
);
u3_seed_section('Before counts', $before);

$mysqli->begin_transaction();
try {
    $course_seeded = u3_seed_insert_select_missing($mysqli, "
        INSERT INTO youngo_course_translations
            (course_id, language_code, title, slug, short_description, description, outcomes, requirements, faqs, seo_title, meta_keywords, meta_description, created_at, updated_at)
        SELECT
            c.id,
            'english',
            c.title,
            NULL,
            c.short_description,
            c.description,
            c.outcomes,
            c.requirements,
            c.faqs,
            c.title,
            c.meta_keywords,
            c.meta_description,
            {$now},
            {$now}
        FROM course c
        LEFT JOIN youngo_course_translations t
            ON t.course_id = c.id AND t.language_code = 'english'
        WHERE t.id IS NULL
    ");

    $category_description = u3_seed_column_exists($mysqli, 'category', 'description') ? 'c.description' : 'NULL';
    $category_seeded = u3_seed_insert_select_missing($mysqli, "
        INSERT INTO youngo_category_translations
            (category_id, language_code, name, slug, description, created_at, updated_at)
        SELECT
            c.id,
            'english',
            c.name,
            c.slug,
            {$category_description},
            {$now},
            {$now}
        FROM category c
        LEFT JOIN youngo_category_translations t
            ON t.category_id = c.id AND t.language_code = 'english'
        WHERE t.id IS NULL
    ");

    $section_seeded = u3_seed_insert_select_missing($mysqli, "
        INSERT INTO youngo_section_translations
            (section_id, language_code, title, created_at, updated_at)
        SELECT
            s.id,
            'english',
            s.title,
            {$now},
            {$now}
        FROM section s
        LEFT JOIN youngo_section_translations t
            ON t.section_id = s.id AND t.language_code = 'english'
        WHERE t.id IS NULL
    ");

    $lesson_text_content = u3_seed_column_exists($mysqli, 'lesson', 'text_content') ? 'l.text_content' : 'NULL';
    $lesson_seeded = u3_seed_insert_select_missing($mysqli, "
        INSERT INTO youngo_lesson_translations
            (lesson_id, language_code, title, summary, text_content, created_at, updated_at)
        SELECT
            l.id,
            'english',
            l.title,
            l.summary,
            {$lesson_text_content},
            {$now},
            {$now}
        FROM lesson l
        LEFT JOIN youngo_lesson_translations t
            ON t.lesson_id = l.id AND t.language_code = 'english'
        WHERE t.id IS NULL
    ");

    $mysqli->commit();
} catch (Throwable $e) {
    $mysqli->rollback();
    u3_seed_section('Result', array(
        'status' => 'FAIL',
        'reason' => $e->getMessage(),
    ));
    exit(1);
}

$after = array(
    'course' => u3_seed_count($mysqli, 'course'),
    'category' => u3_seed_count($mysqli, 'category'),
    'section' => u3_seed_count($mysqli, 'section'),
    'lesson' => u3_seed_count($mysqli, 'lesson'),
    'youngo_course_translations' => u3_seed_count($mysqli, 'youngo_course_translations'),
    'youngo_category_translations' => u3_seed_count($mysqli, 'youngo_category_translations'),
    'youngo_section_translations' => u3_seed_count($mysqli, 'youngo_section_translations'),
    'youngo_lesson_translations' => u3_seed_count($mysqli, 'youngo_lesson_translations'),
);

u3_seed_section('Seeded rows', array(
    'youngo_course_translations' => $course_seeded,
    'youngo_category_translations' => $category_seeded,
    'youngo_section_translations' => $section_seeded,
    'youngo_lesson_translations' => $lesson_seeded,
));
u3_seed_section('After counts', $after);
u3_seed_section('Result', array('status' => 'PASS'));
