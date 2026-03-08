/**
 * CTAForge — Admin Settings JS
 *
 * Security note on Test Connection:
 * The API key is NOT sent from the browser to the AJAX handler.
 * The server reads the saved key from wp_options directly.
 * Only the nonce (CSRF token) is sent — it proves the request
 * came from a legitimate admin session.
 */
( function ( $ ) {
	'use strict';

	$( document ).on( 'click', '.ctaforge-test-connection', function () {
		const $btn    = $( this );
		const $status = $( '.ctaforge-connection-status' );

		// Warn user if they have unsaved changes in the key field.
		const $keyField  = $( '#ctaforge_api_key' );
		const isDirty    = $keyField.data( 'original' ) !== $keyField.val();
		if ( isDirty ) {
			$status
				.css( 'color', '#b45309' )
				.text( ctaforgeAdmin.i18n.saveFirst );
			return;
		}

		$btn.prop( 'disabled', true ).text( ctaforgeAdmin.i18n.testing );
		$status.text( '' ).css( 'color', '' );

		// Only send the nonce — the server reads the API key from wp_options.
		$.post( ctaforgeAdmin.ajaxUrl, {
			action:   'ctaforge_test_connection',
			_wpnonce: ctaforgeAdmin.nonce,
		} )
		.done( function ( response ) {
			if ( response.success ) {
				const user = response.data && response.data.user
					? ' (' + response.data.user.email + ')'
					: '';
				$status
					.css( 'color', 'green' )
					.text( ctaforgeAdmin.i18n.connected + user );
			} else {
				const msg = response.data && response.data.message
					? response.data.message
					: ctaforgeAdmin.i18n.error;
				$status.css( 'color', '#b91c1c' ).text( '❌ ' + msg );
			}
		} )
		.fail( function () {
			$status.css( 'color', '#b91c1c' ).text( ctaforgeAdmin.i18n.error );
		} )
		.always( function () {
			$btn.prop( 'disabled', false ).text( ctaforgeAdmin.i18n.testBtn );
		} );
	} );

	// Track original value to detect unsaved changes.
	$( function () {
		const $key = $( '#ctaforge_api_key' );
		$key.data( 'original', $key.val() );
	} );

} )( jQuery );
