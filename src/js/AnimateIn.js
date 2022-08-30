export const AnimateIn = (function () {
	'use strict';

	let $win, $winH, $animateIn, threshold, distScrolled;

	function init() {
		$win = $(window);
		$animateIn = $('.animate-in');
		distScrolled = 0;

		if ($animateIn.length) {
			resize();
			checkElements();
			$win.on('scroll', checkElements);
			$win.on('resize', resize);
		}
	}

	function resize() {
		$winH = $(window).height();
		threshold = $winH * 0.15; // this controls when the animation occurs - distance from bottom of window
	}

	function checkElements() {
		distScrolled = $win.scrollTop();

		$animateIn.each(function (i, el) {
			let $el = $(el);
			let elTreshold = $el.data('threshold') ? $(window).height() * ($el.data('threshold') / 100) : threshold;
			let elDist = $el.offset().top - ($winH - elTreshold);

			if (distScrolled > elDist) {
				$el.addClass('animated');
				$animateIn = $('.animate-in:not(.animated)');
			}
		});
	}

	return {
		init: init,
	};
})();
