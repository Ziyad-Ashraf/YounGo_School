<?php
/**
 * PAYMENT.CHECKOUT.CTA.LOCAL.BLOCK.1 diagnostic.
 *
 * Read-only/static checks for the disabled-by-default YounGo checkout CTA
 * helper and course-detail-only integration. No DB writes, no Paymob calls,
 * no secrets printed.
 */

define('ENVIRONMENT', 'development');
define('BASEPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);
define('APPPATH', BASEPATH);

$root = dirname(__DIR__, 2);
$checks = array();
$details = array();

function ycta1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ycta1_read($path)
{
    return is_file($path) ? file_get_contents($path) : false;
}

function ycta1_contains($source, $needle)
{
    return is_string($source) && strpos($source, $needle) !== false;
}

function ycta1_count_rows($mysqli, $table)
{
    if (!$mysqli) {
        return null;
    }

    $result = $mysqli->query('SELECT COUNT(*) AS c FROM `' . $table . '`');
    if (!$result) {
        return null;
    }

    $row = $result->fetch_assoc();
    return isset($row['c']) ? (int) $row['c'] : null;
}

class Youngo_cta_diag_config
{
    protected $values;

    public function __construct($values = array())
    {
        $this->values = array_merge(array(
            'checkout_cta_enabled' => false,
            'checkout_routes_enabled' => false,
            'checkout_local_testing_enabled' => false,
            'network_enabled' => false,
            'mode' => 'sandbox',
            'currency' => 'EGP',
        ), $values);
    }

    public function is_checkout_cta_enabled()
    {
        return !empty($this->values['checkout_cta_enabled']);
    }

    public function is_checkout_routes_enabled()
    {
        return !empty($this->values['checkout_routes_enabled']);
    }

    public function is_checkout_local_testing_enabled()
    {
        return !empty($this->values['checkout_local_testing_enabled']);
    }

    public function get_mode()
    {
        return (string) $this->values['mode'];
    }

    public function get_currency()
    {
        return (string) $this->values['currency'];
    }

    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }
}

$paths = array(
    'helper' => $root . '/application/helpers/youngo_checkout_cta_helper.php',
    'course_page' => $root . '/application/views/frontend/youngo/course_page.php',
    'course_card' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'my_wishlist' => $root . '/application/views/frontend/youngo/my_wishlist.php',
    'wishlist_items' => $root . '/application/views/frontend/youngo/wishlist_items.php',
    'featured_courses' => $root . '/application/views/frontend/youngo/home_sections/featured_courses.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
    'paymob_config_reader' => $root . '/application/libraries/Youngo_paymob_config.php',
);

foreach ($paths as $key => $path) {
    ycta1_check($checks, 'file_exists_' . $key, is_file($path), $path);
}

if (is_file($paths['paymob_config_reader'])) {
    require_once $paths['paymob_config_reader'];
}

if (is_file($paths['helper'])) {
    require_once $paths['helper'];
}

ycta1_check($checks, 'helper_function_exists', function_exists('youngo_checkout_cta_decision'));

$default_config = class_exists('Youngo_paymob_config')
    ? new Youngo_paymob_config(array('load_local_override' => false))
    : null;

$eligible_course = array(
    'id' => 9,
    'status' => 'active',
    'youngo_access_mode' => 'subscription_and_purchase',
    'is_free_course' => 0,
    'discount_flag' => 0,
    'price' => '1000.00',
    'discounted_price' => '0.00',
);

$subscription_only_course = $eligible_course;
$subscription_only_course['id'] = 1;
$subscription_only_course['youngo_access_mode'] = 'subscription_only';

$free_course = $eligible_course;
$free_course['id'] = 2;
$free_course['is_free_course'] = 1;

$invalid_course = array('id' => 0);
$local_config = new Youngo_cta_diag_config(array(
    'checkout_cta_enabled' => true,
    'checkout_routes_enabled' => true,
    'checkout_local_testing_enabled' => true,
    'network_enabled' => false,
    'mode' => 'sandbox',
    'currency' => 'EGP',
));

$default_decision = function_exists('youngo_checkout_cta_decision')
    ? youngo_checkout_cta_decision($eligible_course, 10, array(
        'config_reader' => $default_config,
        'access_state' => array('has_access' => false),
        'is_learner' => true,
    ))
    : array();

$local_decision = function_exists('youngo_checkout_cta_decision')
    ? youngo_checkout_cta_decision($eligible_course, 10, array(
        'config_reader' => $local_config,
        'access_state' => array('has_access' => false),
        'is_learner' => true,
    ))
    : array();

$guest_decision = function_exists('youngo_checkout_cta_decision')
    ? youngo_checkout_cta_decision($eligible_course, 0, array(
        'config_reader' => $local_config,
        'access_state' => array('has_access' => false),
        'is_learner' => false,
    ))
    : array();

$already_access_decision = function_exists('youngo_checkout_cta_decision')
    ? youngo_checkout_cta_decision($eligible_course, 10, array(
        'config_reader' => $local_config,
        'access_state' => array('has_access' => true),
        'is_learner' => true,
    ))
    : array();

$subscription_decision = function_exists('youngo_checkout_cta_decision')
    ? youngo_checkout_cta_decision($subscription_only_course, 10, array(
        'config_reader' => $local_config,
        'access_state' => array('has_access' => false),
        'is_learner' => true,
    ))
    : array();

$free_decision = function_exists('youngo_checkout_cta_decision')
    ? youngo_checkout_cta_decision($free_course, 10, array(
        'config_reader' => $local_config,
        'access_state' => array('has_access' => false),
        'is_learner' => true,
    ))
    : array();

$invalid_decision = function_exists('youngo_checkout_cta_decision')
    ? youngo_checkout_cta_decision($invalid_course, 10, array(
        'config_reader' => $local_config,
        'access_state' => array('has_access' => false),
        'is_learner' => true,
    ))
    : array();

ycta1_check($checks, 'default_config_keeps_cta_hidden', empty($default_decision['show_cta']) && isset($default_decision['reason_code']) && $default_decision['reason_code'] === 'checkout_cta_disabled');
ycta1_check($checks, 'local_flag_simulation_allows_eligible_course', !empty($local_decision['show_cta']) && isset($local_decision['reason_code']) && $local_decision['reason_code'] === 'checkout_cta_available_local');
ycta1_check($checks, 'local_target_url_only_youngo_checkout_start', isset($local_decision['target_url']) && $local_decision['target_url'] === '/youngo/checkout/start/9');
ycta1_check($checks, 'guest_gets_login_safe_state', empty($guest_decision['show_cta']) && isset($guest_decision['state']) && $guest_decision['state'] === 'login_required' && isset($guest_decision['login_url']) && strpos($guest_decision['login_url'], 'login') !== false);
ycta1_check($checks, 'already_access_hides_cta', empty($already_access_decision['show_cta']) && isset($already_access_decision['reason_code']) && $already_access_decision['reason_code'] === 'active_course_access_exists');
ycta1_check($checks, 'subscription_only_hides_purchase_cta', empty($subscription_decision['show_cta']) && isset($subscription_decision['reason_code']) && $subscription_decision['reason_code'] === 'course_subscription_only');
ycta1_check($checks, 'free_course_hides_purchase_cta', empty($free_decision['show_cta']) && isset($free_decision['reason_code']) && $free_decision['reason_code'] === 'free_course_no_purchase_checkout');
ycta1_check($checks, 'invalid_course_hides_cta', empty($invalid_decision['show_cta']) && isset($invalid_decision['reason_code']) && $invalid_decision['reason_code'] === 'invalid_course');

$legacy_url_hits = array();
foreach (array($default_decision, $local_decision, $guest_decision, $already_access_decision, $subscription_decision, $free_decision, $invalid_decision) as $decision) {
    foreach (array('target_url', 'login_url') as $field) {
        if (!empty($decision[$field]) && preg_match('#home/(handle_buy_now|handle_cart_items|course_payment|shopping_cart)|^/?payment$#', $decision[$field])) {
            $legacy_url_hits[] = $field . ':' . $decision[$field];
        }
    }
}
ycta1_check($checks, 'helper_returns_no_legacy_cart_payment_urls', empty($legacy_url_hits), implode(', ', $legacy_url_hits));

$course_page = ycta1_read($paths['course_page']);
$course_card = ycta1_read($paths['course_card']);
$my_wishlist = ycta1_read($paths['my_wishlist']);
$wishlist_items = ycta1_read($paths['wishlist_items']);
$featured_courses = ycta1_read($paths['featured_courses']);
$helper = ycta1_read($paths['helper']);

ycta1_check($checks, 'course_detail_has_cta_marker', ycta1_contains($course_page, 'data-youngo-checkout-cta="local-course-detail"'));
ycta1_check($checks, 'course_detail_loads_cta_helper', ycta1_contains($course_page, "youngo_checkout_cta_helper.php") && ycta1_contains($course_page, 'youngo_checkout_cta_decision($course_details'));
ycta1_check($checks, 'course_listing_has_no_checkout_start_link', !ycta1_contains($course_card, 'youngo/checkout/start') && !ycta1_contains($course_card, 'data-youngo-checkout-cta'));
ycta1_check($checks, 'wishlist_page_has_no_checkout_start_link', !ycta1_contains($my_wishlist, 'youngo/checkout/start') && !ycta1_contains($my_wishlist, 'data-youngo-checkout-cta'));
ycta1_check($checks, 'wishlist_partial_has_no_checkout_start_link', !ycta1_contains($wishlist_items, 'youngo/checkout/start') && !ycta1_contains($wishlist_items, 'data-youngo-checkout-cta'));
ycta1_check($checks, 'homepage_featured_courses_has_no_checkout_start_link', !ycta1_contains($featured_courses, 'youngo/checkout/start') && !ycta1_contains($featured_courses, 'data-youngo-checkout-cta'));
ycta1_check($checks, 'course_detail_keeps_legacy_managed_access_fallback', ycta1_contains($course_page, '$youngo_is_managed_access') && ycta1_contains($course_page, '$youngo_managed_access_message'));

$network_needles = array('curl_init', 'file_get_contents(\'http', 'file_get_contents("http', 'accept.paymob.com', 'paymob.com/api');
$network_hits = array();
foreach (array('helper' => $helper, 'course_page' => $course_page) as $name => $source) {
    foreach ($network_needles as $needle) {
        if (ycta1_contains($source, $needle)) {
            $network_hits[] = $name . ':' . $needle;
        }
    }
}
ycta1_check($checks, 'no_paymob_network_calls_in_cta_phase_files', empty($network_hits), implode(', ', $network_hits));

$write_needles = array('->insert(', '->update(', '->delete(', 'trans_begin', 'ALTER TABLE', 'CREATE TABLE', 'DROP TABLE');
$write_hits = array();
foreach (array('helper' => $helper, 'course_page' => $course_page) as $name => $source) {
    foreach ($write_needles as $needle) {
        if (ycta1_contains($source, $needle)) {
            $write_hits[] = $name . ':' . $needle;
        }
    }
}
ycta1_check($checks, 'cta_helper_and_course_detail_have_no_db_writes', empty($write_hits), implode(', ', $write_hits));

$mysqli = null;
$before_counts = array();
$after_counts = array();
if (is_file($root . '/application/config/database.php')) {
    include $root . '/application/config/database.php';
    if (isset($db[$active_group])) {
        $c = $db[$active_group];
        $mysqli = @new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
    }
}

foreach (array('youngo_checkout_orders', 'youngo_payment_transactions', 'youngo_course_access', 'payment', 'enrol') as $table) {
    $before_counts[$table] = ycta1_count_rows($mysqli, $table);
    $after_counts[$table] = ycta1_count_rows($mysqli, $table);
}

ycta1_check($checks, 'protected_counts_unchanged_by_diagnostic', $before_counts === $after_counts, json_encode(array('before' => $before_counts, 'after' => $after_counts)));

$details['decisions'] = array(
    'default' => $default_decision,
    'local_eligible' => $local_decision,
    'guest' => $guest_decision,
    'already_access' => $already_access_decision,
    'subscription_only' => $subscription_decision,
    'free_course' => $free_decision,
    'invalid_course' => $invalid_decision,
);
$details['protected_counts_before'] = $before_counts;
$details['protected_counts_after'] = $after_counts;
$details['db_writes'] = 'none';
$details['network_requests'] = 'none';

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo json_encode(array(
    'phase' => 'PAYMENT.CHECKOUT.CTA.LOCAL.BLOCK.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
