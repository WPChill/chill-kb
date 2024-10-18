(function () {
	// Configuration
	const scrollDepths  = [25, 50, 75, 100];
	const debounceDelay = 250; // milliseconds

	// State
	let lastTrackedDepth = 0;
	let ticking          = false;
	let lastPagePath     = '';

	// Debounce function
	function debounce(func, wait) {
		let timeout;
		return function executedFunction(...args) {
			const later = () => {
				clearTimeout( timeout );
				func( ...args );
			};
			clearTimeout( timeout );
			timeout = setTimeout( later, wait );
		};
	}

	// Calculate scroll depth
	function getScrollDepth() {
		const windowHeight     = window.innerHeight;
		const documentHeight   = document.documentElement.scrollHeight;
		const scrollTop        = window.pageYOffset || document.documentElement.scrollTop;
		const trackLength      = documentHeight - windowHeight;
		const scrollPercentage = Math.floor( (scrollTop / trackLength) * 100 );
		return Math.min( scrollPercentage, 100 );
	}

	// Track scroll depth
	function trackScrollDepth() {
		if ( ! ticking) {
			window.requestAnimationFrame(
				() => {
					const currentDepth    = getScrollDepth();
					const currentPagePath = window.location.pathname;
					// Reset tracking if page changed
					if (currentPagePath !== lastPagePath) {
						lastTrackedDepth = 0;
						lastPagePath     = currentPagePath;
					}

					for (let depth of scrollDepths) {
						if (currentDepth >= depth && lastTrackedDepth < depth) {
							if (typeof umami !== 'undefined') {
								umami.track(
									'Scroll Depth',
									{
										depth: depth + '%',
										page: currentPagePath
									}
								);
							}
							lastTrackedDepth = depth;
							break;
						}
					}
					ticking = false;
				}
			);
			ticking = true;
		}
	}

	// Debounced scroll handler
	const debouncedScrollHandler = debounce( trackScrollDepth, debounceDelay );

	// Initialize scroll depth tracking
	function initScrollDepthTracking() {
		window.addEventListener( 'scroll', debouncedScrollHandler, { passive: true } );
		// Track initial page load
		trackScrollDepth();
	}

	// Re-initialize on page changes (for SPAs)
	function handlePageChange() {
		lastTrackedDepth = 0;
		lastPagePath     = window.location.pathname;
		trackScrollDepth();
	}

	// Check for page changes
	function checkForPageChanges() {
		const currentPath = window.location.pathname;
		if (currentPath !== lastPagePath) {
			handlePageChange();
		}
	}

	// Initialize
	if (document.readyState === 'complete' || document.readyState === 'interactive') {
		initScrollDepthTracking();
	} else {
		document.addEventListener( 'DOMContentLoaded', initScrollDepthTracking );
	}

	// Check for page changes periodically (for SPAs)
	setInterval( checkForPageChanges, 1000 );
})();