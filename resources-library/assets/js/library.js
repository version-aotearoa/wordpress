(function () {
	'use strict';

	console.log('[RL] library.js v1.1.19 loaded; fadeIn=' + (typeof fadeIn === 'function'));

	(function checkKeyframes() {
		var found = false;
		for (var i = 0; i < document.styleSheets.length; i++) {
			try {
				var rules = document.styleSheets[i].cssRules || [];
				for (var j = 0; j < rules.length; j++) {
					if (rules[j].name === 'rl-fade-in') {
						found = true;
					}
				}
			} catch (err) {
				// Ignore cross-origin stylesheets.
			}
		}
		console.log('[RL] keyframes rl-fade-in present:', found);
	})();

	function embedVideo(url) {
		var youtube = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/)|youtu\.be\/)([\w-]+)/);
		if (youtube) {
			return '<iframe src="https://www.youtube.com/embed/' + youtube[1] + '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="width:100%;aspect-ratio:16/9"></iframe>';
		}
		var vimeo = url.match(/vimeo\.com\/(\d+)/);
		if (vimeo) {
			return '<iframe src="https://player.vimeo.com/video/' + vimeo[1] + '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen style="width:100%;aspect-ratio:16/9"></iframe>';
		}
		return '<video src="' + url + '" controls autoplay playsinline style="width:100%"></video>';
	}

	function openVideo(url) {
		console.log('[RL] openVideo', url);
		var modal = document.createElement('div');
		modal.className = 'rl-modal';
		modal.setAttribute('role', 'dialog');
		modal.setAttribute('aria-modal', 'true');
		modal.innerHTML = '<div class="rl-modal-box"><div class="rl-modal-body">' + embedVideo(url) + '</div></div>';
		document.body.appendChild(modal);

		function close() {
			modal.remove();
			document.body.style.overflow = '';
			document.removeEventListener('keydown', onKey);
		}
		function onKey(e) {
			if (e.key === 'Escape') {
				close();
			}
		}
		modal.addEventListener('click', function (e) {
			if (e.target === modal) {
				close();
			}
		});
		document.addEventListener('keydown', onKey);
		document.body.style.overflow = 'hidden';
	}

	function copyText(text) {
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).catch(function () {});
			return;
		}
		var ta = document.createElement('textarea');
		ta.value = text;
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		document.body.appendChild(ta);
		ta.select();
		try {
			document.execCommand('copy');
		} catch (err) {
			// Ignore copy failures.
		}
		ta.remove();
	}

	function showToast(msg) {
		var t = document.createElement('div');
		t.className = 'rl-toast';
		t.textContent = msg;
		document.body.appendChild(t);
		setTimeout(function () {
			t.remove();
		}, 2000);
	}

	var container = document.getElementById('rl-posts');

	document.addEventListener('click', function (e) {
		var video = e.target.closest ? e.target.closest('[data-video]') : null;
		if (video) {
			console.log('[RL] data-video clicked:', video.getAttribute('data-video'));
			e.preventDefault();
			openVideo(video.getAttribute('data-video'));
		}
	});

	if (!container) {
		return;
	}

	var libraryBase = window.location.pathname;
	var head = document.querySelector('.rl-head');
	var loadMore = document.getElementById('rl-load-more');
	var formatsEl = document.getElementById('rl-formats');
	var title = document.querySelector('.rl-title');
	var baseTitle = title ? title.getAttribute('data-title') || title.textContent : '';
	var lastLibrary = null;
	var state = parseState();
	var expanded = {};

	updateSidebar();
	initEditBar();

	function parseState() {
		var params = new URLSearchParams(window.location.search);
		return {
			tag: params.get('tag') || '',
			format: params.get('format') || '',
			page: parseInt(params.get('page') || '1', 10) || 1,
			post: 0,
			favourites: params.get('favourites') === '1'
		};
	}

	function currentUrl(s) {
		var params = new URLSearchParams();
		if (s.favourites) params.set('favourites', '1');
		if (s.tag) params.set('tag', s.tag);
		if (s.format) params.set('format', s.format);
		if (s.page > 1) params.set('page', s.page);
		var qs = params.toString();
		return libraryBase + (qs ? '?' + qs : '');
	}

	function buildQuery(s) {
		var params = new URLSearchParams();
		params.set('action', 'rl_load_posts');
		params.set('nonce', window.rl_library.nonce);
		params.set('base', libraryBase);
		if (s.favourites) params.set('favourites', '1');
		if (s.tag) params.set('tag', s.tag);
		if (s.format) params.set('format', s.format);
		params.set('page', s.page);
		return params.toString();
	}

	function render(html, more, formatsHtml) {
		animateOut(function () {
			container.innerHTML = html;
			container.setAttribute('data-page', state.page);
			if (loadMore) {
				loadMore.style.display = more ? '' : 'none';
			}
			if (formatsEl && typeof formatsHtml === 'string' && formatsHtml !== '') {
				formatsEl.outerHTML = formatsHtml;
				formatsEl = document.getElementById('rl-formats');
			}
			if (title) {
				if (state.tag) {
					var tagEl = document.querySelector('[data-tag="' + state.tag + '"]');
					title.textContent = tagEl ? tagEl.textContent.replace(/^\s+|\s+$/g, '') : baseTitle;
				} else if (state.favourites) {
					title.textContent = window.rl_library.favourites;
				} else {
					title.textContent = window.rl_library.featured;
				}
			}
			document.querySelectorAll('[data-tag]').forEach(function (el) {
				el.classList.toggle('rl-tag-active', el.getAttribute('data-tag') === state.tag);
			});
			updateSidebar();
			document.querySelectorAll('.rl-fav-nav').forEach(function (el) {
				el.classList.toggle('rl-tag-active', state.favourites && !state.tag);
			});
			restoreEditBar();
			fadeIn(container);
		});
	}

	function showLibraryHead() {
		if (head) {
			head.style.display = '';
		}
	}

	function showSingle(html) {
		if (head) {
			head.style.display = 'none';
		}
		if (loadMore) {
			loadMore.style.display = 'none';
		}
		container.innerHTML = html;
	}

	function empty() {
		container.innerHTML = '<p class="rl-empty">' + window.rl_library.no_results + '</p>';
	}

	function fadeIn(el) {
		console.log('[RL] fadeIn', el ? el.id || el.className : el);
		if (!el) {
			return;
		}
		el.classList.remove('rl-fade');
		void el.offsetWidth;
		el.classList.add('rl-fade');
	}

	function animateOut(cb) {
		container.classList.add('rl-slide-out');
		var done = false;
		function finish() {
			if (done) {
				return;
			}
			done = true;
			container.classList.remove('rl-slide-out');
			cb();
		}
		container.addEventListener('animationend', function handler(e) {
			if (e.target === container) {
				container.removeEventListener('animationend', handler);
				finish();
			}
		});
		setTimeout(finish, 600);
	}

	function updateSidebar() {
		var bySlug = {};
		document.querySelectorAll('[data-tag]').forEach(function (el) {
			bySlug[el.getAttribute('data-tag')] = el;
		});
		var chain = [];
		var slug = state.tag;
		while (slug && bySlug[slug]) {
			chain.push(slug);
			var parent = bySlug[slug].getAttribute('data-parent');
			if (!parent) {
				break;
			}
			slug = parent;
		}
		function inChain(s) {
			return chain.indexOf(s) !== -1;
		}
		function isOpen(parentSlug) {
			if (typeof expanded[parentSlug] !== 'undefined') {
				return expanded[parentSlug];
			}
			return inChain(parentSlug);
		}
		document.querySelectorAll('[data-tag]').forEach(function (el) {
			var s = el.getAttribute('data-tag');
			var parent = el.getAttribute('data-parent');
			var visible;
			if (!parent) {
				visible = true;
			} else if (inChain(s)) {
				visible = true;
			} else {
				visible = isOpen(parent);
			}
			el.classList.toggle('is-visible', visible);
		});
		document.querySelectorAll('.rl-tag-arrow').forEach(function (btn) {
			var s = btn.getAttribute('data-arrow');
			var open = isOpen(s);
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			btn.classList.toggle('is-open', open);
		});
	}

	function toggleChildren(slug) {
		expanded[slug] = expanded[slug] ? false : true;
		updateSidebar();
	}

	var origEdit = null;

	function initEditBar() {
		var el = document.getElementById('wp-admin-bar-edit');
		if (!el) {
			return;
		}
		var a = el.querySelector('.ab-item') || el.querySelector('a');
		if (!a) {
			return;
		}
		origEdit = { href: a.getAttribute('href'), html: a.innerHTML };
	}

	function setEditBar(href, label) {
		var el = document.getElementById('wp-admin-bar-edit');
		if (!el) {
			return;
		}
		var a = el.querySelector('.ab-item') || el.querySelector('a');
		if (!a || !href) {
			return;
		}
		a.setAttribute('href', href);
		var textNodes = Array.prototype.filter.call(a.childNodes, function (n) {
			return n.nodeType === 3;
		});
		if (textNodes.length) {
			textNodes[textNodes.length - 1].nodeValue = ' ' + label;
		} else {
			a.appendChild(document.createTextNode(' ' + label));
		}
	}

	function restoreEditBar() {
		if (!origEdit) {
			return;
		}
		var el = document.getElementById('wp-admin-bar-edit');
		if (!el) {
			return;
		}
		var a = el.querySelector('.ab-item') || el.querySelector('a');
		if (!a) {
			return;
		}
		a.setAttribute('href', origEdit.href);
		a.innerHTML = origEdit.html;
	}

	function load(s, replace) {
		showLibraryHead();
		container.classList.add('rl-loading');
		container.textContent = window.rl_library.loading;
		fetch(window.rl_library.ajax_url + '?' + buildQuery(s))
			.then(function (res) { return res.json(); })
			.then(function (json) {
				container.classList.remove('rl-loading');
				if (!json || !json.success) {
					empty();
					return;
				}
				var formats = json.data.formats || [];
				if (s.format && formats.length && formats.indexOf(s.format) === -1) {
					s.format = '';
					window.history.replaceState(s, '', currentUrl(s));
					load(s, replace);
					return;
				}
				state = s;
				render(json.data.html, json.data.more, json.data.formats_html);
			})
			.catch(function () {
				container.classList.remove('rl-loading');
				empty();
			});
	}

	function loadSingle(postId) {
		var params = new URLSearchParams();
		params.set('action', 'rl_load_single');
		params.set('nonce', window.rl_library.nonce);
		params.set('base', libraryBase);
		params.set('post_id', postId);
		if (state.tag) params.set('tag', state.tag);
		container.classList.add('rl-loading');
		container.textContent = window.rl_library.loading;
		fetch(window.rl_library.ajax_url + '?' + params.toString())
			.then(function (res) { return res.json(); })
			.then(function (json) {
				container.classList.remove('rl-loading');
				if (!json || !json.success) {
					return;
				}
				showSingle(json.data.html);
				setEditBar(json.data.edit_url, json.data.edit_label);
			})
			.catch(function () {
				container.classList.remove('rl-loading');
			});
	}

	function go(s, replace) {
		state = s;
		load(s, replace);
		if (replace) {
			window.history.replaceState(state, '', currentUrl(s));
		} else {
			window.history.pushState(state, '', currentUrl(s));
		}
	}

	function goSingle(postId, url) {
		lastLibrary = Object.assign({}, state);
		loadSingle(postId);
		window.history.pushState({ post: postId }, '', url);
	}

	function applyFormatFilter(format) {
		state.format = format;
		state.page = 1;
		window.history.pushState(state, '', currentUrl(state));

		var emptyMsg = container.querySelector('.rl-empty');
		if (emptyMsg) {
			emptyMsg.remove();
		}

		var leaving = [];
		var entering = [];
		var shown = 0;

		container.querySelectorAll('.rl-card').forEach(function (card) {
			var match = !state.format || card.getAttribute('data-format') === state.format;
			var visible = card.style.display !== 'none';
			if (match) {
				shown++;
				if (!visible) {
					entering.push(card);
				}
			} else if (visible) {
				leaving.push(card);
			}
		});

		var before = new WeakMap();
		container.querySelectorAll('.rl-card').forEach(function (card) {
			if (card.style.display !== 'none') {
				before.set(card, card.getBoundingClientRect());
			}
		});

		function reflow() {
			entering.forEach(function (card) {
				card.style.display = '';
			});

			container.querySelectorAll('.rl-card').forEach(function (card) {
				if (card.style.display === 'none') {
					return;
				}
				var old = before.get(card);
				if (!old) {
					return;
				}
				var next = card.getBoundingClientRect();
				var dx = old.left - next.left;
				var dy = old.top - next.top;
				if (dx || dy) {
					card.style.transition = 'none';
					card.style.transform = 'translate(' + dx + 'px,' + dy + 'px)';
					void card.offsetWidth;
					card.style.transition = 'transform .5s ease';
					card.style.transform = 'translate(0,0)';
					(function (el) {
						setTimeout(function () {
							el.style.transition = '';
							el.style.transform = '';
						}, 550);
					})(card);
				}
			});

			entering.forEach(function (card) {
				fadeIn(card);
			});

			if (shown === 0) {
				var p = document.createElement('p');
				p.className = 'rl-empty';
				p.textContent = window.rl_library.no_results;
				container.appendChild(p);
			}

			document.querySelectorAll('.rl-chip').forEach(function (el) {
				el.classList.toggle('rl-chip-active', el.getAttribute('data-format') === state.format);
			});
		}

		if (leaving.length) {
			var done = 0;
			leaving.forEach(function (card) {
				card.classList.add('rl-card-leave');
				var onEnd = function (e) {
					if (e.target === card) {
						card.removeEventListener('animationend', onEnd);
						done++;
						if (done === leaving.length) {
							done = leaving.length;
							leaving.forEach(function (c) {
								c.classList.remove('rl-card-leave');
								c.style.display = 'none';
							});
							reflow();
						}
					}
				};
				card.addEventListener('animationend', onEnd);
			});
			setTimeout(function () {
				if (done < leaving.length) {
					done = leaving.length;
					leaving.forEach(function (c) {
						c.classList.remove('rl-card-leave');
						c.style.display = 'none';
					});
					reflow();
				}
			}, 600);
		} else {
			reflow();
		}
	}

	function shareCard(url) {
		if (navigator.share) {
			navigator.share({ title: document.title, url: url }).catch(function () {});
			return;
		}
		copyText(url);
		showToast(window.rl_library.link_copied);
	}

	function toggleFavourite(btn) {
		var id = btn.getAttribute('data-fav');
		var params = new URLSearchParams();
		params.set('action', 'rl_toggle_favourite');
		params.set('nonce', window.rl_library.nonce);
		params.set('post_id', id);
		fetch(window.rl_library.ajax_url + '?' + params.toString())
			.then(function (res) { return res.json(); })
			.then(function (json) {
				if (!json || !json.success) {
					return;
				}
				var faved = !!json.data.faved;
				btn.classList.toggle('rl-faved', faved);
				btn.setAttribute('aria-pressed', faved ? 'true' : 'false');
			});
	}

	var favNav = document.querySelector('.rl-fav-nav');
	if (favNav) {
		favNav.addEventListener('click', function (e) {
			e.preventDefault();
			go({ tag: '', format: '', page: 1, favourites: true }, false);
		});
	}

	document.querySelectorAll('[data-tag]').forEach(function (el) {
		el.addEventListener('click', function (e) {
			e.preventDefault();
			go({ tag: el.getAttribute('data-tag'), format: '', page: 1, favourites: false }, false);
		});
	});

	document.querySelectorAll('.rl-tag-arrow').forEach(function (el) {
		el.addEventListener('click', function (e) {
			e.preventDefault();
			toggleChildren(el.getAttribute('data-arrow'));
		});
	});

	document.addEventListener('click', function (e) {
		var target = e.target;
		var fav = target.closest ? target.closest('.rl-fav') : null;
		var share = target.closest ? target.closest('.rl-share') : null;
		var chip = target.closest ? target.closest('.rl-chip') : null;
		var single = target.closest ? target.closest('a[data-rl-single]') : null;
		var back = target.closest ? target.closest('.rl-back') : null;

		if (fav) {
			e.preventDefault();
			toggleFavourite(fav);
		} else if (share) {
			e.preventDefault();
			shareCard(share.getAttribute('data-share'));
		} else if (chip) {
			e.preventDefault();
			var format = chip.getAttribute('data-format') || '';
			var allLoaded = !loadMore || loadMore.style.display === 'none';
			if (allLoaded) {
				applyFormatFilter(format);
			} else {
				go({ tag: state.tag, format: format, page: 1, favourites: state.favourites }, false);
			}
		} else if (single) {
			e.preventDefault();
			goSingle(single.getAttribute('data-rl-single'), single.getAttribute('href'));
		} else if (back) {
			e.preventDefault();
			if (lastLibrary) {
				go(lastLibrary, true);
			} else {
				go({ tag: '', format: '', page: 1 }, true);
			}
		}
	});

	if (loadMore) {
		loadMore.addEventListener('click', function () {
			var next = { tag: state.tag, format: state.format, page: state.page + 1, favourites: state.favourites };
			state = next;
			fetch(window.rl_library.ajax_url + '?' + buildQuery(next))
				.then(function (res) { return res.json(); })
				.then(function (json) {
					if (!json || !json.success) {
						return;
					}
					var before = container.querySelectorAll('.rl-card').length;
					container.insertAdjacentHTML('beforeend', json.data.html);
					var cards = container.querySelectorAll('.rl-card');
					for (var i = before; i < cards.length; i++) {
						cards[i].classList.add('rl-fade');
					}
					container.setAttribute('data-page', next.page);
					if (!json.data.more && loadMore) {
						loadMore.style.display = 'none';
					}
					window.history.pushState(state, '', currentUrl(state));
				});
		});
	}

	window.addEventListener('popstate', function (e) {
		if (e.state && e.state.post) {
			loadSingle(e.state.post);
		} else {
			state = parseState();
			load(state, true);
		}
	});
})();
