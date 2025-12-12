( function () {
	const settings = window.cahSettings || {};

	if ( ! settings.enabled ) {
		return;
	}

	const selectors = parseSelectors( settings.selectors );
	if ( selectors.length === 0 ) {
		return;
	}

	const maskRadius = Number( settings.maskRadius ) || 300;
	const shadowSize = Number( settings.shadowSize ) || 4;
	const colorsMode = settings.colorsMode === 'three' ? 'three' : 'two';
	const trackingMode = settings.trackingMode === 'global' ? 'global' : 'per_element';
	const pauseEventName = settings.pauseEvent || 'cah-pause';
	const resumeEventName = settings.resumeEvent || 'cah-resume';

const overlayMap = new Map();
let scanRunning = false;

// Build path from root element to a descendant (array of child indices)
function getPathToElement( root, target ) {
	const path = [];
	let current = target;
	while ( current && current !== root ) {
		const parent = current.parentNode;
		if ( ! parent ) break;
		const index = Array.from( parent.children ).indexOf( current );
		if ( index === -1 ) break;
		path.unshift( index );
		current = parent;
	}
	return current === root ? path : null;
}

// Get element at path from root
function getElementAtPath( root, path ) {
	let current = root;
	for ( const index of path ) {
		if ( ! current.children || ! current.children[ index ] ) return null;
		current = current.children[ index ];
	}
	return current;
}

// Sync style.transform from original element/child to clone counterpart
function syncDescendantStyle( originalRoot, clone, changedElement ) {
	if ( changedElement === originalRoot ) {
		// Top-level element changed
		clone.style.transform = changedElement.style.transform || '';
		return;
	}
	// Descendant changed - find corresponding element in clone
	const path = getPathToElement( originalRoot, changedElement );
	if ( path ) {
		const cloneTarget = getElementAtPath( clone, path );
		if ( cloneTarget ) {
			cloneTarget.style.transform = changedElement.style.transform || '';
		}
	}
}

// Create a MutationObserver for an element that watches all descendants
function createDeepStyleObserver( element, clone ) {
	if ( ! window.MutationObserver ) return null;
	
	const observer = new MutationObserver( ( mutations ) => {
		mutations.forEach( ( mutation ) => {
			if ( mutation.attributeName === 'style' ) {
				syncDescendantStyle( element, clone, mutation.target );
			}
		} );
	} );
	
	observer.observe( element, {
		attributes: true,
		attributeFilter: [ 'style' ],
		subtree: true, // Watch all descendants!
	} );
	
	return observer;
}

function setPausedState( data, paused ) {
	if ( data.paused === paused ) {
		return;
	}

	data.paused = paused;
	data.overlay.style.opacity = paused ? '0' : '';
	data.overlay.style.visibility = paused ? 'hidden' : '';
	clearMask( data.overlay );
}

	let mouseX = 0;
	let mouseY = 0;
	let rafScheduled = false;
	const resizeObserver = window.ResizeObserver
		? new ResizeObserver( ( entries ) => {
				entries.forEach( ( entry ) => {
					const data = overlayMap.get( entry.target );
					if ( data ) {
						clearMask( data.overlay );
					}
				} );
		  } )
		: null;

function parseSelectors( raw ) {
		if ( ! raw ) {
			return [];
		}

		return raw
			.split( /[\n,]+/ )
			.map( ( selector ) => selector.trim() )
			.filter( Boolean );
	}

	function buildFilterString() {
		const radius = Math.max( 0, shadowSize );
		if ( colorsMode === 'three' ) {
			return [
				`drop-shadow(${ radius }px 0 ${ radius }px ${ settings.redColor })`,
				`drop-shadow(${-radius}px 0 ${ radius }px ${ settings.greenColor })`,
				`drop-shadow(0 ${ radius }px ${ radius }px ${ settings.blueColor })`,
			].join( ' ' );
		}

		return [
			`drop-shadow(${ radius }px 0 ${ radius }px ${ settings.leftColor })`,
			`drop-shadow(${-radius}px 0 ${ radius }px ${ settings.rightColor })`,
		].join( ' ' );
	}

	function scheduleScan() {
		if ( scanRunning ) {
			return;
		}

		scanRunning = true;
		requestAnimationFrame( () => {
			scanRunning = false;
			scanTargets();
		} );
	}

	function scanTargets() {
		selectors.forEach( ( selector ) => {
			document.querySelectorAll( selector ).forEach( ( element ) => {
				if ( overlayMap.has( element ) ) {
					return;
				}

				if ( element.closest( '.cah-wrap' ) ) {
					return;
				}

				createOverlay( element );
			} );
		} );
	}

function createOverlay( element ) {
	const computed = window.getComputedStyle( element );
	const displayValue = computed.display || 'inline-block';
	const wrapperTag = displayValue.startsWith( 'inline' ) ? 'span' : 'div';
	const wrapper = document.createElement( wrapperTag );
	wrapper.classList.add( 'cah-wrap' );
	const normalizedDisplay = displayValue === 'inline' ? 'inline-block' : displayValue;
	wrapper.style.display = normalizedDisplay;
	wrapper.style.position = 'relative';

	const parent = element.parentNode;
	if ( ! parent ) {
		return;
	}

	parent.insertBefore( wrapper, element );
	wrapper.appendChild( element );

	const overlay = document.createElement( 'span' );
	overlay.classList.add( 'cah-overlay' );
	overlay.setAttribute( 'aria-hidden', 'true' );
	overlay.setAttribute( 'role', 'presentation' );
	overlay.setAttribute( 'data-nosnippet', 'true' );
	overlay.setAttribute( 'inert', 'true' );
	overlay.style.filter = buildFilterString();
	overlay.style.transition = 'opacity 0.18s ease';
	overlay.style.fontSize = 'inherit';
	overlay.style.fontFamily = 'inherit';
	overlay.style.lineHeight = 'inherit';

	const clone = element.cloneNode( true );
	clone.classList.add( 'cah-clone' );
	clearIds( clone );

	overlay.appendChild( clone );
	wrapper.appendChild( overlay );

	clearMask( overlay );

	const data = {
		wrapper,
		overlay,
		clone,
		paused: false,
		deepObserver: null,
		pauseHandler: null,
		resumeHandler: null,
	};

	overlay.__cahData = data;

	// Initial sync of all transforms (element + all descendants)
	syncAllDescendantStyles( element, clone );

	// Create deep observer for style changes on element and all descendants
	data.deepObserver = createDeepStyleObserver( element, clone );

	const pauseHandler = () => setPausedState( data, true );
	const resumeHandler = () => setPausedState( data, false );

	element.addEventListener( pauseEventName, pauseHandler );
	element.addEventListener( resumeEventName, resumeHandler );

	data.pauseHandler = pauseHandler;
	data.resumeHandler = resumeHandler;

	overlayMap.set( element, data );

	if ( resizeObserver ) {
		resizeObserver.observe( element );
	}

	if ( trackingMode === 'per_element' ) {
		setupPerElementEvents( wrapper, overlay );
	}
}

function clearIds( node ) {
	if ( node.removeAttribute ) {
		node.removeAttribute( 'id' );
	}

	if ( node.children && node.children.length ) {
		Array.from( node.children ).forEach( ( child ) => {
			clearIds( child );
		} );
	}
}

function setupPerElementEvents( wrapper, overlay ) {
	wrapper.addEventListener( 'mouseenter', ( event ) => {
		handleMaskForEvent( event, overlay );
	} );

	wrapper.addEventListener( 'mousemove', ( event ) => {
		handleMaskForEvent( event, overlay );
	} );

	wrapper.addEventListener( 'mouseleave', () => {
		clearMask( overlay );
	} );
}

	function handleMaskForEvent( event, overlay ) {
	const data = overlay.__cahData;
	if ( data && data.paused ) {
		return;
	}

		const rect = overlay.parentElement.getBoundingClientRect();
		const x = event.clientX - rect.left;
		const y = event.clientY - rect.top;

		applyMask( overlay, x, y );
	}

	function applyMask( overlay, x, y ) {
		const maskValue = `radial-gradient(circle ${ maskRadius }px at ${ x }px ${ y }px, #000, transparent)`;
		overlay.style.webkitMask = maskValue;
		overlay.style.mask = maskValue;
	}

	function clearMask( overlay ) {
		const maskValue = 'radial-gradient(circle 0px at 0px 0px, transparent, transparent)';
		overlay.style.webkitMask = maskValue;
		overlay.style.mask = maskValue;
	}

	function handleGlobalMouseMove( event ) {
		mouseX = event.clientX;
		mouseY = event.clientY;

		if ( rafScheduled ) {
			return;
		}

		rafScheduled = true;
		requestAnimationFrame( () => {
			rafScheduled = false;
			overlayMap.forEach( ( data ) => {
				if ( data.paused ) {
					return;
				}

				const rect = data.wrapper.getBoundingClientRect();
				const x = mouseX - rect.left;
				const y = mouseY - rect.top;

				if ( x < 0 || y < 0 || x > rect.width || y > rect.height ) {
					clearMask( data.overlay );
					return;
				}

				applyMask( data.overlay, x, y );
			} );
		} );
	}

// Sync transform for element and all its descendants to the clone
function syncAllDescendantStyles( element, clone ) {
	// Sync the element itself
	clone.style.transform = element.style.transform || '';
	
	// Sync all descendants
	const originals = element.querySelectorAll( '*' );
	const clones = clone.querySelectorAll( '*' );
	
	originals.forEach( ( orig, i ) => {
		if ( clones[ i ] && orig.style && orig.style.transform ) {
			clones[ i ].style.transform = orig.style.transform;
		}
	} );
}

	function initObservers() {
		if ( window.MutationObserver ) {
			const observer = new MutationObserver( scheduleScan );
			observer.observe( document.body, {
				childList: true,
				subtree: true,
			} );
		}
	}

	function init() {
		scanTargets();
		initObservers();

		if ( trackingMode === 'global' ) {
			window.addEventListener( 'mousemove', handleGlobalMouseMove );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();

