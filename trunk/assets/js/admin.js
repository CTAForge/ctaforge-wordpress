/**
 * CTAForge — Admin Settings JS
 * Handles the "Test Connection" button.
 */
( function ( $ ) {
	'use strict';

	$( document ).on( 'click', '.ctaforge-test-connection', function () {
		const $btn    = $( this );
		const $status = $( '.ctaforge-connection-status' );
		const apiKey  = $( '#ctaforge_api_key' ).val();

		$btn.prop( 'disabled', true ).text( ctaforgeAdmin.i18n.testing );
		$status.text( '' );

		$.post( ctaforgeAdmin.ajaxUrl, {
			action:         'ctaforge_test_connection',
			_wpnonce:       ctaforgeAdmin.nonce,
			api_key:        apiKey,
		} )
		.done( function ( response ) {
			if ( response.success ) {
				$status.css( 'color', 'green' ).text( ctaforgeAdmin.i18n.connected );
			} else {
				const msg = response.data && response.data.message
					? ctaforgeAdmin.i18n.error + ' — ' + response.data.message
					: ctaforgeAdmin.i18n.error;
				$status.css( 'color', 'red' ).text( msg );
			}
		} )
		.fail( function () {
			$status.css( 'color', 'red' ).text( ctaforgeAdmin.i18n.error );
		} )
		.always( function () {
			$btn.prop( 'disabled', false ).text( 'Test Connection' );
		} );
	} );

} )( jQuery );
