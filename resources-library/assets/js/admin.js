(function () {
	'use strict';

	function isAreaEmpty(area) {
		if (area.querySelector('.postbox')) {
			return false;
		}
		var iframe = area.querySelector('iframe');
		if (iframe) {
			try {
				if (iframe.contentDocument && iframe.contentDocument.querySelector('.postbox')) {
					return false;
				}
			} catch (err) {
				// Ignore cross-origin access errors.
			}
		}
		return true;
	}

	function hideIfEmpty(area) {
		if (area.classList.contains('rl-hidden-empty')) {
			return;
		}
		if (!isAreaEmpty(area)) {
			return;
		}
		area.style.display = 'none';
		area.classList.add('rl-hidden-empty');
	}

	function unhideIfPopulated(area) {
		if (!area.classList.contains('rl-hidden-empty')) {
			return;
		}
		if (isAreaEmpty(area)) {
			return;
		}
		area.style.display = '';
		area.classList.remove('rl-hidden-empty');
	}

	function check() {
		document.querySelectorAll('.edit-post-meta-boxes-area, .block-editor-meta-boxes-area, .edit-post-meta-boxes-main').forEach(function (area) {
			unhideIfPopulated(area);
			hideIfEmpty(area);
		});
	}

	var observer = new MutationObserver(check);
	observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class'] });

	document.addEventListener('change', function (e) {
		var toggle = e.target;
		if (!toggle.classList || !toggle.classList.contains('rl-featured-toggle')) {
			return;
		}
		var id = toggle.getAttribute('data-id');
		var nonce = toggle.getAttribute('data-nonce');
		var params = new URLSearchParams();
		params.set('action', 'rl_toggle_featured');
		params.set('nonce', nonce);
		params.set('post_id', id);
		fetch(window.ajaxurl + '?' + params.toString())
			.then(function (res) { return res.json(); })
			.then(function (json) {
				if (!json || !json.success) {
					toggle.checked = !toggle.checked;
				}
			});
	});

	document.addEventListener('click', function (e) {
		var link = e.target.closest ? e.target.closest('.category-tabs a') : null;
		if (!link) {
			return;
		}
		e.preventDefault();
		var tabs = link.closest('.category-tabs');
		if (!tabs) {
			return;
		}
		var panelId = link.getAttribute('href');
		tabs.querySelectorAll('a').forEach(function (a) {
			a.setAttribute('aria-selected', a === link ? 'true' : 'false');
		});
		tabs.querySelectorAll('li').forEach(function (li) {
			li.classList.toggle('tabs', li.contains(link));
		});
		var div = tabs.parentNode;
		div.querySelectorAll('.tabs-panel').forEach(function (panel) {
			var show = '#' + panel.id === panelId;
			panel.style.display = show ? '' : 'none';
			panel.setAttribute('aria-hidden', show ? 'false' : 'true');
		});
	});

	if (window.jQuery) {
		window.jQuery(function ($) {
			var TARGET = '#rl_tags_box, #rl_formats_box, #rl_library_options';
			function blockDrag(container) {
				var sortable = $(container).data('ui-sortable') || $(container).data('sortable');
				if (!sortable || !sortable.options) {
					return;
				}
				var cur = sortable.options.cancel || '';
				if (cur && cur.indexOf('#rl_tags_box') !== -1) {
					return;
				}
				sortable.options.cancel = cur ? cur + ', ' + TARGET : TARGET;
			}
			$(document).on('sortcreate', '.meta-box-sortables', function () {
				blockDrag(this);
			});
			$(document).on('mouseenter', '.meta-box-sortables', function () {
				blockDrag(this);
			});
		});
	}

	function boot() {
		setTimeout(check, 200);
		window.addEventListener('load', function () {
			setTimeout(check, 400);
		});
		var count = 0;
		var poll = setInterval(function () {
			check();
			if (++count > 120) {
				clearInterval(poll);
			}
		}, 500);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
