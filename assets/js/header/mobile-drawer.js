/**
 * Mobile drawer: open/close, backdrop click and Escape.
 *
 * Dispatches `tg:drawer-close` on the drawer element when it closes.
 */

(function () {
	'use strict';

	function init() {
		var drawer = document.getElementById('tg-mobile-drawer');
		if (!drawer) {
			return;
		}

		var openButtons = document.querySelectorAll('[data-drawer-open]');
		var closeButtons = drawer.querySelectorAll('[data-drawer-close]');
		var lastFocused = null;

		function setExpanded(value) {
			openButtons.forEach(function (button) {
				button.setAttribute('aria-expanded', value ? 'true' : 'false');
			});
		}

		function open() {
			lastFocused = document.activeElement;
			drawer.hidden = false;
			document.body.classList.add('has-drawer-open');
			setExpanded(true);

			var close = drawer.querySelector('.mobile-drawer__close');
			if (close) {
				close.focus();
			}
		}

		function close() {
			if (drawer.hidden) {
				return;
			}

			drawer.hidden = true;
			document.body.classList.remove('has-drawer-open');
			setExpanded(false);
			drawer.dispatchEvent(new CustomEvent('tg:drawer-close'));

			if (lastFocused && typeof lastFocused.focus === 'function') {
				lastFocused.focus();
			}
		}

		openButtons.forEach(function (button) {
			button.addEventListener('click', open);
		});

		closeButtons.forEach(function (button) {
			button.addEventListener('click', close);
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				close();
			}
		});

		window.matchMedia('(min-width: 64rem)').addEventListener('change', function (event) {
			if (event.matches) {
				close();
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
