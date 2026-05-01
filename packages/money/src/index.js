const Money = ( () => {
	return {
		/**
		 * Returns the available currencies.
		 *
		 * @return {Object[]} An array of currencies.
		 */
		getCurrencies() {
			return window.eac_currencies || [];
		},

		/**
		 * Retrieves the configuration for a given currency.
		 *
		 * @param {string} [currency] The currency code (e.g., 'USD').
		 * @return {Object} The currency configuration object.
		 */
		getConfig( currency ) {
			currency = currency || window.eac_base_currency || 'USD';
			const currencies = this.getCurrencies();
			return currencies[ currency ] || {};
		},

		/**
		 * Gets the symbol for a given currency.
		 *
		 * @param {string} [currency] The currency code (e.g., 'USD').
		 * @return {string} The currency symbol.
		 */
		getSymbol( currency ) {
			const config = this.getConfig( currency );
			return config.symbol || '$';
		},

		/**
		 * Gets the name of a given currency.
		 *
		 * @param {string} [currency] The currency code (e.g., 'USD').
		 * @return {string} The currency name.
		 */
		getName( currency ) {
			const config = this.getConfig( currency );
			return config.name || currency;
		},

		/**
		 * Gets the exchange rate for a given currency.
		 *
		 * @param {string} [currency] The currency code (e.g., 'USD').
		 * @return {number} The currency exchange rate.
		 */
		getRate( currency ) {
			const config = this.getConfig( currency );
			return parseFloat( config.rate ) || 1;
		},

		/**
		 * Gets the precision for a given currency.
		 *
		 * @param {string} [currency] The currency code (e.g., 'USD').
		 * @return {number} The number of decimal places to use.
		 */
		getPrecision( currency ) {
			const config = this.getConfig( currency );
			return config.precision || 2;
		},

		/**
		 * Gets the position of the currency symbol (before or after the amount).
		 *
		 * @param {string} [currency] The currency code (e.g., 'USD').
		 * @return {string} 'before' or 'after'.
		 */
		getPosition( currency ) {
			const config = this.getConfig( currency );
			return config.position || 'before';
		},

		/**
		 * Gets the thousand separator for a given currency.
		 *
		 * @param {string} [currency] The currency code (e.g., 'USD').
		 * @return {string} The thousand separator character.
		 */
		getThousand( currency ) {
			const config = this.getConfig( currency );
			return config.thousand || ',';
		},

		/**
		 * Gets the decimal separator for a given currency.
		 *
		 * @param {string} [currency] The currency code (e.g., 'USD').
		 * @return {string} The decimal separator character.
		 */
		getDecimal( currency ) {
			const config = this.getConfig( currency );
			return config.decimal || '.';
		},

		/**
		 * Formats an amount according to the specified currency's rules.
		 *
		 * @param {number|string} amount     The amount to format.
		 * @param {string}        [currency] The currency code (e.g., 'USD').
		 * @return {string} The formatted amount as a string.
		 */
		format( amount, currency ) {
			currency = currency || window.eac_base_currency || 'USD';
			amount = this.unformat( amount );

			const negative = amount < 0;
			const absAmount = Math.abs( amount );

			const precision = this.getPrecision( currency );
			const thousand = this.getThousand( currency );
			const decimal = this.getDecimal( currency );
			const position = this.getPosition( currency );
			const symbol = this.getSymbol( currency );

			// Format integer and decimal parts
			const parts = absAmount.toFixed( precision ).split( '.' );
			const integerPart = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, thousand );

			const decimalPart = parts.length > 1 ? decimal + parts[ 1 ] : '';

			// Construct formatted value
			const formattedAmount =
				position === 'before'
					? symbol + integerPart + decimalPart
					: integerPart + decimalPart + symbol;

			return negative ? '-' + formattedAmount : formattedAmount;
		},

		/**
		 * Converts a formatted amount back to a numeric value based on currency rules.
		 *
		 * @param {string|number} amount     The formatted amount to unformat.
		 * @param {string}        [currency] The currency code (e.g., 'USD').
		 * @return {number} The unformatted numeric value.
		 */
		unformat( amount, currency ) {
			currency = currency || window.eac_base_currency || 'USD';

			if ( amount === undefined || amount === null || amount === '' ) {
				return 0;
			}

			if ( typeof amount === 'number' ) {
				return amount;
			}

			const decimal = this.getDecimal( currency );
			const regex = new RegExp( `[^0-9${ decimal }]`, 'g' );

			const raw = String( amount ).trim();
			const isNegative = /^-/.test( raw ) || /-$/.test( raw );

			const cleaned = raw.replace( regex, '' ).replace( decimal, '.' );

			const unformatted = parseFloat( cleaned );

			if ( isNaN( unformatted ) ) {
				return 0;
			}

			return isNegative ? -unformatted : unformatted;
		},

		/**
		 * Converts an amount from one currency to another using their exchange rates.
		 *
		 * @param {number|string} amount The amount to convert.
		 * @param {string|number} from   The source currency code (e.g., 'USD').
		 * @param {string|number} to     The target currency code (e.g., 'EUR').
		 * @return {number} The converted amount in the target currency.
		 */
		convert( amount, from, to ) {
			amount = this.unformat( amount );

			from = typeof from === 'string' && from.length === 3 ? this.getRate( from ) : from;
			to = typeof to === 'string' && to.length === 3 ? this.getRate( to ) : to;

			if ( isNaN( from ) || from <= 0 || isNaN( to ) || to <= 0 ) {
				return amount;
			}

			if ( from === to ) {
				return amount;
			}

			if ( from !== 1 ) {
				amount = amount / from;
			}

			if ( to !== 1 ) {
				amount = amount * to;
			}

			return amount;
		},

		/**
		 * Returns the absolute integer value of an amount after unformatting it.
		 *
		 * @param {number|string} amount The amount to convert to an absolute integer.
		 * @return {number} The absolute integer value of the unformatted amount.
		 */
		absint( amount ) {
			return Math.abs( Math.round( this.unformat( amount ) ) );
		},

		/**
		 * Get currency mask options for a currency.
		 *
		 * @param {string} currency The currency code (e.g., 'USD').
		 *
		 * @return {Object} The input mask options.
		 */
		getCurrencyMaskOptions( currency ) {
			const symbol = this.getSymbol( currency ).replace( /[.,]/g, '' );
			const position = this.getPosition( currency );

			return {
				alias: 'currency',
				placeholder: '0.00',
				rightAlign: false,
				allowMinus: true,
				digits: this.getPrecision( currency ),
				radixPoint: this.getDecimal( currency ),
				groupSeparator: this.getThousand( currency ),
				prefix: 'before' === position ? symbol : '',
				suffix: 'after' === position ? symbol : '',
				autoUnmask: true,
			};
		},

		/**
		 * Get exchange rate mask options for a currency.
		 *
		 * @param {string} currency The currency code (e.g., 'USD').
		 *
		 * @return {Object} The input mask options.
		 */
		getExchangeRateMaskOptions( currency ) {
			const symbol = this.getSymbol( currency ).replace( /[.,]/g, '' );
			return {
				alias: 'decimal',
				rightAlign: false,
				allowMinus: false,
				digitsOptional: false,
				groupSeparator: '',
				digits: 4,
				suffix: symbol,
				removeMaskOnSubmit: true,
			};
		},
	};
} )();

export default Money;
