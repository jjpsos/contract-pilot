/**
 * Contract Pilot: blur-only currency amount formatting for admin fields.
 *
 * Pairs a visible text input with a hidden input that stores the canonical
 * dot-decimal value submitted to PHP.
 */
(function ($) {
	'use strict';

	var DATA_KEY = 'contractPilotAmountMask';

	function normalizeOptions(options) {
		options = options || {};

		return {
			digits: options.digits !== undefined ? options.digits : 2,
			radixPoint: options.radixPoint || '.',
			groupSeparator: options.groupSeparator || ',',
			prefix: options.prefix || '',
			suffix: options.suffix || '',
			allowMinus: options.allowMinus !== false,
		};
	}

	function escapeRegExp(value) {
		return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	function parseDisplayValue(value, options) {
		if (value === null || value === undefined) {
			return '';
		}

		var str = String(value).trim();

		if (str === '') {
			return '';
		}

		if (options.prefix && str.indexOf(options.prefix) === 0) {
			str = str.slice(options.prefix.length);
		}

		if (
			options.suffix &&
			str.slice(-options.suffix.length) === options.suffix
		) {
			str = str.slice(0, -options.suffix.length);
		}

		str = str.trim();

		var isNegative = options.allowMinus && /^-/.test(str);
		var groupPattern = options.groupSeparator
			? new RegExp(escapeRegExp(options.groupSeparator), 'g')
			: null;

		str = str.replace(/[^\d.,\-]/g, '');

		if (groupPattern) {
			str = str.replace(groupPattern, '');
		}

		if (options.radixPoint && options.radixPoint !== '.') {
			str = str.replace(
				new RegExp(escapeRegExp(options.radixPoint), 'g'),
				'.'
			);
		}

		str = str.replace(/-/g, '');

		if (str === '' || str === '.') {
			return '';
		}

		var numeric = parseFloat(str);

		if (isNaN(numeric)) {
			return '';
		}

		if (isNegative) {
			numeric = -Math.abs(numeric);
		}

		if (!options.allowMinus && numeric < 0) {
			numeric = Math.abs(numeric);
		}

		return numeric;
	}

	function formatDisplay(rawValue, options) {
		if (rawValue === '' || rawValue === null || rawValue === undefined) {
			return '';
		}

		var numeric =
			typeof rawValue === 'number'
				? rawValue
				: parseFloat(String(rawValue));

		if (isNaN(numeric)) {
			return '';
		}

		if (!options.allowMinus && numeric < 0) {
			numeric = Math.abs(numeric);
		}

		var negative = numeric < 0;
		var absolute = Math.abs(numeric);
		var fixed = absolute.toFixed(options.digits);
		var parts = fixed.split('.');
		var group = options.groupSeparator || '';

		if (group) {
			parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, group);
		}

		var formatted = parts.join(options.radixPoint || '.');

		if (negative && options.allowMinus) {
			formatted = '-' + formatted;
		}

		return (options.prefix || '') + formatted + (options.suffix || '');
	}

	function toCanonicalString(rawValue, options) {
		if (rawValue === '' || rawValue === null || rawValue === undefined) {
			return '';
		}

		var numeric =
			typeof rawValue === 'number'
				? rawValue
				: parseFloat(String(rawValue));

		if (isNaN(numeric)) {
			return '';
		}

		return numeric.toFixed(options.digits);
	}

	function syncField($input, formatVisible) {
		var state = $.data($input[0], DATA_KEY);

		if (!state) {
			return;
		}

		var parsed = parseDisplayValue($input.val(), state.options);
		var canonical = toCanonicalString(parsed, state.options);

		if (state.$hidden && state.$hidden.length) {
			state.$hidden.val(canonical).trigger('change');
		}

		if (formatVisible) {
			$input.val(formatDisplay(parsed, state.options));
		}
	}

	function attach($input, config) {
		if ($input.data(DATA_KEY)) {
			return;
		}

		var state = {
			options: normalizeOptions(config.options || config),
			$hidden: config.$hidden || null,
		};

		$.data($input[0], DATA_KEY, state);

		var initial =
			config.value !== undefined && config.value !== null
				? config.value
				: $input.val();

		if (state.$hidden && state.$hidden.length) {
			var hiddenValue = state.$hidden.val();

			if (hiddenValue !== undefined && hiddenValue !== '') {
				initial = hiddenValue;
			}
		}

		var parsedInitial = parseDisplayValue(initial, state.options);

		if (parsedInitial === '' && initial !== '' && initial !== null) {
			parsedInitial = parseFloat(String(initial).replace(/[^\d.-]/g, ''));

			if (isNaN(parsedInitial)) {
				parsedInitial = '';
			}
		}

		$input.val(formatDisplay(parsedInitial, state.options));

		if (state.$hidden && state.$hidden.length) {
			state.$hidden
				.val(toCanonicalString(parsedInitial, state.options))
				.trigger('change');
		}

		$input.on('blur.contractPilotAmountMask', function () {
			syncField($input, true);
		});
	}

	function getUnmaskedValue($input) {
		var state = $.data($input[0], DATA_KEY);

		if (!state) {
			return '';
		}

		return toCanonicalString(
			parseDisplayValue($input.val(), state.options),
			state.options
		);
	}

	$.fn.contractPilotAmountMask = function (method, arg) {
		if (method === 'unmaskedValue') {
			return getUnmaskedValue(this.first());
		}

		if (method === 'sync') {
			return this.each(function () {
				syncField($(this), true);
			});
		}

		if (method === 'option') {
			return this.each(function () {
				var $input = $(this);
				var state = $.data(this, DATA_KEY);

				if (!state) {
					return;
				}

				state.options = normalizeOptions(
					$.extend({}, state.options, arg || {})
				);
				syncField($input, document.activeElement !== this);
			});
		}

		return this.each(function () {
			attach($(this), method || {});
		});
	};

	$(document).on('submit', 'form', function () {
		$(this)
			.find('.contract_pilot_amount.enhanced')
			.each(function () {
				if ($.data(this, DATA_KEY)) {
					$(this).contractPilotAmountMask('sync');
				}
			});
	});
})(jQuery);
