/**
 * Hoeveel Zijn Er Nog — Admin JS
 *
 * Provides:
 *   - Autocomplete for merk/handelsbenaming inputs on the Add Model form.
 */
/* global hzenAdmin, jQuery */
(function ($) {
	'use strict';

	/**
	 * Lightweight autocomplete widget.
	 * Fires an admin-ajax request and shows a dropdown.
	 */
	function initAutocomplete() {
		var $inputs = $('.hzen-autocomplete');
		if (!$inputs.length) return;

		$inputs.each(function () {
			var $input = $(this);
			var field  = $input.data('field') || 'merk';
			var $list  = null;
			var timer  = null;

			$input.attr('autocomplete', 'off');

			$input.on('input', function () {
				var term = $input.val();
				clearTimeout(timer);

				if (term.length < 2) {
					hideDropdown();
					return;
				}

				timer = setTimeout(function () {
					$.getJSON(
						hzenAdmin.ajaxUrl,
						{
							action: 'hzen_search_rdw',
							nonce:  hzenAdmin.nonce,
							field:  field,
							term:   term
						},
						function (response) {
							if (response.success && response.data.length) {
								showDropdown($input, response.data);
							} else {
								hideDropdown();
							}
						}
					);
				}, 250);
			});

			$input.on('keydown', function (e) {
				if (!$list) return;
				var $items = $list.find('li');
				var $active = $items.filter('.hzen-active');

				if (e.key === 'ArrowDown') {
					e.preventDefault();
					if ($active.length) {
						$active.removeClass('hzen-active').next().addClass('hzen-active');
					} else {
						$items.first().addClass('hzen-active');
					}
				} else if (e.key === 'ArrowUp') {
					e.preventDefault();
					if ($active.length) {
						$active.removeClass('hzen-active').prev().addClass('hzen-active');
					}
				} else if (e.key === 'Enter' && $active.length) {
					e.preventDefault();
					$input.val($active.text());
					hideDropdown();
				} else if (e.key === 'Escape') {
					hideDropdown();
				}
			});

			$(document).on('click.hzen_ac', function (e) {
				if (!$(e.target).closest($input).length) {
					hideDropdown();
				}
			});

			function showDropdown($el, items) {
				hideDropdown();
				$list = $('<ul class="hzen-autocomplete-dropdown"></ul>');
				$.each(items, function (i, item) {
					var $li = $('<li></li>').text(item.value);
					$li.on('click', function () {
						$el.val(item.value);
						hideDropdown();
					});
					$list.append($li);
				});

				var offset = $el.offset();
				$list.css({
					top:  offset.top + $el.outerHeight(),
					left: offset.left
				});
				$('body').append($list);
			}

			function hideDropdown() {
				if ($list) {
					$list.remove();
					$list = null;
				}
			}
		});
	}

	$(document).ready(function () {
		initAutocomplete();
	});
}(jQuery));
