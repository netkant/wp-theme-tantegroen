/**
 * Mobile drawer drill-down levels (mega menus): toggles open a level over the
 * panel (or over the parent level), back buttons close it again.
 *
 * All levels close when the drawer dispatches `tg:drawer-close`.
 */

(function () {
	'use strict';

	function init() {
		var drawer = document.getElementById('tg-mobile-drawer');
		if (!drawer) {
			return;
		}

		var panel = drawer.querySelector('.mobile-drawer__panel');

		function getParent(level) {
			return level.parentElement.closest('.drawer-level') || panel;
		}

		function setCovered(parent, covered) {
			if (!parent) {
				return;
			}

			if (covered) {
				parent.scrollTop = 0;
			}

			parent.classList.toggle(parent === panel ? 'mobile-drawer__panel--covered' : 'drawer-level--covered', covered);
		}

		function getToggle(level) {
			return drawer.querySelector('[data-drawer-level-open][aria-controls="' + level.id + '"]');
		}

		function openLevel(toggle) {
			var level = document.getElementById(toggle.getAttribute('aria-controls'));
			if (!level) {
				return;
			}

			setCovered(getParent(level), true);
			level.hidden = false;
			toggle.setAttribute('aria-expanded', 'true');

			var back = level.querySelector('[data-drawer-level-back]');
			if (back) {
				back.focus();
			}
		}

		function closeLevel(level, restoreFocus) {
			level.querySelectorAll('.drawer-level:not([hidden])').forEach(function (child) {
				closeLevel(child, false);
			});

			level.hidden = true;
			level.classList.remove('drawer-level--covered');
			setCovered(getParent(level), false);

			var toggle = getToggle(level);
			if (toggle) {
				toggle.setAttribute('aria-expanded', 'false');

				if (restoreFocus) {
					toggle.focus();
				}
			}
		}

		drawer.addEventListener('click', function (event) {
			var toggle = event.target.closest('[data-drawer-level-open]');
			if (toggle) {
				openLevel(toggle);
				return;
			}

			var back = event.target.closest('[data-drawer-level-back]');
			if (back) {
				closeLevel(back.closest('.drawer-level'), true);
			}
		});

		drawer.addEventListener('tg:drawer-close', function () {
			drawer.querySelectorAll('.drawer-level:not([hidden])').forEach(function (level) {
				if (!level.hidden) {
					closeLevel(level, false);
				}
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
