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
	$fee_contractor = 'full' === $fee_type ? 10 : 0;
	$fee_client     = 'none' !== $fee_type ? 17.5 : 0;

	if ( isset( $_GET['fee_client'] ) && is_numeric( $_GET['fee_client'] ) ) {
		if ( 'none' !== $fee_type ) {
			$fee_client = (float) $_GET['fee_client'];
		}
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
							<span class="label">Optimistic value:  </span><input id="optimistic_estimate" type="number" step="0.25" min="1" /> hours
						</div>
						<div class="field">
							<span class="label">Most likely value: </span><input id="likely_estimate" type="number" step="0.25" min="1" /> hours
						</div>
						<div class="field">
							<span class="label">Pessimistic value: </span><input id="pessimistic_estimate" type="number" step="0.25" min="1" /> hours
						</div>
					</div>
				</div>

				<div id="fees" class="postbox">
					<h2 class="hndle">
						<span>Rate and Fees</span>
					</h2>
					<div class="inside">
						<div class="field">
							<span class="label">Your hourly rate: </span><input id="hourly_rate" type="number" value="<?php echo esc_attr( $rate ); ?>" min="35" max="1000" /> $
						</div>
						<?php if ( 'full' === $fee_type ) : ?>
							<div class="field">
								<span class="label">Contractor fee: </span><input id="contractor_fee" type="number" step="0.01" value="<?php echo esc_attr( $fee_contractor ); ?>" max="100" min="0" /> %
								<p class="description">
									This is the fee Codeable charges you. It will be added on top of your hourly rate, so you actually get paid the rate you entered above.</p>
							</div>
						<?php else : ?>
							<input id="contractor_fee" type="hidden" value="0" />
						<?php endif; ?>
						<?php if ( 'none' !== $fee_type ) : ?>
							<div class="field">
								<span class="label">Client fee: </span><input id="client_fee" type="number" step="0.01" value="<?php echo esc_attr( $fee_client ); ?>" max="100" min="0" /> %
								<p class="description">
									This is the fee, that the client has to pay on top of your rate.</p>
							</div>
						<?php else : ?>
							<input id="client_fee" type="hidden" value="0" />
							<p class="description">
								Note: Your hourly rate already includes all fees.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="estimate-row">
				<div id="totals" class="postbox">
					<h2 class="hndle">
						<span>Totals</span>
					</h2>
					<div class="inside">
						<div class="field">
							<span class="label">PERT Standard Estimate: </span><input id="estimate_hours_standard" type="number" value="" readonly="readonly"/> hours
						</div>
						<div class="field">
							<span class="label">Estimate for client <br/>(including fees): </span><input id="estimate" type="number" value="" readonly="readonly"/> $
							<p class="description">
								Take these metrics as consideration if you put more weight on the realistic value. (Proper documentation, clear scope etc.)</p>
						</div>
					</div>
				</div>

				<div id="totals_pessimistic" class="postbox">
					<h2 class="hndle">
						<span>Totals with extra buffer</span>
					</h2>
					<div class="inside">
						<div class="field">
							<span class="label">PERT Cautious Estimate: </span><input id="estimate_hours_pessimistic" type="number" value="" readonly="readonly"/> hours
						</div>
						<div class="field">
							<span class="label">Estimate for client <br/>(including fees): </span><input id="estimate_pessimistic" type="number" value="" readonly="readonly"/> $
							<p class="description">
								Take these metrics as consideration if you put more weight on the pessimistic value. (Not proper documentation, not so clear scope etc.)</p>
						</div>
					</div>
				</div>
			</div>

			<button
				class="button button-primary"
				id="calculate"
				type="submit">
				Calculate
			</button>
			<button
				class="button"
				id="reset_estimates"
				type="button">
				Reset estimates
			</button>
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

			function applyFees( value ) {
				var feeContractor = parseFloat( $('#contractor_fee').val() );
				var feeClient     = parseFloat( $('#client_fee').val() );

				return value / (1 - (feeContractor / 100)) * (1 + (feeClient / 100));
			}

			$('#estimator').on('submit', function(e) {
				e.stopPropagation();
				var optimistic  = parseFloat($('#optimistic_estimate').val());
				var likely      = parseFloat($('#likely_estimate').val());
				var pessimistic = parseFloat($('#pessimistic_estimate').val());
				var rate        = parseFloat($('#hourly_rate').val());

				var estimate_hours_standard    = (optimistic + 4 * likely + pessimistic) / 6;
				var estimate_hours_pessimistic = (optimistic + 2 * likely + 3 * pessimistic) / 6;

				var estimate_standard    = estimate_hours_standard * rate;
				var estimate_pessimistic = estimate_hours_pessimistic * rate;
				var estimate_with_fees_standard    = applyFees( estimate_standard );
				var estimate_with_fees_pessimistic = applyFees( estimate_pessimistic );

				$('#estimate_hours_standard').val(round(estimate_hours_standard, 0.5));
				$('#estimate_hours_pessimistic').val(round(estimate_hours_pessimistic, 0.5));

				$('#estimate').val(round(estimate_with_fees_standard,0.01));
				$('#estimate_pessimistic').val(round(estimate_with_fees_pessimistic,0.01));
				return false;
			});

			$('#reset_estimates').on('click', function(e) {
				e.stopPropagation();

				$('input:not(#hourly_rate):not(#contractor_fee)').val('');
			});

		});
	</script>
	<?php
}
