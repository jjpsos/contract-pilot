/**
 * Contract Pilot admin money helpers (USD/CAD only).
 *
 * Expects window.contractPilotMoneyConfig from PHP:
 *   { baseCurrency: 'USD', currencies: { USD: {...}, CAD: {...} } }
 */
(function () {
	'use strict';

	function getConfig(currencyCode) {
		var config = window.contractPilotMoneyConfig || {};
		var currencies = config.currencies || {};
		var base = config.baseCurrency || 'USD';

		currencyCode = currencyCode || base;

		return currencies[currencyCode] || currencies[base] || {};
	}

	function unformat(amount, currencyCode) {
		var settings = getConfig(currencyCode);

		if (amount === null || amount === undefined || amount === '') {
			return 0;
		}

		if (typeof amount === 'number') {
			return amount;
		}

		var decimalSeparator = settings.decimal || '.';
		var numericPattern = new RegExp('[^0-9' + decimalSeparator + '\\-+]', 'g');
		var trimmed = String(amount).trim();
		var isNegative = /^-/.test(trimmed) || /-$/.test(trimmed);
		var normalized = trimmed
			.replace(numericPattern, '')
			.replace(decimalSeparator, '.');
		var parsed = parseFloat(normalized);

		if (isNaN(parsed)) {
			return 0;
		}

		return isNegative ? -parsed : parsed;
	}

	function getCurrencyMaskOptions(currencyCode) {
		var settings = getConfig(currencyCode);
		var symbol = (settings.symbol || '$').replace(/[.,]/g, '');
		var position = settings.position || 'before';

		return {
			alias: 'currency',
			placeholder: '0.00',
			rightAlign: false,
			allowMinus: true,
			digits: settings.precision || 2,
			radixPoint: settings.decimal || '.',
			groupSeparator: settings.thousand || ',',
			prefix: position === 'before' ? symbol : '',
			suffix: position === 'after' ? symbol : '',
			autoUnmask: true,
		};
	}

	window.contractPilotMoney = {
		getCurrencyMaskOptions: getCurrencyMaskOptions,
		unformat: unformat,
	};
})();
