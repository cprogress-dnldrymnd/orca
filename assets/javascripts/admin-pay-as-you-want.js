/* global jQuery */
(function ($) {
	'use strict';

	function showPayAsYouWantFields() {
		var type = $('#product-type').val();
		var isPwyw = type === 'pay_as_you_want';

		$('.show_if_pay_as_you_want').each(function () {
			$(this).toggle(isPwyw);
		});
		$('.hide_if_pay_as_you_want').toggle(!isPwyw);

		if (isPwyw) {
			$('.product_data_tabs .general_options').show();
			$('.options_group.pricing').show();
			// Suggested/min/max replace regular/sale for this type.
			$('._regular_price_field, ._sale_price_field').hide();
		}
	}

	$(function () {
		$('.options_group.pricing').addClass('show_if_pay_as_you_want');
		$('._tax_status_field').closest('.options_group').addClass('show_if_pay_as_you_want');

		showPayAsYouWantFields();
		$('#product-type').on('change', showPayAsYouWantFields);
		$(document.body).on('woocommerce-product-type-change', showPayAsYouWantFields);
	});
})(jQuery);
