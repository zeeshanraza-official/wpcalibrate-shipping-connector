/**
 * WPCalibrate Shipping Connector - Admin JavaScript
 *
 * @package WPCalibrate\ShippingConnector
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Copy Webhook URL to clipboard.
		$('#wpcalibrate-webhook-url').on('click', function() {
			this.select();
			if (navigator.clipboard) {
				navigator.clipboard.writeText(this.value).then(function() {
					window.alert('Webhook URL copied to clipboard!');
				});
			}
		});

		// Dynamic toggle for NDR reschedule date field.
		$('#ndr_action_type').on('change', function() {
			var action = $(this).val();
			var dateField = $('#ndr_reschedule_date').closest('p');
			if (action === 'RESCHEDULE') {
				dateField.slideDown(150);
			} else {
				dateField.slideUp(150);
			}
		}).trigger('change');
	});
})(jQuery);
