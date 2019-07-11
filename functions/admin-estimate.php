<?php
/**
 *
 * @package wpcable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function codeable_estimate_callback() {
	$rate           = (float) get_option( 'wpcable_rate', 80 );
	$fee_type       = get_option( 'wpcable_fee_type', 'client' );
	$fee_contractor = 10;
	$fee_client     = 17.5;

	if ( isset( $_GET['fee_client'] ) && is_numeric( $_GET['fee_client'] ) ) {
		$fee_client = (float) $_GET['fee_client'];
	}
	if ( isset( $_GET['fee_contractor'] ) && is_numeric( $_GET['fee_contractor'] ) ) {
		if ( 'full' === $fee_type ) {
			$fee_contractor = (float) $_GET['fee_contractor'];
		}
	}
	if ( isset( $_GET['rate'] ) && is_numeric( $_GET['rate'] ) ) {
		$rate = (float) $_GET['rate'];
	}
	?>
	<div class="wrap">
		<h1>PERT Estimator</h1>
		<form id="estimator" class="metabox-holder">
			<div class="estimate-row">
				<div id="estimates" class="postbox">
					<h2 class="hndle">
						<span>Time required to complete task</span>
					</h2>
					<div class="inside">
						<div class="field">
							<span class="label">Optimistic value:  </span><input id="optimistic_estimate" type="number" class="calc-input" step="0.25" min="1" value="1" /> hours
						</div>
						<div class="field">
							<span class="label">Most likely value: </span><input id="likely_estimate" type="number" class="calc-input" step="0.25" min="1" value="1" /> hours
						</div>
						<div class="field">
							<span class="label">Pessimistic value: </span><input id="pessimistic_estimate" type="number" class="calc-input" step="0.25" min="1" value="1" /> hours
						</div>
					</div>
				</div>

				<div id="fees" class="postbox">
					<h2 class="hndle">
						<span>Rate and Fees</span>
					</h2>
					<div class="inside">
						<div class="field">
							<span class="label">Your hourly rate: </span><input id="hourly_rate" type="number" class="calc-input" value="<?php echo esc_attr( $rate ); ?>" min="35" max="1000" /> $
						</div>
						<div>
							<p class="description">
								<strong>Fees</strong>:
								<?php
								if ( 'full' === $fee_type ) {
									_e( 'My rate is what I want to get paid, without any fees', 'wpcable' );
								} elseif ( 'client' === $fee_type ) {
									_e( 'My rate includes my fee (10%) but not the client fee', 'wpcable' );
								} elseif ( 'none' === $fee_type ) {
									_e( 'My rate includes all fees', 'wpcable' );
								}
								?>.<br />
								Following values are used to calculate the total estimate and your earnings.
							</p>
						</div>
						<div class="field">
							<span class="label">Contractor fee: </span><input id="contractor_fee" type="number" class="calc-input" step="0.01" value="<?php echo esc_attr( $fee_contractor ); ?>" max="100" min="0" /> % (your fee)
						</div>
						<div class="field">
							<span class="label">Client fee: </span><input id="client_fee" type="number" class="calc-input" step="0.01" value="<?php echo esc_attr( $fee_client ); ?>" max="100" min="0" /> % (depends on the client)
						</div>
					</div>
				</div>
			</div>

			<div class="estimate-row">
				<div id="totals" class="postbox">
					<h2 class="hndle">
						<span>Totals</span>
					</h2>
					<div class="inside">
						<p class="description">
							Take these metrics as consideration if you put more weight on the realistic value. (Proper documentation, clear scope etc.)</p>
						<div class="field">
							<span class="label">PERT Standard Estimate: </span><input id="estimate_hours_standard" type="number" value="" readonly="readonly"/> hours
						</div>
						<div class="field">
							<span class="label">Paid by the client: <br/>(including fees)</span><input id="payment" type="number" value="" readonly="readonly"/> $
						</div>
						<div class="field">
							<span class="label"><strong>Estimate</strong>: <br/>(what you enter in Codeable)</span><input id="estimate" type="number" value="" readonly="readonly"/> $
						</div>
						<div class="field">
							<span class="label">Your earnings</span><input id="earnings" type="number" value="" readonly="readonly"/> $
						</div>
					</div>
				</div>

				<div id="totals_pessimistic" class="postbox">
					<h2 class="hndle">
						<span>Totals, with extra buffer</span>
					</h2>
					<div class="inside">
						<p class="description">
							Take these metrics as consideration if you put more weight on the pessimistic value. (Not proper documentation, not so clear scope etc.)</p>
						<div class="field">
							<span class="label">PERT Cautious Estimate: </span><input id="estimate_hours_pessimistic" type="number" value="" readonly="readonly"/> hours
						</div>
						<div class="field">
							<span class="label">Paid by the client: <br/>(including fees)</span><input id="payment_pessimistic" type="number" value="" readonly="readonly"/> $
						</div>
						<div class="field">
							<span class="label"><strong>Estimate</strong>: <br/>(what you enter in Codeable)</span><input id="estimate_pessimistic" type="number" value="" readonly="readonly"/> $
						</div>
						<div class="field">
							<span class="label">Your earnings</span><input id="earnings_pessimistic" type="number" value="" readonly="readonly"/> $
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>

	<?php codeable_last_fetch_info(); ?>

	<script type="text/javascript">
		jQuery(document).ready(function($) {
			function round(value, step) {
				step || (step = 1.0);
				var inv = 1.0 / step;
				return Math.round(value * inv) / inv;
			}

			function applyAllFees( value ) {
				var feeContractor = 1 / (1 - parseFloat( $('#contractor_fee').val() ) / 100);
				var feeClient     = 1 + parseFloat( $('#client_fee').val() ) / 100;

				<?php if ( 'full' === $fee_type ) : ?>
					var factor = feeContractor * feeClient;
				<?php elseif ( 'client' === $fee_type ) : ?>
					var factor = feeClient;
				<?php elseif ( 'none' === $fee_type ) : ?>
					var factor = 1;
				<?php endif; ?>

				return round(value * factor, 0.01);
			}

			function applyMyFees( value ) {
				var feeContractor = 1 / (1 - parseFloat( $('#contractor_fee').val() ) / 100);
				var feeClient     = 1 + parseFloat( $('#client_fee').val() ) / 100;

				<?php if ( 'full' === $fee_type ) : ?>
					var factor = feeContractor;
				<?php elseif ( 'client' === $fee_type ) : ?>
					var factor = 1;
				<?php elseif ( 'none' === $fee_type ) : ?>
					var factor = 1 / feeClient;
				<?php endif; ?>

				return round(value * factor, 0.01);
			}

			function withoutFees( value ) {
				var feeContractor = 1 / (1 - parseFloat( $('#contractor_fee').val() ) / 100);
				var feeClient     = 1 + parseFloat( $('#client_fee').val() ) / 100;

				<?php if ( 'full' === $fee_type ) : ?>
					var factor = 1;
				<?php elseif ( 'client' === $fee_type ) : ?>
					var factor = feeContractor;
				<?php elseif ( 'none' === $fee_type ) : ?>
					var factor = feeContractor * feeClient;
				<?php endif; ?>

				return round(value / factor, 0.01);
			}

			function calculate() {
				var optimistic  = parseFloat($('#optimistic_estimate').val());
				var likely      = parseFloat($('#likely_estimate').val());
				var pessimistic = parseFloat($('#pessimistic_estimate').val());
				var rate        = parseFloat($('#hourly_rate').val());

				var estimate_hours_standard    = (optimistic + 4 * likely + pessimistic) / 6;
				var estimate_hours_pessimistic = (optimistic + 2 * likely + 3 * pessimistic) / 6;

				var estimate_standard    = estimate_hours_standard * rate;
				var estimate_pessimistic = estimate_hours_pessimistic * rate;

				$('#estimate_hours_standard').val(round(estimate_hours_standard, 0.5));
				$('#estimate_hours_pessimistic').val(round(estimate_hours_pessimistic, 0.5));

				$('#payment').val(applyAllFees( estimate_standard ));
				$('#payment_pessimistic').val(applyAllFees( estimate_pessimistic ));

				$('#estimate').val(applyMyFees( estimate_standard ));
				$('#estimate_pessimistic').val(applyMyFees( estimate_pessimistic ));

				$('#earnings').val(withoutFees( estimate_standard ));
				$('#earnings_pessimistic').val(withoutFees( estimate_pessimistic ));
			}

			$('.calc-input').on('change', calculate);
			calculate();
		});
	</script>
	<?php
}
