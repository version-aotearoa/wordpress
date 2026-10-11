(function ($) {
	'use strict';

	$(function () {
		$(document).on('click', '.rl-media-select', function (e) {
			e.preventDefault();
			var $wrap = $(this).closest('.rl-media-field');
			var frame = wp.media({
				title: 'Default featured image',
				button: { text: 'Use image' },
				multiple: false
			});
			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
				$wrap.find('.rl-media-id').val(attachment.id);
				$wrap.find('.rl-media-preview').html('<img src="' + url + '" style="max-width:150px;height:auto;display:block;margin-bottom:.5em">');
				$wrap.find('.rl-media-remove').show();
			});
			frame.open();
		});

		$(document).on('click', '.rl-media-remove', function (e) {
			e.preventDefault();
			var $wrap = $(this).closest('.rl-media-field');
			$wrap.find('.rl-media-id').val('0');
			$wrap.find('.rl-media-preview').empty();
			$(this).hide();
		});
	});
})(jQuery);
