// @ts-nocheck
/* eslint-disable prettier/prettier */
import './styles/public.scss';

/**
 * Quote popup: opens from any ".doitgq-get-quote-button", sends the form with
 * fetch and shows the answer. The settings come from window.doitgqQuote
 * (set by Frontend\Public_Assets).
 */
const initQuotePopup = () => {
	const config = window.doitgqQuote;
	const overlay = document.getElementById( 'doitgq-popup-overlay' );
	const form = document.getElementById( 'doitgq-form' );

	if ( ! config || ! overlay || ! form ) {
		return;
	}

	const response = document.getElementById( 'doitgq-form-response' );
	const submit = document.getElementById( 'doitgq-submit-btn' );
	let opener = null;
	let closeTimer = null;

	// A disabled input is not sent, so only the fields in use reach the server.
	const setHidden = ( name, value ) => {
		const input = form.elements[ name ];

		if ( input ) {
			input.value = value;
			input.disabled = '' === value;
		}
	};

	const showMessage = ( message, type ) => {
		response.textContent = message;
		response.className = type ? `doitgq-form-response is-${ type }` : 'doitgq-form-response';
	};

	const close = () => {
		clearTimeout( closeTimer );
		overlay.hidden = true;
		document.body.classList.remove( 'doitgq-popup-open' );

		if ( opener ) {
			opener.focus();
		}
	};

	const open = ( button ) => {
		const isCart = 'cart' === button.dataset.type;
		const productId = button.dataset.productId || '';

		if ( ! isCart && ( '' === productId || '0' === productId ) ) {
			return;
		}

		clearTimeout( closeTimer );
		form.reset();
		showMessage( '', '' );

		setHidden( 'is_cart', isCart ? '1' : '' );
		setHidden( 'is_product', isCart ? '' : '1' );
		setHidden( 'product_id', isCart ? '' : productId );

		opener = button;
		overlay.hidden = false;
		document.body.classList.add( 'doitgq-popup-open' );

		const first = form.querySelector( '.doitgq-field input, .doitgq-field textarea, .doitgq-field select' );

		if ( first ) {
			first.focus();
		}
	};

	// One listener for every button, however many the page has.
	document.addEventListener( 'click', ( event ) => {
		const button = event.target.closest( '.doitgq-get-quote-button' );

		if ( button ) {
			event.preventDefault();
			open( button );
		}
	} );

	// @ts-ignore
	document.getElementById( 'doitgq-close-popup' ).addEventListener( 'click', close );

	overlay.addEventListener( 'click', ( event ) => {
		if ( event.target === overlay ) {
			close();
		}
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' === event.key && ! overlay.hidden ) {
			close();
		}
	} );

	form.addEventListener( 'submit', ( event ) => {
		event.preventDefault();

		// @ts-ignore
		const label = submit.textContent;
		// @ts-ignore
		const body = new FormData( form );

		body.append( 'action', config.action );
		body.append( 'nonce', config.nonce );

		// @ts-ignore
		submit.disabled = true;
		// @ts-ignore
		submit.textContent = config.i18n.sending;

		fetch( config.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } )
			.then( ( res ) => res.json() )
			.then( ( result ) => {
				if ( result && result.success ) {
					showMessage( result.data, 'success' );
					closeTimer = setTimeout( () => {
						// @ts-ignore
						form.reset();
						close();
					}, 2500 );
				} else {
					showMessage( ( result && result.data ) || config.i18n.error, 'error' );
				}
			} )
			.catch( () => showMessage( config.i18n.error, 'error' ) )
			// @ts-ignore
			.finally( () => {
				// @ts-ignore
				submit.disabled = false;
				// @ts-ignore
				submit.textContent = label;
			} );
	} );
};

/**
 * Run a function once the DOM is ready.
 *
 * @param {Function} fn Function to run.
 */
const ready = ( fn ) => {
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', fn );
	} else {
		fn();
	}
};

ready( () => {
	// If this file is loaded twice, run only once. A copy that finds no
	// settings (not loaded by Frontend\Public_Assets) does nothing and does
	// not block the copy that has them.
	// @ts-ignore
	if ( window.doitgqPublicInit || ! window.doitgqQuote ) {
		return;
	}

	// @ts-ignore
	window.doitgqPublicInit = true;
	initQuotePopup();
} );