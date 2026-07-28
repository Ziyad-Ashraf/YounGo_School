<?php
/**
 * DEMO.FIX.2 minimal Blog i18n schema helper.
 *
 * Apply-safe/idempotent:
 * - Creates youngo_blog_translations only when it is missing.
 * - Does not drop, truncate, overwrite, or alter existing data.
 * - Does not touch blogs, language, users, payments, checkout, coupons, grants, or settings.
 */

define('BASEPATH', true);
defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');

$root = realpath(__DIR__ . '/../..');
require $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$cfg = isset($db['default']) ? $db['default'] : array();
$mysqli = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);

if ($mysqli->connect_errno) {
    fwrite(STDERR, 'DB connection failed: ' . $mysqli->connect_error . PHP_EOL);
    exit(1);
}

$mysqli->set_charset('utf8mb4');

$tableExists = $mysqli->query("SHOW TABLES LIKE 'youngo_blog_translations'");
if ($tableExists && $tableExists->num_rows > 0) {
    $count = $mysqli->query('SELECT COUNT(*) FROM youngo_blog_translations');
    $rowCount = $count ? (int) $count->fetch_row()[0] : 0;
    echo "youngo_blog_translations already exists; no schema change needed. rows={$rowCount}" . PHP_EOL;
    echo "Allowed language_code values are english and arabic; application save paths normalize to those values." . PHP_EOL;
    exit(0);
}

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `youngo_blog_translations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_id` INT UNSIGNED NOT NULL,
  `language_code` VARCHAR(16) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `excerpt` TEXT NULL,
  `description` MEDIUMTEXT NULL,
  `slug` VARCHAR(255) NULL,
  `created_at` INT UNSIGNED NULL,
  `updated_at` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ybt_blog_language` (`blog_id`, `language_code`),
  KEY `idx_ybt_language` (`language_code`),
  KEY `idx_ybt_slug` (`slug`),
  CONSTRAINT `chk_ybt_language_code` CHECK (`language_code` IN ('english', 'arabic'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

if (!$mysqli->query($sql)) {
    fwrite(STDERR, 'Failed to create youngo_blog_translations: ' . $mysqli->error . PHP_EOL);
    exit(1);
}

echo "Created youngo_blog_translations." . PHP_EOL;
echo "Allowed language_code values: english, arabic." . PHP_EOL;
echo "Core blogs table and language table were not touched." . PHP_EOL;
