/**
 * Desktop mega menus: hover intent, tab switching and keyboard support.
 */

(function () {
	'use strict';

	function init() {
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

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
