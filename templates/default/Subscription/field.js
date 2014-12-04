(function ($) {

	$.fn.multipleInput = function () {

		return this.each(function () {

			// create html elements

			// list of email addresses as unordered list
			$list = $('<ul />');

			// input
			var $input = $('<input type="text" />').keyup(function (event) {

				if (event.which == 32 || event.which == 188) {
					// key press is space or comma
					var val = $(this).val().slice(0, -1); // remove space/comma from value

					// append to list of emails with remove button
					$list.append($('<li class="multipleInput-email"><span>' + val + '</span></li>')
						.append($('<a href="#" class="multipleInput-close" title="Remove" />')
							.click(function (e) {
								$(this).parent().remove();
								e.preventDefault();
							})
						)
					);
					$(this).attr('placeholder', '');
					// empty input
					$(this).val('');
				}

			});

			// container div
			var $container = $('<div class="multipleInput-container" />').click(function () {
				$input.focus();
			});

			// insert elements into DOM
			$container.append($list).append($input).insertAfter($(this));

			// add onsubmit handler to parent form to copy emails into original input as csv before submitting
			var $orig = $(this);
			$(this).closest('form').submit(function (e) {

				var emails = new Array();
				$('.multipleInput-email span').each(function () {
					emails.push($(this).html());
				});
				emails.push($input.val());

				$orig.val(emails.join());

			});

			return $(this).hide();

		});

	};
})(jQuery);


$(document).ready(function () {
	$('#sr_ms_email_list_field').multipleInput();
});