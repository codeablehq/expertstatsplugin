<?php
/**
 *
 * @package wpcable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function codeable_estimate_callback() {
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
							<span class="label">Your hourly rate: </span><input id="hourly_rate" type="number" value="80" min="35" max="1000" /> $
						</div>
						<div class="field">
							<span class="label">Contractor fee: </span><input id="contractor_fee" type="number" step="0.01" value="10" max="100" min="0" /> %
							<p class="description">
								This fee will be added to the estimate, so that it can be passed on to the client.
								If your hourly rate already takes the Contractor Fee into account, you can set this to zero.</p>
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

	<script type="text/javascript">
		jQuery(document).ready(function($) {
			function round(value, step) {
				step || (step = 1.0);
				var inv = 1.0 / step;
				return Math.round(value * inv) / inv;
			}

			$('#estimator').on('submit', function(e) {
				e.stopPropagation();
				var optimistic = parseFloat($('#optimistic_estimate').val());
				var likely = parseFloat($('#likely_estimate').val());
				var pessimistic = parseFloat($('#pessimistic_estimate').val());

				var estimate_hours_standard = (optimistic + 4 * likely + pessimistic) / 6;
				var estimate_hours_pessimistic = (optimistic + 2 * likely + 3 * pessimistic) / 6;

				var estimate_standard = estimate_hours_standard * $('#hourly_rate').val();
				var estimate_pessimistic = estimate_hours_pessimistic * $('#hourly_rate').val();
				var estimate_with_fees_standard = estimate_standard / (1 - ($('#contractor_fee').val() / 100));
				var estimate_with_fees_pessimistic = estimate_pessimistic / (1 - ($('#contractor_fee').val() / 100));

				$('#estimate_hours_standard').val(round(estimate_hours_standard, 0.5));
				$('#estimate_hours_pessimistic').val(round(estimate_hours_pessimistic, 0.5));

				$('#estimate').val(Math.round(estimate_with_fees_standard ));
				$('#estimate_pessimistic').val(Math.round(estimate_with_fees_pessimistic));
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
