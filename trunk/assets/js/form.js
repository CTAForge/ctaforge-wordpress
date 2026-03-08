/**
 * CTAForge — Signup Form Handler
 * Handles async form submission via WP AJAX.
 */
( function ( $ ) {
	'use strict';

	$( document ).on( 'submit', '.ctaforge-form', function ( e ) {
		e.preventDefault();

		const $form    = $( this );
		const $wrap    = $form.closest( '.ctaforge-form-wrap' );
		const $submit  = $form.find( '.ctaforge-submit' );
		const $label   = $submit.find( '.ctaforge-submit-label' );
		const $loading = $submit.find( '.ctaforge-submit-loading' );
		const $msg     = $form.find( '.ctaforge-message' );

		const successMsg = $form.data( 'success' );
		const errorMsg   = $form.data( 'error' );
		const listId     = $form.data( 'list-id' );

		// Reset state
		$msg.removeClass( 'ctaforge-success ctaforge-error' ).text( '' );
		$submit.prop( 'disabled', true );
		$label.hide();
		$loading.show();

		const data = {
			action:           'ctaforge_subscribe',
			_ctaforge_nonce:  ctaforgeAjax.nonce,
			email:            $form.find( '[name="email"]' ).val(),
			list_id:          listId,
			first_name:       $form.find( '[name="first_name"]' ).val() || '',
			last_name:        $form.find( '[name="last_name"]' ).val() || '',
		};

		$.post( ctaforgeAjax.url, data )
			.done( function ( response ) {
				if ( response.success ) {
					// Replace form with success message
					$form.slideUp( 200, function () {
						$wrap.append(
							$( '<div class="ctaforge-success-message" role="status" />' )
								.text( successMsg )
								.hide()
								.slideDown( 200 )
						);
					} );
				} else {
					const msg = response.data && response.data.message
						? response.data.message
						: errorMsg;
					$msg.addClass( 'ctaforge-error' ).text( msg );
					resetButton();
				}
			} )
			.fail( function () {
				$msg.addClass( 'ctaforge-error' ).text( errorMsg );
				resetButton();
			} );

		function resetButton() {
			$submit.prop( 'disabled', false );
			$loading.hide();
			$label.show();
		}
	} );

} )( jQuery );
