<?php
$test_center_summary = isset($test_center_summary) && is_array($test_center_summary) ? $test_center_summary : array();
$route_readiness = isset($route_readiness) && is_array($route_readiness) ? $route_readiness : array();
$test_action_reason = isset($test_action_reason) ? (string) $test_action_reason : 'Available after deployment to HTTPS domain and explicit sandbox execution enablement.';
$checks = isset($test_center_summary['checks']) && is_array($test_center_summary['checks']) ? $test_center_summary['checks'] : array();

if (!function_exists('youngo_payment_test_center_status_badge')) {
    function youngo_payment_test_center_status_badge($status)
    {
        $status = (string) $status;
        $classes = array(
            'ready' => 'badge-success-lighten',
            'configured' => 'badge-success-lighten',
            'ready_fail_closed' => 'badge-success-lighten',
            'ready_disabled_no_write' => 'badge-success-lighten',
            'disabled' => 'badge-secondary-lighten',
            'missing' => 'badge-warning-lighten',
            'enabled_unapproved' => 'badge-danger-lighten',
            'missing_or_not_fail_closed' => 'badge-danger-lighten',
        );

        $class = isset($classes[$status]) ? $classes[$status] : 'badge-secondary-lighten';
        return '<span class="badge ' . $class . '">' . html_escape($status) . '</span>';
    }
}

if (!function_exists('youngo_payment_test_center_text')) {
    function youngo_payment_test_center_text($value, $default = '-')
    {
        $value = $value === null || $value === '' ? $default : $value;
        return html_escape((string) $value);
    }
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title">
                    <i class="mdi mdi-test-tube title_icon"></i> <?php echo html_escape($page_title); ?>
                    <a href="<?php echo site_url('admin/youngo/payment-settings'); ?>" class="btn btn-outline-primary btn-rounded alignToTitle">Back to Payment Settings</a>
                </h4>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info" role="alert" data-youngo-paymob-test-center="true">
    This Test Center is readiness-only. It does not call Paymob, create intentions, process callbacks, mark orders paid, issue access, expose checkout CTAs, or use legacy Academy payment gateways.
</div>

<div class="row">
    <div class="col-xl-7">
        <div class="card" data-youngo-paymob-test-section="readiness">
            <div class="card-body">
                <h4 class="header-title"><p><?php echo get_phrase('readiness_checks'); ?></p></h4>
                <p class="text-muted"><?php echo get_phrase('statuses_below_show_presence_only._secret_values,_encrypted_blobs,_and_local_security_configuration_contents_are_never_rendered.'); ?></p>
                <div class="table-responsive-sm">
                    <table class="table table-sm table-centered mb-0">
                        <thead>
                            <tr>
                                <th><?php echo get_phrase('check'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                                <th><?php echo get_phrase('note'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($checks as $check): ?>
                                <tr>
                                    <td><?php echo youngo_payment_test_center_text(isset($check['label']) ? $check['label'] : ''); ?></td>
                                    <td><?php echo youngo_payment_test_center_status_badge(isset($check['status']) ? $check['status'] : 'missing'); ?></td>
                                    <td class="text-muted"><?php echo youngo_payment_test_center_text(isset($check['note']) ? $check['note'] : ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card" data-youngo-paymob-test-section="routes">
            <div class="card-body">
                <h4 class="header-title"><p><?php echo get_phrase('return_and_webhook_routes'); ?></p></h4>
                <div class="table-responsive-sm">
                    <table class="table table-sm table-centered mb-0">
                        <thead>
                            <tr>
                                <th><?php echo get_phrase('route'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                                <th><?php echo get_phrase('behavior'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($route_readiness as $route): ?>
                                <tr>
                                    <td><code><?php echo youngo_payment_test_center_text(isset($route['route']) ? $route['route'] : ''); ?></code></td>
                                    <td><?php echo youngo_payment_test_center_status_badge(isset($route['status']) ? $route['status'] : 'missing'); ?></td>
                                    <td class="text-muted"><?php echo youngo_payment_test_center_text(isset($route['note']) ? $route['note'] : ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card" data-youngo-paymob-test-section="summary">
            <div class="card-body">
                <h4 class="header-title"><p><?php echo get_phrase('sandbox_summary'); ?></p></h4>
                <table class="table table-sm table-centered mb-0">
                    <tbody>
                        <tr><th><?php echo get_phrase('provider'); ?></th><td>Paymob</td></tr>
                        <tr><th><?php echo get_phrase('mode'); ?></th><td><?php echo youngo_payment_test_center_text(isset($test_center_summary['mode']) ? $test_center_summary['mode'] : 'sandbox'); ?></td></tr>
                        <tr><th><?php echo get_phrase('currency'); ?></th><td><?php echo youngo_payment_test_center_text(isset($test_center_summary['currency']) ? $test_center_summary['currency'] : 'EGP'); ?></td></tr>
                        <tr><th><?php echo get_phrase('dashboard_db_config'); ?></th><td><?php echo youngo_payment_test_center_status_badge(isset($test_center_summary['configured_db_row']) && $test_center_summary['configured_db_row'] === 'configured_redacted' ? 'configured' : 'missing'); ?></td></tr>
                        <tr><th><?php echo get_phrase('adapter_readiness_code'); ?></th><td><code><?php echo youngo_payment_test_center_text(isset($test_center_summary['adapter_readiness_code']) ? $test_center_summary['adapter_readiness_code'] : 'paymob_sandbox_gates_not_ready'); ?></code></td></tr>
                        <tr><th><?php echo get_phrase('sandbox_execution'); ?></th><td><?php echo youngo_payment_test_center_status_badge('disabled'); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" data-youngo-paymob-test-section="disabled-actions">
            <div class="card-body">
                <h4 class="header-title"><p><?php echo get_phrase('planned_test_actions'); ?></p></h4>
                <p class="text-muted"><?php echo html_escape($test_action_reason); ?></p>
                <?php foreach (array('Check config readiness', 'Test card sandbox intention', 'Test wallet sandbox intention', 'Test webhook/HMAC callback', 'Test return URL') as $action_label): ?>
                    <button type="button" class="btn btn-outline-secondary btn-block mb-2" disabled aria-disabled="true" data-youngo-paymob-test-action-disabled="true" title="<?php echo html_escape($test_action_reason); ?>">
                        <?php echo html_escape($action_label); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" data-youngo-paymob-test-section="production-upload-checklist">
            <div class="card-body">
                <h4 class="header-title"><p><?php echo get_phrase('production_upload_checklist'); ?></p></h4>
                <ul class="mb-0">
                    <li>Upload latest code.</li>
                    <li>Apply DB migrations.</li>
                    <li>Create server encryption key file.</li>
                    <li>Enter Paymob credentials in dashboard.</li>
                    <li>Enter card/wallet integration IDs.</li>
                    <li>Update return_url to production domain.</li>
                    <li>Update notification_url to production domain.</li>
                    <li>Keep sandbox mode first.</li>
                    <li>Run Test Center sandbox tests.</li>
                    <li>Enable public checkout only after successful sandbox QA.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
