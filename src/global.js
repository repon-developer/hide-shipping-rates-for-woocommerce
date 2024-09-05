(function ($) {
	$('#hide-shipping-rates-modal').on('open', function () {
		$(this).addClass('modal-opened')
	})

	$('#hide-shipping-rates-modal').on('close', function () {
		$(this).removeClass('modal-opened')
	})

	$('#hide-shipping-rates-modal [data-modal-close]').on('click', function (e) {
		e.preventDefault();
		$('#hide-shipping-rates-modal').trigger('close')
	})

	$(document).keyup(function (e) {
		if (e.key === "Escape") {
			$('#hide-shipping-rates-modal').trigger('close')
		}
	});

	$('body').on('click', '.btn-open-hide-shipping-rates-license-form', function (e) {
		e.preventDefault();
		$('#hide-shipping-rates-modal').trigger('open')
	})
})(jQuery)