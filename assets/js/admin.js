/**
 * Sequential PDF Invoices — admin scripts.
 *
 * Handles the media-library logo picker and the meta-box "delete invoice" action.
 *
 * @package WooPdfInvoice
 */
(function ( $ ) {
	'use strict';

	var mediaFrame = null;

	/**
	 * Media-library based logo picker.
	 */
	function initLogoPicker() {
		var field = $( '.wpi-logo-field' );

		if ( ! field.length ) {
			return;
		}

		field.on( 'click', '.wpi-select-logo', function ( event ) {
			event.preventDefault();

			if ( mediaFrame ) {
				mediaFrame.open();
				return;
			}

			mediaFrame = wp.media( {
				title: window.wpiAdmin ? window.wpiAdmin.logoTitle : 'Select logo',
				library: { type: 'image' },
				multiple: false,
				button: {
					text: window.wpiAdmin ? window.wpiAdmin.useThisImage : 'Use this image'
				}
			} );

			mediaFrame.on( 'select', function () {
				var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();

				field.find( '#company_logo' ).val( attachment.id );
				field.find( '.wpi-logo-preview' ).html(
					'<img src="' + attachment.url + '" alt="" style="max-width:220px;height:auto;" />'
				);
			} );

			mediaFrame.open();
		} );

		field.on( 'click', '.wpi-remove-logo', function ( event ) {
			event.preventDefault();

			field.find( '#company_logo' ).val( '0' );
			field.find( '.wpi-logo-preview' ).empty();
		} );
	}

	/**
	 * Deletes the invoice from the order meta box.
	 */
	function initMetaBoxDelete() {
		$( document ).on( 'click', '.wpi-delete-invoice', function ( event ) {
			event.preventDefault();

			var button = $( this );
			var orderId = button.data( 'order' );
			var nonce = button.data( 'nonce' );
			var confirmMessage = window.wpiAdmin ? window.wpiAdmin.deleteConfirm : 'Delete this invoice?';

			if ( ! window.confirm( confirmMessage ) ) {
				return;
			}

			button.prop( 'disabled', true );

			$.post( window.ajaxurl, {
				action: 'wpi_delete_invoice',
				order_id: orderId,
				nonce: nonce
			} ).always( function () {
				window.location.reload();
			} );
		} );
	}

	$( function () {
		initLogoPicker();
		initMetaBoxDelete();
	} );
} )( jQuery );
