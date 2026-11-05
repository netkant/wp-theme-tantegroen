/**
 * Desktop search panel toggle.
 */

(function () {
	'use strict';

	function init() {
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

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
