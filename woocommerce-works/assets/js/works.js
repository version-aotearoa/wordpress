/**
 * WooCommerce Works
 *
 * 1. Masonry layout (no dependencies, no jQuery: shortest-column placement).
 * 2. Category filtering + "Load more" with URL state.
 * 3. Quick view dialog (native <dialog>) with gallery and add to cart.
 */
(function () {
	'use strict';

	var data = window.wcwData || {};
	var i18n = data.i18n || {};

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
			return;
		}
		document.addEventListener('DOMContentLoaded', fn);
	}

	function debounce(fn, wait) {
		var timer = null;
		return function () {
			var context = this;
			var args = arguments;
			window.clearTimeout(timer);
			timer = window.setTimeout(function () {
				fn.apply(context, args);
			}, wait);
		};
	}

	function each(list, fn, thisArg) {
		Array.prototype.forEach.call(list, fn, thisArg);
	}

	/* ------------------------------------------------------------------ masonry */

	function Masonry(root, grid, gap) {
		this.root = root;
		this.grid = grid;
		this.gap = gap;
		this.items = Array.prototype.slice.call(grid.querySelectorAll('.wcw-item'));
		this.scheduled = false;

		this.onResize = debounce(this.layout.bind(this), 120);

		if (window.ResizeObserver) {
			this.ro = new ResizeObserver(this.onResize);
			this.ro.observe(root);
		}
		window.addEventListener('resize', this.onResize);
		window.addEventListener('orientationchange', this.onResize);

		this.grid.setAttribute('data-masonry', '');
		this.watch(this.items);
		this.layout();
	}

	Masonry.prototype.columns = function () {
		var width = window.innerWidth;
		var desktop = parseInt(this.root.getAttribute('data-cols'), 10) || 3;
		var tablet = parseInt(this.root.getAttribute('data-cols-md'), 10) || 2;
		var mobile = parseInt(this.root.getAttribute('data-cols-sm'), 10) || 1;

		if (width <= 640) {
			return mobile;
		}
		if (width <= 960) {
			return tablet;
		}
		return desktop;
	};

	Masonry.prototype.schedule = function () {
		if (this.scheduled) {
			return;
		}
		this.scheduled = true;
		var self = this;
		window.requestAnimationFrame(function () {
			self.scheduled = false;
			self.layout();
		});
	};

	Masonry.prototype.watch = function (items) {
		var self = this;
		items.forEach(function (item) {
			each(item.querySelectorAll('img'), function (img) {
				if (img.complete) {
					return;
				}
				img.addEventListener('load', self.schedule.bind(self), { once: true });
				img.addEventListener('error', self.schedule.bind(self), { once: true });
			});
		});
	};

	Masonry.prototype.ready = function (items) {
		var jobs = [];

		items.forEach(function (item) {
			each(item.querySelectorAll('img'), function (img) {
				if (img.complete && img.naturalWidth) {
					if (img.decode) {
						jobs.push(img.decode().catch(function () {}));
					}
					return;
				}
				jobs.push(
					new Promise(function (resolve) {
						img.addEventListener('load', resolve, { once: true });
						img.addEventListener('error', resolve, { once: true });
					})
				);
			});
		});

		return Promise.all(jobs);
	};

	Masonry.prototype.layout = function () {
		if (!this.grid || !this.items.length) {
			this.grid.style.height = '';
			return;
		}

		var width = this.grid.clientWidth;
		if (!width) {
			return;
		}

		var columns = Math.max(1, this.columns());
		var gap = this.gap;
		var columnWidth = (width - gap * (columns - 1)) / columns;
		if (columnWidth <= 0) {
			return;
		}

		var heights = [];
		var i;
		for (i = 0; i < columns; i++) {
			heights.push(0);
		}

		this.items.forEach(function (item) {
			item.style.position = 'absolute';
			item.style.top = '0';
			item.style.left = '0';
			item.style.width = columnWidth + 'px';

			var height = item.getBoundingClientRect().height;

			var column = 0;
			for (var c = 1; c < columns; c++) {
				if (heights[c] < heights[column] - 0.5) {
					column = c;
				}
			}

			item.style.transform =
				'translate3d(' + Math.round(column * (columnWidth + gap)) + 'px,' + Math.round(heights[column]) + 'px,0)';

			heights[column] += height + gap;
		});

		var tallest = Math.max.apply(null, heights) - gap;
		this.grid.style.height = Math.max(0, tallest) + 'px';
	};

	Masonry.prototype.add = function (items) {
		if (!items.length) {
			return;
		}
		this.items = this.items.concat(items);
		this.watch(items);
		this.layout();

		var self = this;
		this.ready(items).then(function () {
			self.layout();
		});
	};

	Masonry.prototype.reset = function () {
		this.items = [];
		this.grid.style.height = '';
	};

	/* -------------------------------------------------------------- quick view */

	function QuickView(works) {
		this.works = works;
		this.dialog = works.root.querySelector('[data-wcw-dialog]');
		if (!this.dialog) {
			return;
		}

		this.body = this.dialog.querySelector('[data-wcw-body]');
		this.prev = this.dialog.querySelector('[data-wcw-prev]');
		this.next = this.dialog.querySelector('[data-wcw-next]');
		this.cache = {};
		this.currentId = null;
		this.opener = null;
		this.token = 0;

		each(this.dialog.querySelectorAll('[data-wcw-close]'), function (btn) {
			btn.addEventListener('click', this.close.bind(this));
		}, this);

		this.dialog.addEventListener('click', function (event) {
			if (event.target === this.dialog) {
				this.close();
			}
		}.bind(this));

		this.dialog.addEventListener('close', function () {
			document.documentElement.classList.remove('wcw-qv-open');
			if (this.opener && this.opener.focus) {
				this.opener.focus();
			}
		}.bind(this));

		if (this.prev) {
			this.prev.addEventListener('click', this.move.bind(this, -1));
		}
		if (this.next) {
			this.next.addEventListener('click', this.move.bind(this, 1));
		}

		this.dialog.addEventListener('keydown', function (event) {
			if (event.target && /^(INPUT|TEXTAREA|SELECT)$/.test(event.target.tagName)) {
				return;
			}
			if (event.key === 'ArrowLeft') {
				event.preventDefault();
				this.move(-1);
			} else if (event.key === 'ArrowRight') {
				event.preventDefault();
				this.move(1);
			}
		}.bind(this));

		this.works.root.addEventListener('click', function (event) {
			var button = event.target.closest ? event.target.closest('.wcw-quick-view') : null;
			if (!button) {
				return;
			}
			if (!this.supported()) {
				return;
			}
			event.preventDefault();
			this.open(button);
		}.bind(this));

		each(this.works.root.querySelectorAll('.wcw-quick-view'), function (link) {
			link.setAttribute('aria-haspopup', 'dialog');
		});
	}

	QuickView.prototype.supported = function () {
		return this.dialog && typeof this.dialog.showModal === 'function';
	};

	QuickView.prototype.ids = function () {
		return Array.prototype.map.call(this.works.grid.querySelectorAll('.wcw-item'), function (item) {
			return item.getAttribute('data-product-id');
		});
	};

	QuickView.prototype.open = function (button) {
		var item = button.closest('.wcw-item');
		var id = button.getAttribute('data-product-id') || (item && item.getAttribute('data-product-id'));

		if (!this.supported()) {
			if (item && item.getAttribute('data-url')) {
				window.location.href = item.getAttribute('data-url');
			}
			return;
		}

		if (!id) {
			return;
		}

		this.opener = button;
		this.dialog.showModal();
		document.documentElement.classList.add('wcw-qv-open');
		this.load(id);
	};

	QuickView.prototype.close = function () {
		if (this.dialog && this.dialog.open) {
			this.dialog.close();
		}
	};

	QuickView.prototype.load = function (id) {
		var self = this;
		var token = ++this.token;

		this.currentId = id;

		if (this.cache[id]) {
			this.render(this.cache[id]);
			return;
		}

		this.body.innerHTML = '<p class="wcw-qv-loading">' + (i18n.loading || 'Loading…') + '</p>';
		this.setBusy(true);

		if (!data.rest) {
			this.body.innerHTML = '<p class="wcw-qv-error">' + (i18n.error || '') + '</p>';
			this.setBusy(false);
			return;
		}

		window
			.fetch(data.rest + 'product/' + encodeURIComponent(id), {
				credentials: 'same-origin',
				headers: { Accept: 'application/json' }
			})
			.then(function (response) {
				return response.json().then(function (json) {
					if (!response.ok || json.code) {
						throw new Error(json.message || 'Request failed');
					}
					return json;
				});
			})
			.then(function (json) {
				self.cache[id] = json;
				if (token === self.token) {
					self.render(json);
				}
			})
			.catch(function () {
				if (token === self.token) {
					self.body.innerHTML = '<p class="wcw-qv-error">' + (i18n.error || '') + '</p>';
				}
			})
			.then(function () {
				if (token === self.token) {
					self.setBusy(false);
				}
			});
	};

	QuickView.prototype.setBusy = function (busy) {
		if (this.prev) {
			this.prev.disabled = busy;
		}
		if (this.next) {
			this.next.disabled = busy;
		}
	};

	QuickView.prototype.render = function (json) {
		this.body.innerHTML = json.html;
		this.body.scrollTop = 0;
		this.dialog.setAttribute('aria-label', json.title || 'Quick view');
		this.bindThumbs();
		this.bindCart();
		this.updateNav();
	};

	QuickView.prototype.bindThumbs = function () {
		var stage = this.body.querySelector('[data-wcw-stage-img]');
		var thumbs = this.body.querySelectorAll('.wcw-qv-thumb');
		if (!stage || !thumbs.length) {
			return;
		}

		each(thumbs, function (thumb) {
			thumb.addEventListener('click', function () {
				stage.src = thumb.getAttribute('data-src');
				var srcset = thumb.getAttribute('data-srcset');
				if (srcset) {
					stage.srcset = srcset;
				} else {
					stage.removeAttribute('srcset');
				}
				var alt = thumb.getAttribute('data-alt');
				if (alt) {
					stage.alt = alt;
				}
				each(thumbs, function (other) {
					other.classList.remove('is-active');
					other.setAttribute('aria-pressed', 'false');
				});
				thumb.classList.add('is-active');
				thumb.setAttribute('aria-pressed', 'true');
			});
		});
	};

	QuickView.prototype.bindCart = function () {
		var form = this.body.querySelector('[data-wcw-atc]');
		if (!form || !data.wcAjax) {
			return;
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();

			var button = form.querySelector('[type="submit"]');
			var note = form.querySelector('.wcw-atc-note');
			var id = form.getAttribute('data-product-id');
			var original = button ? button.textContent : '';

			if (button) {
				button.disabled = true;
				button.textContent = i18n.adding || 'Adding…';
			}
			if (note) {
				note.textContent = '';
			}

			var payload = new FormData(form);
			if (id) {
				payload.append('product_id', id);
			}

			window
				.fetch(data.wcAjax, {
					method: 'POST',
					credentials: 'same-origin',
					body: payload
				})
				.then(function (response) {
					return response.json();
				})
				.then(function (json) {
					if (!json || json.error) {
						throw new Error('add to cart failed');
					}
					applyFragments(json.fragments);
					if (window.jQuery) {
						window.jQuery(document.body).trigger('added_to_cart', [
							json.fragments,
							json.cart_hash,
							window.jQuery(button)
						]);
					} else {
						document.body.dispatchEvent(
							new CustomEvent('wcw:added_to_cart', { detail: json })
						);
					}
					if (note) {
						note.textContent = i18n.added || 'Added to cart';
					}
				})
				.catch(function () {
					// Let WooCommerce handle it the plain way (redirects to the cart).
					form.submit();
				})
				.then(function () {
					if (button) {
						button.disabled = false;
						button.textContent = note && note.textContent ? (i18n.added || 'Added to cart') : original;
					}
				});
		});
	};

	QuickView.prototype.updateNav = function () {
		var ids = this.ids();
		var index = ids.indexOf(this.currentId);

		if (this.prev) {
			this.prev.disabled = index <= 0;
		}
		if (this.next) {
			this.next.disabled = index === -1 || index >= ids.length - 1;
		}
	};

	QuickView.prototype.move = function (delta) {
		var ids = this.ids();
		var index = ids.indexOf(this.currentId);
		if (index === -1) {
			return;
		}
		var target = ids[index + delta];
		if (!target) {
			return;
		}
		this.load(target);
	};

	/* --------------------------------------------------------------- fragments */

	function applyFragments(fragments) {
		if (!fragments) {
			return;
		}
		Object.keys(fragments).forEach(function (selector) {
			var nodes;
			try {
				nodes = document.querySelectorAll(selector);
			} catch (err) {
				return;
			}
			each(nodes, function (node) {
				node.innerHTML = fragments[selector];
			});
		});
	}

	/* ------------------------------------------------------------------- works */

	function Works(root) {
		this.root = root;
		this.grid = root.querySelector('[data-wcw-grid]');
		this.count = root.querySelector('[data-wcw-count]');
		this.empty = root.querySelector('[data-wcw-empty]');
		this.moreBtn = root.querySelector('[data-wcw-more]');
		this.facets = root.querySelector('.wcw-facets');
		this.base = root.getAttribute('data-base') || window.location.href;
		this.filters = this.readFilters();
		this.chipsEnabled = root.getAttribute('data-chips') !== '0';
		this.focusAfter = null;
		this.page = parseInt(root.getAttribute('data-page'), 10) || 1;
		this.perPage = parseInt(root.getAttribute('data-per-page'), 10) || 24;
		this.loading = false;
		this.request = null;
		this.requestToken = 0;
		this.errorEl = null;

		if (!this.grid) {
			return;
		}

		root.classList.add('wcw--js');

		this.masonry = new Masonry(root, this.grid, parseInt(root.getAttribute('data-gap'), 10) || 24);
		this.quickView = new QuickView(this);

		this.setHasMore(root.getAttribute('data-more') === '1');

		if (this.moreBtn) {
			this.moreBtn.addEventListener('click', this.loadMore.bind(this));
		}

		this.root.addEventListener('click', this.onChipClick.bind(this));

		window.addEventListener('popstate', this.onPopState.bind(this));
	}

	Works.prototype.readFilters = function () {
		var filters = { cat: '', medium: '', year: '' };
		var raw = this.root.getAttribute('data-filters');

		if (raw) {
			try {
				var parsed = JSON.parse(raw);
				if (parsed && typeof parsed === 'object') {
					filters.cat = parsed.cat || '';
					filters.medium = parsed.medium || '';
					filters.year = parsed.year || '';
				}
			} catch (err) {
				// Fall through to the legacy attribute.
			}
		}

		if (!filters.cat) {
			filters.cat = this.root.getAttribute('data-cat') || '';
		}

		return filters;
	};

	Works.prototype.setHasMore = function (more) {
		if (this.moreBtn) {
			this.moreBtn.hidden = !more;
		}
	};

	Works.prototype.setLoading = function (loading) {
		this.loading = loading;
		this.root.setAttribute('aria-busy', loading ? 'true' : 'false');
		this.grid.classList.toggle('is-loading', loading);
		if (this.moreBtn) {
			this.moreBtn.disabled = loading;
			if (loading) {
				this.moreBtn.textContent = i18n.loading || 'Loading…';
			} else {
				this.moreBtn.textContent = i18n.loadMore || 'Load more';
			}
		}
	};

	Works.prototype.urlFor = function () {
		var url;
		try {
			url = new URL(this.base, window.location.href);
		} catch (err) {
			return this.base;
		}

		var map = { cat: 'wcw_cat', medium: 'wcw_medium', year: 'wcw_year' };
		Object.keys(map).forEach(function (slot) {
			if (this.filters[slot]) {
				url.searchParams.set(map[slot], this.filters[slot]);
			} else {
				url.searchParams.delete(map[slot]);
			}
		}, this);

		url.searchParams.delete('wcw_page');
		return url.pathname + url.search + url.hash;
	};

	Works.prototype.onChipClick = function (event) {
		if (event.metaKey || event.ctrlKey || event.shiftKey || event.button === 1) {
			return;
		}

		var chip = event.target.closest ? event.target.closest('.wcw-chip') : null;
		if (!chip || !this.root.contains(chip)) {
			return;
		}

		var facet = chip.getAttribute('data-facet') || 'cat';
		var value = chip.getAttribute('data-value') || '';

		event.preventDefault();

		if (value === (this.filters[facet] || '')) {
			return;
		}

		this.filters[facet] = value;
		this.focusAfter = { facet: facet, value: value };
		this.applyFilters(true);
	};

	Works.prototype.applyFilters = function (push) {
		this.page = 1;
		this.masonry.reset();
		this.grid.innerHTML = '';

		if (false !== push && window.history && window.history.pushState) {
			window.history.pushState({ wcwFilters: this.filters }, '', this.urlFor());
		}

		this.fetch(1, true);
	};

	Works.prototype.onPopState = function () {
		var params = new URLSearchParams(window.location.search);
		var next = {
			cat: params.get('wcw_cat') || '',
			medium: params.get('wcw_medium') || '',
			year: params.get('wcw_year') || ''
		};

		if (
			next.cat === (this.filters.cat || '') &&
			next.medium === (this.filters.medium || '') &&
			next.year === (this.filters.year || '')
		) {
			return;
		}

		this.filters = next;
		this.applyFilters(false);
	};

	Works.prototype.loadMore = function () {
		if (this.loading) {
			return;
		}
		this.fetch(this.page + 1, false);
	};

	Works.prototype.endpoint = function (page) {
		var url =
			data.rest +
			'works?page=' +
			encodeURIComponent(page) +
			'&per_page=' +
			encodeURIComponent(this.perPage) +
			'&chips=' +
			(this.chipsEnabled ? '1' : '0') +
			'&base=' +
			encodeURIComponent(this.base);

		var map = { cat: 'wcw_cat', medium: 'wcw_medium', year: 'wcw_year' };
		Object.keys(map).forEach(function (slot) {
			if (this.filters[slot]) {
				url += '&' + slot + '=' + encodeURIComponent(this.filters[slot]);
			}
		}, this);

		return url;
	};

	Works.prototype.fetch = function (page, replace) {
		var self = this;

		if (!data.rest) {
			return;
		}

		var token = ++this.requestToken;

		this.setLoading(true);
		this.clearError();

		if (this.request) {
			this.request.abort();
		}
		this.request = window.AbortController ? new AbortController() : null;

		window
			.fetch(this.endpoint(page), {
				credentials: 'same-origin',
				headers: { Accept: 'application/json' },
				signal: this.request ? this.request.signal : undefined
			})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('HTTP ' + response.status);
				}
				return response.json();
			})
			.then(function (json) {
				if (token === self.requestToken) {
					self.render(json, replace);
				}
			})
			.catch(function (err) {
				if (err && err.name === 'AbortError') {
					return;
				}
				if (token === self.requestToken) {
					self.showError();
				}
			})
			.then(function () {
				if (token === self.requestToken) {
					self.setLoading(false);
				}
			});
	};

	Works.prototype.render = function (json, replace) {
		var holder = document.createElement('div');
		holder.innerHTML = json.html;

		var items = Array.prototype.filter.call(holder.children, function (el) {
			return el.classList && el.classList.contains('wcw-item');
		});

		if (replace) {
			this.grid.innerHTML = '';
			this.masonry.reset();
		}

		items.forEach(function (item) {
			item.classList.add('wcw-item--new');
			var link = item.querySelector('.wcw-quick-view');
			if (link) {
				link.setAttribute('aria-haspopup', 'dialog');
			}
			this.grid.appendChild(item);
		}, this);

		this.masonry.add(items);
		this.page = parseInt(json.page, 10) || 1;
		this.setHasMore(!!json.more);

		if (this.count && json.count_text) {
			this.count.textContent = json.count_text;
		}

		this.renderFacets(json);

		if (this.empty) {
			var isEmpty = !this.grid.querySelector('.wcw-item');
			this.empty.hidden = !isEmpty;
			if (isEmpty) {
				var p = this.empty.querySelector('p');
				if (p) {
					p.textContent = i18n.empty || p.textContent;
				}
			}
		}
	};

	Works.prototype.renderFacets = function (json) {
		if (json.filters) {
			this.filters = {
				cat: json.filters.cat || '',
				medium: json.filters.medium || '',
				year: json.filters.year || ''
			};
			this.root.setAttribute('data-filters', JSON.stringify(this.filters));
		}

		if (typeof json.facets_html === 'undefined') {
			return;
		}

		var holder = document.createElement('div');
		holder.innerHTML = json.facets_html || '';
		var replacement = holder.firstElementChild;

		if (this.facets && replacement) {
			this.facets.replaceWith(replacement);
		} else if (this.facets && !replacement) {
			this.facets.remove();
		}

		this.facets = this.root.querySelector('.wcw-facets');

		if (this.focusAfter && this.facets) {
			var value = String(this.focusAfter.value).replace(/["\\]/g, '');
			var chip = this.facets.querySelector(
				'.wcw-chip[data-facet="' + this.focusAfter.facet + '"][data-value="' + value + '"]'
			);
			if (chip) {
				chip.focus();
			}
		}

		this.focusAfter = null;
	};

	Works.prototype.showError = function () {
		if (!this.errorEl) {
			this.errorEl = document.createElement('p');
			this.errorEl.className = 'wcw-error';
			this.errorEl.setAttribute('role', 'alert');
			if (this.moreBtn && this.moreBtn.parentNode) {
				this.moreBtn.parentNode.insertBefore(this.errorEl, this.moreBtn);
			} else {
				this.root.appendChild(this.errorEl);
			}
		}
		this.errorEl.textContent = i18n.error || 'Something went wrong. Please try again.';
	};

	Works.prototype.clearError = function () {
		if (this.errorEl) {
			this.errorEl.textContent = '';
		}
	};

	/* -------------------------------------------------------------------- boot */

	ready(function () {
		each(document.querySelectorAll('[data-wcw]'), function (root) {
			new Works(root);
		});
	});
})();
