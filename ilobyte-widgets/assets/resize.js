/**
 * Auto-resize for IloByte widget iframes. The embedded page reports its height
 * via postMessage ({type:'ilobyte:resize', height}); this listener applies it
 * to whichever of our iframes sent the message.
 */
( function () {
	'use strict';
	window.addEventListener( 'message', function ( ev ) {
		if ( ! ev || ! ev.data || ev.data.type !== 'ilobyte:resize' ) {
			return;
		}
		var frames = document.querySelectorAll( 'iframe.ilobyte-widget-frame' );
		for ( var i = 0; i < frames.length; i++ ) {
			if ( frames[ i ].contentWindow === ev.source ) {
				var h = parseInt( ev.data.height, 10 );
				if ( h && h > 0 ) {
					frames[ i ].style.height = Math.max( 240, h ) + 'px';
				}
			}
		}
	} );
} )();
