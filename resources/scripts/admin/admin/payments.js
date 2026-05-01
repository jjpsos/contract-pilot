jQuery( document ).ready( ( $ ) => {
	'use strict';

	// $( '#eac-edit-payment' ).on( 'change', ':input[name="account_id"]', function ( e ) {
	// 	var $form = $( this ).closest( 'form' ),
	// 		$amount = $( ':input[name="amount"]', $form ),
	// 		$exchange = $( ':input[name="exchange_rate"]', $form ),
	// 		account = $( e.target ).select2( 'data' )?.[0],
	// 		currency = account?.currency || eac_base_currency,
	// 		config = eac_currencies[ account?.currency ] || eac_currencies[ eac_base_currency ];
	//
	// 	$amount.removeClass( 'enhanced' ).data( 'currency', currency );
	// 	$exchange.val( config?.rate || 1 ).removeClass( 'enhanced' ).data( 'currency', currency ).attr( 'readonly', currency === eac_base_currency );
	// 	$( document.body ).trigger( 'eac_update_ui' );
	// } );
} );
