<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function codeable_estimate_callback() {
    ?>
    <div class="wrap">
        <h2>PERT Estimator</h2>
        <form id="estimator">
            <div id="estimates" class="section">
                <div class="field">
                    <span class="label">Optimistic value (hours):  </span><input id="optimistic_estimate" type="number" step="0.25" min="1" />
                </div>
                <div class="field">
                    <span class="label">Most likely value (hours): </span><input id="likely_estimate" type="number" step="0.25" min="1" />
                </div>
                <div class="field">
                    <span class="label">Pessimistic value (hours): </span><input id="pessimistic_estimate" type="number" step="0.25" min="1" />
                </div>
                <div class="field">
                    <span class="label">Hourly rate: $ </span><input id="hourly_rate" type="number" value="80" />
                </div>
            </div>

            <div id="fees" class="section">
                <div class="field">
                    <span class="label">Contractor fee (%): </span><input id="contractor_fee" type="number" step="0.01" value="10" />
                    <p class="description">
                        This fee will be added to the estimate, so that it can be passed on to the client.
                        If your hourly rate already takes the Contractor Fee into account, you can set this to zero.</p>
                </div>
            </div>

            <div id="totals" class="section">
                <div class="field">
                    <span class="label">PERT Estimate (hours): </span><input id="estimate_hours" type="number" value="" readonly="readonly"/>
                </div>
                <div class="field">
                    <span class="label">Estimate for client <br/>(including fees, in $ ): </span><input id="estimate" type="number" value="" readonly="readonly"/>
                </div>
            </div>

            <button id="calculate" type="submit">Calculate</button>
            <button id="reset_estimates" type="button">Reset estimates</button>
        </form>
    </div>

    <style type="text/css">
        body {
            font-family: Helvetica;
        }
        .section {
            border: 1px solid silver;
            margin-bottom: 0.5em;
            padding: 0 0.5em 0.5em;
        }
        .field {
            margin-top: 0.5em;
        }
        .label {
            display: inline-block;
            width: 15em;
        }
        .description {
            font-size: 95%;
            margin-top: 4px;
            margin-bottom: 0;
        }
    </style>
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

                var estimate_hours = (optimistic + 4 * likely + pessimistic) / 6;
                var estimate = estimate_hours * $('#hourly_rate').val();
                var estimate_with_fees = estimate / (1 - ($('#contractor_fee').val() / 100));

                $('#estimate_hours').val(round(estimate_hours, 0.5));
                $('#estimate').val(Math.round(estimate_with_fees * 100) / 100);
                return false;
            });
        });
    </script>
    <?php
}
