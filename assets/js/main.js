/**
 * Main JavaScript file for Tante Groen.
 */

(function () {
	'use strict';

	/**
	 * Mobile drawer: open/close, backdrop click and Escape.
	 */
	function initDrawer() {
		var drawer = document.getElementById('tg-mobile-drawer');
		if (!drawer) {
			return;
		}

		var openButtons = document.querySelectorAll('[data-drawer-open]');
		var closeButtons = drawer.querySelectorAll('[data-drawer-close]');
		var resetLevels = initDrawerLevels(drawer);
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
			resetLevels();

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

	/**
	 * Drawer drill-down levels: toggles open a level over the panel (or over
	 * the parent level), back buttons close it again.
	 *
	 * @param {HTMLElement} drawer Drawer element.
	 * @return {Function} Closes all open levels.
	 */
	function initDrawerLevels(drawer) {
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

		return function () {
			drawer.querySelectorAll('.drawer-level:not([hidden])').forEach(function (level) {
				if (!level.hidden) {
					closeLevel(level, false);
				}
			});
		};
	}

	/**
	 * Desktop search panel toggle.
	 */
	function initSearchToggle() {
		document.querySelectorAll('[data-search-toggle]').forEach(function (button) {
			var panel = document.getElementById(button.getAttribute('aria-controls'));
			if (!panel) {
				return;
			}

			button.addEventListener('click', function () {
				var isOpen = !panel.hidden;

				panel.hidden = isOpen;
				button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');

				if (!isOpen) {
					var input = panel.querySelector('input[type="search"]');
					if (input) {
						input.focus();
					}
				}
			});
		});
	}

	/**
	 * Desktop mega menus: hover intent, tab switching and keyboard support.
	 */
	function initMegaMenu() {
		var desktop = window.matchMedia('(min-width: 64rem)');
		var openDelay = 100;
		var closeDelay = 200;
		var tabDelay = 75;
		var current = null;
		var lastPointerType = 'mouse';

		var menus = Array.prototype.map.call(document.querySelectorAll('.main-nav__item--has-mega'), function (item) {
			var link = item.querySelector('.main-nav__link');
			var panel = link ? document.getElementById(link.getAttribute('aria-controls')) : null;

			return panel ? { item: item, link: link, panel: panel, timer: null } : null;
		}).filter(Boolean);

		if (!menus.length) {
			return;
		}

		function open(menu) {
			clearTimeout(menu.timer);

			if (current && current !== menu) {
				close(current);
			}

			menu.panel.hidden = false;
			menu.item.classList.add('main-nav__item--open');
			menu.link.setAttribute('aria-expanded', 'true');
			current = menu;
		}

		function close(menu) {
			clearTimeout(menu.timer);

			menu.panel.hidden = true;
			menu.item.classList.remove('main-nav__item--open');
			menu.link.setAttribute('aria-expanded', 'false');

			if (current === menu) {
				current = null;
			}
		}

		function schedule(menu, callback, delay) {
			clearTimeout(menu.timer);
			menu.timer = setTimeout(function () {
				callback(menu);
			}, delay);
		}

		function getTabs(menu) {
			return Array.prototype.slice.call(menu.panel.querySelectorAll('[data-mega-menu-tab]'));
		}

		function activateTab(menu, tab) {
			getTabs(menu).forEach(function (other) {
				var selected = other === tab;
				var target = document.getElementById(other.getAttribute('aria-controls'));

				other.setAttribute('aria-selected', selected ? 'true' : 'false');
				other.tabIndex = selected ? 0 : -1;

				if (target) {
					target.hidden = !selected;
				}
			});
		}

		function getActiveTab(menu) {
			return menu.panel.querySelector('[data-mega-menu-tab][aria-selected="true"]');
		}

		menus.forEach(function (menu) {
			var tabTimer = null;
			var wasOpen = false;

			menu.item.addEventListener('mouseenter', function () {
				if (desktop.matches) {
					schedule(menu, open, current ? 0 : openDelay);
				}
			});

			menu.item.addEventListener('mouseleave', function () {
				if (desktop.matches) {
					schedule(menu, close, closeDelay);
				}
			});

			menu.link.addEventListener('pointerdown', function (event) {
				lastPointerType = event.pointerType;
				wasOpen = current === menu;
			});

			menu.link.addEventListener('click', function (event) {
				if (desktop.matches && 'mouse' !== lastPointerType && !wasOpen) {
					event.preventDefault();
					open(menu);
				}

				lastPointerType = 'mouse';
			});

			menu.link.addEventListener('keydown', function (event) {
				if ('ArrowDown' !== event.key || !desktop.matches) {
					return;
				}

				event.preventDefault();
				open(menu);

				var tab = getActiveTab(menu);
				if (tab) {
					tab.focus();
				}
			});

			menu.item.addEventListener('focusin', function () {
				if (desktop.matches) {
					open(menu);
				}
			});

			menu.item.addEventListener('focusout', function (event) {
				if (!menu.item.contains(event.relatedTarget)) {
					close(menu);
				}
			});

			menu.item.addEventListener('keydown', function (event) {
				if ('Escape' === event.key && current === menu) {
					close(menu);
					menu.link.focus();
				}
			});

			getTabs(menu).forEach(function (tab) {
				tab.addEventListener('mouseenter', function () {
					clearTimeout(tabTimer);
					tabTimer = setTimeout(function () {
						activateTab(menu, tab);
					}, tabDelay);
				});

				tab.addEventListener('mouseleave', function () {
					clearTimeout(tabTimer);
				});

				tab.addEventListener('focus', function () {
					activateTab(menu, tab);
				});

				tab.addEventListener('click', function () {
					activateTab(menu, tab);
				});

				tab.addEventListener('keydown', function (event) {
					var tabs = getTabs(menu);
					var index = tabs.indexOf(tab);
					var next = null;

					if ('ArrowDown' === event.key) {
						next = tabs[(index + 1) % tabs.length];
					} else if ('ArrowUp' === event.key) {
						next = tabs[(index - 1 + tabs.length) % tabs.length];
					} else if ('Home' === event.key) {
						next = tabs[0];
					} else if ('End' === event.key) {
						next = tabs[tabs.length - 1];
					}

					if (next) {
						event.preventDefault();
						next.focus();
					}
				});
			});
		});

		document.addEventListener('click', function (event) {
			if (current && !current.item.contains(event.target)) {
				close(current);
			}
		});

		desktop.addEventListener('change', function (event) {
			if (!event.matches && current) {
				close(current);
			}
		});
	}

	function init() {
		initDrawer();
		initSearchToggle();
		initMegaMenu();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
