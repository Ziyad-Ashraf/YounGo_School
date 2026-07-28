<?php
/**
 * PAYMENT.MANUAL.INSTAPAY.ADMIN.REVIEW.UI.BROWSER.QA.1
 *
 * Authenticated local HTTP/session QA for the read-only manual Instapay admin
 * review UI. This avoids credential handling by inserting temporary
 * CodeIgniter session rows for existing root-admin and learner accounts, then
 * deleting those sessions during cleanup.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
defined('BASEPATH') || define('BASEPATH', $root . '/system/');
defined('APPPATH') || define('APPPATH', $root . '/application/');
defined('FCPATH') || define('FCPATH', $root . '/');
defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');

$checks = array();
$details = array(
    'base_url' => 'http://school.local',
    'backup_path' => '',
    'backup_size' => '',
    'backup_sha256' => '',
    'cleanup' => 'not_started',
    'admin_session_id' => '',
    'learner_session_id' => '',
    'order_id' => '',
    'submission_id' => '',
);

$fixture_session_ids = array();
$fixture_submission_ids = array();
$fixture_order_ids = array();
$fixture_files = array();

function ymiarb1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ymiarb1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ymiarb1_connect()
{
    $config = ymiarb1_db_config();
    $host = isset($config['hostname']) ? $config['hostname'] : 'localhost';
    $port = null;
    if (strpos($host, ':') !== false && substr_count($host, ':') === 1) {
        list($host, $port) = explode(':', $host, 2);
        $port = (int) $port;
    }

    $mysqli = @new mysqli(
        $host,
        isset($config['username']) ? $config['username'] : '',
        isset($config['password']) ? $config['password'] : '',
        isset($config['database']) ? $config['database'] : '',
        $port ?: null
    );

    if ($mysqli->connect_errno) {
        throw new RuntimeException('Database connection failed.');
    }

    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function ymiarb1_ident($name)
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function ymiarb1_sql_value($mysqli, $value)
{
    return $value === null ? 'NULL' : "'" . $mysqli->real_escape_string((string) $value) . "'";
}

function ymiarb1_create_backup($mysqli, $root)
{
    $backup_dir = dirname($root) . '/backups';
    if (!is_dir($backup_dir) && !mkdir($backup_dir, 0777, true)) {
        return array('ok' => false, 'error' => 'backup_dir_failed');
    }

    $path = $backup_dir . '/youngo_school_before_payment_manual_instapay_admin_review_ui_browser_qa_1_' . date('Y_m_d_His') . '.sql';
    $fh = fopen($path, 'wb');
    if (!$fh) {
        return array('ok' => false, 'error' => 'backup_file_failed');
    }

    fwrite($fh, "-- YounGo backup before PAYMENT.MANUAL.INSTAPAY.ADMIN.REVIEW.UI.BROWSER.QA.1\n");
    fwrite($fh, "-- Created at: " . date('c') . "\n");
    fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    $result = $mysqli->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    if (!$result) {
        fclose($fh);
        return array('ok' => false, 'error' => 'table_list_failed');
    }

    while ($table_row = $result->fetch_array(MYSQLI_NUM)) {
        $table = $table_row[0];
        $create = $mysqli->query('SHOW CREATE TABLE ' . ymiarb1_ident($table));
        if (!$create) {
            continue;
        }

        $create_row = $create->fetch_assoc();
        fwrite($fh, "\n-- Table " . $table . "\n");
        fwrite($fh, 'DROP TABLE IF EXISTS ' . ymiarb1_ident($table) . ";\n");
        fwrite($fh, $create_row['Create Table'] . ";\n\n");

        $rows = $mysqli->query('SELECT * FROM ' . ymiarb1_ident($table));
        if (!$rows) {
            continue;
        }

        while ($data = $rows->fetch_assoc()) {
            $columns = array();
            $values = array();
            foreach ($data as $column => $value) {
                $columns[] = ymiarb1_ident($column);
                $values[] = ymiarb1_sql_value($mysqli, $value);
            }
            fwrite($fh, 'INSERT INTO ' . ymiarb1_ident($table) . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n");
        }
    }

    fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);

    return array(
        'ok' => true,
        'path' => $path,
        'size' => filesize($path),
        'sha256' => hash_file('sha256', $path),
    );
}

function ymiarb1_table_columns($mysqli, $table)
{
    $columns = array();
    $result = $mysqli->query('SHOW COLUMNS FROM ' . ymiarb1_ident($table));
    if (!$result) {
        return $columns;
    }

    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = true;
    }

    return $columns;
}

function ymiarb1_insert_filtered($mysqli, $table, $data)
{
    $columns = ymiarb1_table_columns($mysqli, $table);
    $filtered = array();
    foreach ($data as $field => $value) {
        if (isset($columns[$field])) {
            $filtered[$field] = $value;
        }
    }

    if (empty($filtered)) {
        throw new RuntimeException('No insertable columns for ' . $table);
    }

    $fields = array();
    $values = array();
    foreach ($filtered as $field => $value) {
        $fields[] = ymiarb1_ident($field);
        $values[] = ymiarb1_sql_value($mysqli, $value);
    }

    $sql = 'INSERT INTO ' . ymiarb1_ident($table) . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
    if (!$mysqli->query($sql)) {
        throw new RuntimeException('Insert failed for ' . $table . ': ' . $mysqli->error);
    }

    return (int) $mysqli->insert_id;
}

function ymiarb1_count($mysqli, $table)
{
    $result = $mysqli->query('SELECT COUNT(*) AS total FROM ' . ymiarb1_ident($table));
    if (!$result) {
        return null;
    }

    $row = $result->fetch_assoc();
    return (int) $row['total'];
}

function ymiarb1_counts($mysqli, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ymiarb1_count($mysqli, $table);
    }

    return $counts;
}

function ymiarb1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ymiarb1_first_user_id($mysqli, $where)
{
    $sql = 'SELECT id FROM users WHERE ' . $where . ' ORDER BY id ASC LIMIT 1';
    $result = $mysqli->query($sql);
    if (!$result || $result->num_rows === 0) {
        return null;
    }

    $row = $result->fetch_assoc();
    return (int) $row['id'];
}

function ymiarb1_first_course_id($mysqli)
{
    $result = $mysqli->query('SELECT id FROM course ORDER BY id ASC LIMIT 1');
    if (!$result || $result->num_rows === 0) {
        return null;
    }

    $row = $result->fetch_assoc();
    return (int) $row['id'];
}

function ymiarb1_ci_session_string($data)
{
    $raw = '';
    foreach ($data as $key => $value) {
        $raw .= $key . '|' . serialize($value);
    }

    return $raw;
}

function ymiarb1_create_session($mysqli, $user_id, $role_id, $admin_login)
{
    $session_id = bin2hex(random_bytes(16));
    $now = time();
    $data = array(
        '__ci_last_regenerate' => $now,
        'cart_items' => array(),
        'language' => 'english',
        'custom_session_limit' => $now + 864000,
        'user_id' => (string) $user_id,
        'role_id' => (string) $role_id,
        'role' => $admin_login ? 'admin' : 'student',
        'name' => $admin_login ? 'Temporary Root Admin QA Session' : 'Temporary Learner QA Session',
        'is_instructor' => $admin_login ? '1' : '0',
    );

    if ($admin_login) {
        $data['admin_login'] = '1';
    } else {
        $data['user_login'] = '1';
    }

    ymiarb1_insert_filtered($mysqli, 'ci_sessions', array(
        'id' => $session_id,
        'ip_address' => '127.0.0.1',
        'timestamp' => $now,
        'data' => ymiarb1_ci_session_string($data),
    ));

    return $session_id;
}

function ymiarb1_create_png_fixture($root, $order_id)
{
    $dir = $root . '/uploads/youngo/instapay_evidence';
    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
        throw new RuntimeException('Evidence directory could not be created.');
    }

    $path = $dir . '/browser_qa_instapay_admin_review_' . (int) $order_id . '_' . substr(hash('sha256', microtime(true)), 0, 8) . '.png';
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAEtAJJXIDTjwAAAABJRU5ErkJggg==');
    if (file_put_contents($path, $png) === false) {
        throw new RuntimeException('Evidence fixture could not be written.');
    }

    return array(
        'absolute_path' => $path,
        'relative_path' => 'uploads/youngo/instapay_evidence/' . basename($path),
        'original_name' => 'browser-qa-instapay-evidence.png',
        'mime' => 'image/png',
        'size' => filesize($path),
    );
}

function ymiarb1_insert_order($mysqli, $user_id, $course_id)
{
    $now = time();
    $snapshot = array(
        'snapshot_source' => 'browser_qa_admin_review',
        'item_type' => 'course',
        'item_id' => (int) $course_id,
        'course_id' => (int) $course_id,
        'item_title_snapshot' => 'Browser QA Instapay Review Course',
        'original_amount' => '150.00',
        'coupon_code' => 'BROWSERQA25',
        'coupon_discount_type' => 'percentage',
        'coupon_discount_value' => '25.00',
        'discount_amount' => '37.50',
        'final_amount' => '112.50',
        'currency' => 'EGP',
        'selected_payment_method' => 'instapay_manual',
    );

    return ymiarb1_insert_filtered($mysqli, 'youngo_checkout_orders', array(
        'user_id' => (int) $user_id,
        'order_reference' => 'YGO-BROWSER-INSTAPAY-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true)), 0, 6),
        'order_type' => 'course',
        'course_id' => (int) $course_id,
        'plan_id' => null,
        'status' => 'draft',
        'subtotal_amount' => '150.00',
        'discount_amount' => '37.50',
        'total_amount' => '112.50',
        'currency' => 'EGP',
        'coupon_id' => null,
        'coupon_code' => 'BROWSERQA25',
        'coupon_discount_type' => 'percentage',
        'coupon_discount_value' => '25.00',
        'selected_payment_method' => 'instapay_manual',
        'item_title_snapshot' => 'Browser QA Instapay Review Course',
        'checkout_snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES),
        'metadata' => json_encode(array('source' => 'browser_qa_admin_review'), JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    ));
}

function ymiarb1_insert_submission($mysqli, $order_id, $user_id, $file)
{
    $now = time();
    $snapshot = array(
        'order' => array(
            'order_id' => (int) $order_id,
            'user_id' => (int) $user_id,
            'item_type' => 'course',
            'item_id' => 1,
            'course_id' => 1,
            'item_title_snapshot' => 'Browser QA Instapay Review Course',
            'original_amount' => '150.00',
            'coupon_code' => 'BROWSERQA25',
            'coupon_discount_type' => 'percentage',
            'coupon_discount_value' => '25.00',
            'discount_amount' => '37.50',
            'final_amount' => '112.50',
            'currency' => 'EGP',
            'selected_payment_method' => 'instapay_manual',
        ),
        'instapay' => array(
            'enabled' => true,
            'target_label' => 'Browser QA Instapay Target',
            'target_address' => 'browser.qa@instapay',
            'target_link' => 'https://instapay.example/browser-qa',
            'instructions_en' => 'Browser QA evidence only.',
            'instructions_ar' => 'Browser QA evidence only.',
        ),
        'status_model' => array('pending_review', 'approved', 'rejected'),
    );

    return ymiarb1_insert_filtered($mysqli, 'youngo_instapay_payment_submissions', array(
        'order_id' => (int) $order_id,
        'user_id' => (int) $user_id,
        'status' => 'pending_review',
        'expected_amount' => '112.50',
        'submitted_amount' => '112.50',
        'currency' => 'EGP',
        'instapay_target_label' => 'Browser QA Instapay Target',
        'instapay_target_address' => 'browser.qa@instapay',
        'instapay_target_link' => 'https://instapay.example/browser-qa',
        'screenshot_path' => $file['relative_path'],
        'screenshot_original_name' => $file['original_name'],
        'screenshot_mime' => $file['mime'],
        'screenshot_size' => $file['size'],
        'transaction_reference' => 'BROWSER-QA-REF',
        'user_note' => 'Browser QA pending review fixture.',
        'admin_note' => null,
        'reviewed_by_user_id' => null,
        'reviewed_at' => null,
        'approved_at' => null,
        'rejected_at' => null,
        'access_issued' => 0,
        'access_issued_at' => null,
        'snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    ));
}

function ymiarb1_http_get($url, $session_id = null)
{
    $headers = array('User-Agent: YounGo-Instapay-Admin-Review-QA/1.0');
    if ($session_id !== null) {
        $headers[] = 'Cookie: ci_session=' . $session_id;
    }

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'header' => implode("\r\n", $headers) . "\r\n",
            'ignore_errors' => true,
            'timeout' => 25,
            'follow_location' => 0,
        ),
    ));

    $body = @file_get_contents($url, false, $context);
    $response_headers = isset($http_response_header) ? $http_response_header : array();
    $status = 0;
    foreach ($response_headers as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
            $status = (int) $matches[1];
            break;
        }
    }

    return array(
        'status' => $status,
        'headers' => $response_headers,
        'body' => $body === false ? '' : $body,
    );
}

function ymiarb1_header_contains($response, $needle)
{
    foreach ($response['headers'] as $header) {
        if (stripos($header, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function ymiarb1_delete_ids($mysqli, $table, $ids)
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (empty($ids)) {
        return;
    }

    $mysqli->query('DELETE FROM ' . ymiarb1_ident($table) . ' WHERE id IN (' . implode(',', $ids) . ')');
}

$mysqli = ymiarb1_connect();
$protected_tables = array(
    'ci_sessions',
    'youngo_instapay_payment_submissions',
    'youngo_checkout_orders',
    'youngo_payment_transactions',
    'youngo_course_access',
    'youngo_user_subscriptions',
    'youngo_manual_grants',
    'youngo_coupon_usages',
    'payment',
    'enrol',
);
$baseline_counts = ymiarb1_counts($mysqli, $protected_tables);
$backup = ymiarb1_create_backup($mysqli, $root);
if (!empty($backup['ok'])) {
    $details['backup_path'] = $backup['path'];
    $details['backup_size'] = (string) $backup['size'];
    $details['backup_sha256'] = $backup['sha256'];
}

ymiarb1_check($checks, 'backup_created_before_browser_qa_fixtures', !empty($backup['ok']), !empty($backup['ok']) ? $backup['path'] . '|' . $backup['size'] . '|' . $backup['sha256'] : (isset($backup['error']) ? $backup['error'] : 'backup_failed'));

$admin_id = ymiarb1_first_user_id($mysqli, 'id = 1 AND status = 1');
$learner_id = ymiarb1_first_user_id($mysqli, 'role_id = 2 AND status = 1');
$course_id = ymiarb1_first_course_id($mysqli);
ymiarb1_check($checks, 'fixture_users_and_course_available', $admin_id === 1 && $learner_id !== null && $course_id !== null, 'root=' . (string) $admin_id . ', learner=' . (string) $learner_id . ', course=' . (string) $course_id);

try {
    if ($admin_id === 1 && $learner_id !== null && $course_id !== null) {
        $details['cleanup'] = 'started';
        $order_id = ymiarb1_insert_order($mysqli, $learner_id, $course_id);
        $fixture_order_ids[] = $order_id;
        $details['order_id'] = (string) $order_id;

        $file = ymiarb1_create_png_fixture($root, $order_id);
        $fixture_files[] = $file['absolute_path'];

        $submission_id = ymiarb1_insert_submission($mysqli, $order_id, $learner_id, $file);
        $fixture_submission_ids[] = $submission_id;
        $details['submission_id'] = (string) $submission_id;

        $admin_session = ymiarb1_create_session($mysqli, $admin_id, 1, true);
        $learner_session = ymiarb1_create_session($mysqli, $learner_id, 2, false);
        $fixture_session_ids[] = $admin_session;
        $fixture_session_ids[] = $learner_session;
        $details['admin_session_id'] = substr($admin_session, 0, 8) . '...';
        $details['learner_session_id'] = substr($learner_session, 0, 8) . '...';

        $base = rtrim($details['base_url'], '/');
        $inbox = ymiarb1_http_get($base . '/admin/youngo/instapay-payments', $admin_session);
        $pending = ymiarb1_http_get($base . '/admin/youngo/instapay-payments?status=pending_review', $admin_session);
        $approved = ymiarb1_http_get($base . '/admin/youngo/instapay-payments?status=approved', $admin_session);
        $rejected = ymiarb1_http_get($base . '/admin/youngo/instapay-payments?status=rejected', $admin_session);
        $all = ymiarb1_http_get($base . '/admin/youngo/instapay-payments?status=all', $admin_session);
        $detail = ymiarb1_http_get($base . '/admin/youngo/instapay-payments/' . $submission_id, $admin_session);
        $preview = ymiarb1_http_get($base . '/admin/youngo/instapay-payments/' . $submission_id . '/evidence', $admin_session);
        $download = ymiarb1_http_get($base . '/admin/youngo/instapay-payments/' . $submission_id . '/evidence/download', $admin_session);
        $learner_inbox = ymiarb1_http_get($base . '/admin/youngo/instapay-payments', $learner_session);
        $learner_preview = ymiarb1_http_get($base . '/admin/youngo/instapay-payments/' . $submission_id . '/evidence', $learner_session);
        $post_like = ymiarb1_http_get($base . '/admin/youngo/instapay-payments/' . $submission_id . '/approve', $admin_session);

        ymiarb1_check($checks, 'admin_inbox_renders_http_200', $inbox['status'] === 200 && stripos($inbox['body'], 'Manual Instapay Payments') !== false && stripos($inbox['body'], 'BROWSERQA25') !== false, 'status=' . $inbox['status']);
        ymiarb1_check($checks, 'pending_review_filter_renders_fixture', $pending['status'] === 200 && stripos($pending['body'], 'BROWSERQA25') !== false && stripos($pending['body'], 'Pending Review') !== false, 'status=' . $pending['status']);
        ymiarb1_check($checks, 'approved_filter_renders_without_pending_fixture', $approved['status'] === 200 && stripos($approved['body'], 'Approved') !== false && stripos($approved['body'], 'BROWSERQA25') === false, 'status=' . $approved['status']);
        ymiarb1_check($checks, 'rejected_filter_renders_without_pending_fixture', $rejected['status'] === 200 && stripos($rejected['body'], 'Rejected') !== false && stripos($rejected['body'], 'BROWSERQA25') === false, 'status=' . $rejected['status']);
        ymiarb1_check($checks, 'all_filter_renders_fixture', $all['status'] === 200 && stripos($all['body'], 'BROWSERQA25') !== false, 'status=' . $all['status']);
        ymiarb1_check($checks, 'admin_detail_renders_snapshot_and_disabled_decisions', $detail['status'] === 200 && stripos($detail['body'], 'Browser QA Instapay Review Course') !== false && stripos($detail['body'], '112.50') !== false && stripos($detail['body'], 'disabled>Approve payment') !== false && stripos($detail['body'], 'disabled>Reject payment') !== false, 'status=' . $detail['status']);
        ymiarb1_check($checks, 'evidence_preview_returns_png_inline', $preview['status'] === 200 && ymiarb1_header_contains($preview, 'Content-Type: image/png') && ymiarb1_header_contains($preview, 'Content-Disposition: inline') && substr($preview['body'], 0, 8) === "\x89PNG\r\n\x1a\n", 'status=' . $preview['status']);
        ymiarb1_check($checks, 'evidence_download_returns_png_attachment', $download['status'] === 200 && ymiarb1_header_contains($download, 'Content-Type: image/png') && ymiarb1_header_contains($download, 'Content-Disposition: attachment'), 'status=' . $download['status']);
        ymiarb1_check($checks, 'learner_denied_admin_inbox', (in_array($learner_inbox['status'], array(301, 302, 303), true) && ymiarb1_header_contains($learner_inbox, '/login')) || ($learner_inbox['status'] === 200 && stripos($learner_inbox['body'], 'Manual Instapay Payments') === false && stripos($learner_inbox['body'], 'BROWSERQA25') === false), 'status=' . $learner_inbox['status']);
        ymiarb1_check($checks, 'learner_denied_evidence_preview', (in_array($learner_preview['status'], array(301, 302, 303), true) && ymiarb1_header_contains($learner_preview, '/login')) || ($learner_preview['status'] === 200 && substr($learner_preview['body'], 0, 8) !== "\x89PNG\r\n\x1a\n" && !ymiarb1_header_contains($learner_preview, 'Content-Type: image/png')), 'status=' . $learner_preview['status']);
        ymiarb1_check($checks, 'no_approve_route_or_action_available', in_array($post_like['status'], array(301, 302, 303, 404), true) || ($post_like['status'] === 200 && (stripos($post_like['body'], '404') !== false || stripos($post_like['body'], 'Approval and rejection actions will be enabled in the next phase') !== false)), 'status=' . $post_like['status']);

        $order_result = $mysqli->query('SELECT status, payment_gateway, paid_at, entitlement_issued FROM youngo_checkout_orders WHERE id = ' . (int) $order_id . ' LIMIT 1');
        $order = $order_result ? $order_result->fetch_assoc() : array();
        $status_result = $mysqli->query('SELECT status, access_issued FROM youngo_instapay_payment_submissions WHERE id = ' . (int) $submission_id . ' LIMIT 1');
        $submission = $status_result ? $status_result->fetch_assoc() : array();
        ymiarb1_check($checks, 'fixture_remained_unpaid_pending_review_no_access', isset($order['status'], $submission['status']) && $order['status'] === 'draft' && (string) $submission['status'] === 'pending_review' && empty($order['payment_gateway']) && empty($order['paid_at']) && empty($order['entitlement_issued']) && empty($submission['access_issued']));
    }
} finally {
    if (!empty($fixture_submission_ids)) {
        ymiarb1_delete_ids($mysqli, 'youngo_instapay_payment_submissions', $fixture_submission_ids);
    }
    if (!empty($fixture_order_ids)) {
        ymiarb1_delete_ids($mysqli, 'youngo_checkout_orders', $fixture_order_ids);
    }
    if (!empty($fixture_session_ids)) {
        $escaped = array();
        foreach (array_values(array_unique($fixture_session_ids)) as $session_id) {
            $escaped[] = ymiarb1_sql_value($mysqli, $session_id);
        }
        if (!empty($escaped)) {
            $mysqli->query('DELETE FROM ci_sessions WHERE id IN (' . implode(',', $escaped) . ')');
        }
    }
    foreach ($fixture_files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

$after_counts = ymiarb1_counts($mysqli, $protected_tables);
$details['cleanup'] = ymiarb1_counts_match($baseline_counts, $after_counts) ? 'completed' : 'failed';
ymiarb1_check($checks, 'temporary_rows_files_sessions_cleaned', ymiarb1_counts_match($baseline_counts, $after_counts) && empty(array_filter($fixture_files, 'is_file')), json_encode(array('before' => $baseline_counts, 'after' => $after_counts), JSON_UNESCAPED_SLASHES));
ymiarb1_check($checks, 'no_access_payment_enrolment_count_drift', $baseline_counts['youngo_course_access'] === $after_counts['youngo_course_access'] && $baseline_counts['youngo_user_subscriptions'] === $after_counts['youngo_user_subscriptions'] && $baseline_counts['youngo_manual_grants'] === $after_counts['youngo_manual_grants'] && $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol'] && $baseline_counts['youngo_payment_transactions'] === $after_counts['youngo_payment_transactions']);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo 'phase: PAYMENT.MANUAL.INSTAPAY.ADMIN.REVIEW.UI.BROWSER.QA.1' . PHP_EOL;
echo 'mode: authenticated_local_http_session_qa_temp_fixtures_cleaned' . PHP_EOL;
echo 'base_url: ' . $details['base_url'] . PHP_EOL;
echo 'backup_path: ' . $details['backup_path'] . PHP_EOL;
echo 'backup_size: ' . $details['backup_size'] . PHP_EOL;
echo 'backup_sha256: ' . $details['backup_sha256'] . PHP_EOL;
echo 'order_id: ' . $details['order_id'] . PHP_EOL;
echo 'submission_id: ' . $details['submission_id'] . PHP_EOL;
echo 'admin_session_id: ' . $details['admin_session_id'] . PHP_EOL;
echo 'learner_session_id: ' . $details['learner_session_id'] . PHP_EOL;
echo 'cleanup: ' . $details['cleanup'] . PHP_EOL;
echo PHP_EOL;

foreach ($checks as $name => $check) {
    echo $check['status'] . ' - ' . $name;
    if ($check['detail'] !== '') {
        echo ' :: ' . $check['detail'];
    }
    echo PHP_EOL;
}

echo PHP_EOL;
echo empty($failed) ? 'RESULT: PASS' . PHP_EOL : 'RESULT: FAIL' . PHP_EOL;

exit(empty($failed) ? 0 : 1);
