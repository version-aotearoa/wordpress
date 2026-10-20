(function ($) {
	'use strict';

	$(function () {
		var $box = $('.ga-coauthors');
		if (!$box.length) {
			return;
		}

		function serialize() {
			var ids = [];
			$box.find('.ga-coauthors-list .ga-coauthor').each(function () {
				ids.push($(this).attr('data-id'));
			});
			$box.find('.ga-coauthors-value').val(ids.join(','));
		}

		$box.find('.ga-coauthors-list').sortable({
			items: '> .ga-coauthor',
			handle: '.ga-handle',
			placeholder: 'ga-coauthor-placeholder',
			forcePlaceholderSize: true,
			stop: serialize
		});

		$box.on('click', '.ga-add', function (e) {
			e.preventDefault();
			var $picker = $box.find('.ga-coauthor-picker');
			var id = $picker.val();
			if (!id) {
				return;
			}
			if ($box.find('.ga-coauthor[data-id="' + id + '"]').length) {
				$picker.val('');
				return;
			}
			var name = $picker.find('option:selected').text();
			var $li = $('<li class="ga-coauthor" />')
				.attr('data-id', id)
				.append('<span class="ga-handle" aria-hidden="true"><span class="dashicons dashicons-menu"></span></span>')
				.append($('<span class="ga-coauthor-name" />').text(name))
				.append('<button type="button" class="button-link ga-remove" aria-label="Remove">&times;</button>');
			$box.find('.ga-coauthors-list').append($li);
			$picker.val('');
			serialize();
		});

		$box.on('click', '.ga-remove', function (e) {
			e.preventDefault();
			$(this).closest('.ga-coauthor').remove();
			serialize();
		});
	});
})(jQuery);
