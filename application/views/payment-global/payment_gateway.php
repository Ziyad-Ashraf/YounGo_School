<style>
	body {
		padding-top: 50px;
		padding-bottom: 50px;
	}

	.payment-header-text {
		font-size: 23px;

	}

	.close-btn-light {
		padding-left: 10px;
		padding-right: 10px;
		height: 35px;
		line-height: 35px;
		text-align: center;
		font-size: 25px;
		background-color: #F1EAE9;
		color: #a45e72;
		border-radius: 5px;
	}

	.close-btn-light:hover {
		padding-left: 10px;
		padding-right: 10px;
		height: 35px;
		line-height: 35px;
		text-align: center;
		font-size: 25px;
		background-color: #a45e72;
		color: #FFFFFF;
		border-radius: 5px;
	}

	.payment-header {
		font-size: 14px;
	}

	.item {
		width: 100%;
		height: 50px;
		display: block;
	}

	.count-item {
		padding-left: 13px;
		padding-right: 13px;
		padding-top: 5px;
		padding-bottom: 5px;

		margin-bottom: 100%;
		margin-right: 18px;
		margin-top: 8px;

		color: #00B491;
		background-color: #DEF6F3;
		border-radius: 5px;
		float: left;
	}

	.item-title {
		font-weight: bold;
		font-size: 13.5px;
		display: block;
		margin-top: 6px;
	}
	.gateway{
		display: none;
	}

	.item-price {
		float: right;
		color: #00B491;
	}

	.by-owner {
		font-size: 11px;
		color: #76767E;
		display: block;
		margin-top: -3px;
	}

	.total {
		border-radius: 8px 0px 0px 8px;
		background-color: #DBF3F0;
		padding: 10px;
		padding-left: 30px;
		padding-right: 30px;
		font-size: 18px;
	}

	.total-price {
		border-radius: 0px 8px 8px 0px;
		background-color: #CCD4DD;
		padding: 10px;
		padding-left: 25px;
		padding-right: 25px;
		font-size: 18px;
	}

	.indicated-price {
		padding-bottom: 20px;
		margin-bottom: 0px;
	}

	.payment-button {
		background-color: #1DBDA0;
		border-radius: 8px;
		padding: 10px;
		padding-left: 30px;
		padding-right: 30px;
		color: #fff;
		border: none;
		font-size: 18px;
	}

	.payment-gateway {
		border: 2px solid #D3DCDD;
		border-radius: 5px;
		padding-top: 15px;
		padding-bottom: 15px;
		margin-bottom: 15px;
		cursor: pointer;
		min-height: 68px;
	}

	.payment-gateway.selected, .payment-gateway:hover {
		border: 2px solid #00D04F;
		border-radius: 5px;
		padding-top: 15px;
		padding-bottom: 15px;
		margin-bottom: 15px;
		cursor: pointer;
	}

	.payment-gateway-icon {
		float: right;
	    max-width: 70%;
	    margin-right: 13px;
	    max-height: 40px;
	}

	.tick-icon {
		margin: 0px;
		padding: 0px;
		width: 15%;
		float: left;
		display: none;
	}
	.offline_payment_instruction {
	    padding: 20px;
	    min-height: 100px;
	    width: 100%;
	    background-color: #E6E9FC;
	    margin-top: 40px;
	    border-radius: 10px;
	}
</style>

<?php
$payment_details = $this->session->userdata('payment_details');
if (!is_array($payment_details)) {
	$payment_details = array();
}

$system_currency = strtoupper(trim((string) get_settings('system_currency')));
if ($system_currency === '') {
	$system_currency = 'EGP';
}

$payment_gateways = array();
$active_payment_gateways = $this->db->where('status', 1)->get('payment_gateways')->result_array();
foreach ($active_payment_gateways as $payment_gateway) {
	$identifier = isset($payment_gateway['identifier']) ? trim((string) $payment_gateway['identifier']) : '';
	$gateway_currency = strtoupper(trim(isset($payment_gateway['currency']) ? (string) $payment_gateway['currency'] : ''));

	if ($identifier === '' || !preg_match('/^[a-z0-9_]+$/i', $identifier)) {
		continue;
	}

	if (!empty($payment_gateway['is_addon']) && !addon_status($identifier)) {
		continue;
	}

	// PAYMENT.FIX.1: hide active inherited gateways until they are EGP-aligned and deliberately approved for sandbox checkout.
	$youngo_gateway_is_currency_safe = ($gateway_currency === $system_currency);
	if (!$youngo_gateway_is_currency_safe) {
		continue;
	}

	if (!file_exists(__DIR__ . '/' . $identifier . '/payment_form.php')) {
		continue;
	}

	if (!empty($payment_details['is_instructor_payout_user_id'])) {
		$instructor_details = $this->user_model->get_all_user($payment_details['is_instructor_payout_user_id'])->row_array();
		$keys = !empty($instructor_details['payment_keys']) ? json_decode($instructor_details['payment_keys'], true) : array();
		if (!is_array($keys) || !array_key_exists($identifier, $keys) || !is_array($keys[$identifier])) {
			continue;
		}

		$empty_key_of_instructor = 0;
		foreach ($keys[$identifier] as $key_val) {
			if (empty($key_val)) {
				$empty_key_of_instructor = 1;
				break;
			}
		}

		if ($empty_key_of_instructor > 0) {
			continue;
		}
	}

	$payment_gateways[] = $payment_gateway;
}

$back_url = !empty($payment_details['back_url']) ? $payment_details['back_url'] : site_url('home/shopping_cart');
$payment_title = !empty($payment_details['payment_title']) ? $payment_details['payment_title'] : get_phrase('payment');
$payment_items = !empty($payment_details['items']) && is_array($payment_details['items']) ? $payment_details['items'] : array();
$total_payable_amount = isset($payment_details['total_payable_amount']) ? $payment_details['total_payable_amount'] : 0;
?>

<div class="container">
	<div class="row justify-content-center mb-5">
		<div class="col-md-8">
			<div class="row">
				<div class="col-md-12">
					<span class="payment-header-text float-start"><b><?php echo get_phrase('make_payment'); ?></b></span>
					<a href="<?php echo $back_url; ?>" class="close-btn-light float-end"><i class="fa fa-times"></i></a>
				</div>
			</div>
		</div>
	</div>

	<div class="row justify-content-center">
		<div class="col-md-8">
			<div class="row">
				<div class="col-md-3">
					<p class="pb-2 payment-header"><?php echo get_phrase('select_payment_gateway'); ?></p>
					<?php foreach($payment_gateways as $payment_gateway): ?>
						<div class="row payment-gateway <?php echo $payment_gateway['identifier'].'-selector'; ?>" onclick="selectedPaymentGateway('<?php echo $payment_gateway['identifier']; ?>')">
							<div class="col-12">
								<img class="tick-icon <?php echo $payment_gateway['identifier']; ?>-icon" src="<?php echo base_url('assets/payment/tick.png'); ?>">
								<img class="payment-gateway-icon" src="<?php echo base_url('assets/payment/'.$payment_gateway['identifier'].'.png'); ?>">
							</div>
						</div>
					<?php endforeach; ?>
					<?php if (empty($payment_gateways)): ?>
						<div class="alert alert-warning p-3">
							<?php echo get_phrase('No safe payment gateway is available for the current EGP test baseline. Checkout remains disabled until gateway hygiene and sandbox setup are approved.'); ?>
						</div>
					<?php endif; ?>


				</div>

				<div class="col-md-1"></div>

				<div class="col-md-8">
					<div class="w-100 d-grid">
						<p class="pb-2 payment-header"><?php echo $payment_title; ?></p>
						<?php foreach ($payment_items as $key => $item) : ?>
							<?php $user_details = $this->user_model->get_all_user($item['creator_id'])->row_array(); ?>
							<p class="item float-start mb-0 pb-0">
								<span class="count-item"><?php echo ++$key; ?></span>
								<span class="item-title"><?php echo $item['title']; ?>
									<span class="item-price">
										<?php if($item['discount_flag'] == 1): ?>
											<del style="font-size: 10px; color: #646464;"><?php echo currency($item['price']); ?></del>
											<?php echo currency($item['discounted_price']); ?>
										<?php else: ?>
											<?php echo currency($item['actual_price']); ?>
										<?php endif; ?>
									</span>
								</span>
								<span class="by-owner">
									<?php echo get_phrase('by'); ?>
									<?php echo $user_details['first_name'] . ' ' . $user_details['last_name']; ?>
								</span>
							</p>

							<?php foreach ($item['sub_items'] as $sub_item) : ?>
								<p class="text-muted text-13px ms-5 ps-1 py-0 my-1">- <?php echo $sub_item['title']; ?></p>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</div>
					<div class="w-100 float-start mt-4 indicated-price">
						<div class="float-end total-price"><?php echo currency($total_payable_amount); ?></div>
						<div class="float-end total"><?php echo get_phrase('total'); ?></div>
					</div>
					<div class="w-100 float-start">
						<hr class="border mb-4">
						<?php foreach($payment_gateways as $payment_gateway):
							include $payment_gateway['identifier']."/payment_form.php";
						endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	function selectedPaymentGateway(gateway) {
		$(".payment-gateway").removeClass('selected');
		$('.tick-icon').hide();
		$('.gateway').hide();

		$("."+gateway+"-selector").addClass('selected');
		$('.'+gateway+'-icon').show();
		$('.'+gateway+'-gateway').show();
	}
</script>
