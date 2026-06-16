/**
 * Contract Pilot admin UI: forms, settings, charts, datepickers, SelectWoo integration.
 */
(function ($) {
	'use strict';

	function getMoneyApi() {
		return window.contractPilotMoney || null;
	}

	function contractPilotAdminAjax(data) {
		return new Promise(function (resolve, reject) {
			var postData = $.extend(
				{
					nonce: contract_pilot_admin_vars.admin_nonce,
				},
				data || {}
			);

			$.post(contract_pilot_admin_vars.ajax_url, postData)
				.done(function (response) {
					if (response && response.success) {
						resolve(response.data);
						return;
					}

					var message =
						response &&
						response.data &&
						response.data.message
							? response.data.message
							: 'Request failed.';
					reject({ message: message });
				})
				.fail(function () {
					reject({ message: 'Request failed.' });
				});
		});
	}

	function contractPilotCreateIdempotencyKey() {
		return (
			'cp-' +
			Date.now().toString(36) +
			'-' +
			Math.random().toString(36).slice(2, 10)
		);
	}

	function getExchangeRate(form) {
		var $input = form.$(':input[name="exchange_rate"]').first();

		return parseFloat($input.val()) || 1;
	}

	function createDocumentFormConfig(options) {
		var addressAction = options.addressAction;
		var recalcAction = options.recalcAction;
		var priceFromItem = options.priceFromItem;
		var unblockInAlways = options.unblockInAlways;

		return {
			events: {
				'change :input[name="contact_id"]': 'onChangeContact',
				'change :input[name="currency"]': 'onChangeCurrency',
				'select2:select .add-item': 'onAddItem',
				'click .remove-item': 'onRemoveItem',
				'change .item-price, .item-quantity': 'onChangeItem',
				'select2:select .item-taxes': 'onAddTax',
				'select2:unselect .item-taxes': 'onRemoveTax',
				'change :input[name="discount_type"], :input[name="discount_value"]':
					'onChangeDiscount',
			},
			onChangeContact: function () {
				var self = this;
				var values = self.getValues();

				delete values.contract_pilot_action;
				values.action = addressAction;
				self.block();

				$.post(ajaxurl, values, function (response) {
					self.unblock();

					var parsed = wpAjax.parseAjaxResponse(response, 'data');

					if (parsed && !parsed.errors) {
						self.$('.document-address').html(parsed.responses[0].data);
					} else {
						self.$('.document-address').html('');
					}
				});
			},
			onChangeCurrency: function () {
				this.updateTotals();
			},
			onAddItem: function (event) {
				var self = this;
				var itemData = event.params.data;
				var itemIndex = _.uniqueId();

				self.block();
				$(event.target).val(null).trigger('change');

				var exchangeRate = getExchangeRate(self);

				contractPilotAdminAjax({
					action: 'contract_pilot_get_item',
					id: itemData.id,
				}).then(function (item) {
						var fields = {};

						fields['items[' + itemIndex + '][item_id]'] = item.id;
						fields['items[' + itemIndex + '][name]'] = item.name;
						fields['items[' + itemIndex + '][description]'] =
							item.description;
						fields['items[' + itemIndex + '][price]'] = priceFromItem(
							item,
							exchangeRate
						);
						fields['items[' + itemIndex + '][quantity]'] = 1;
						fields['items[' + itemIndex + '][type]'] = item.type;
						fields['items[' + itemIndex + '][unit]'] = item.unit;

						if (item.taxes) {
							item.taxes.forEach(function (tax) {
								var taxIndex = _.uniqueId();

								fields[
									'items[' +
										itemIndex +
										'][taxes][' +
										taxIndex +
										'][tax_id]'
								] = tax.id;
								fields[
									'items[' +
										itemIndex +
										'][taxes][' +
										taxIndex +
										'][name]'
								] = tax.name;
								fields[
									'items[' +
										itemIndex +
										'][taxes][' +
										taxIndex +
										'][rate]'
								] = tax.rate;
								fields[
									'items[' +
										itemIndex +
										'][taxes][' +
										taxIndex +
										'][compound]'
								] = tax.compound || false;
							});
							self.updateTotals(fields);
						}
					});
			},
			onRemoveItem: function (event) {
				$(event.target).closest('tr').remove();
				this.updateTotals();
			},
			onChangeItem: function () {
				this.updateTotals();
			},
			onAddTax: function (event) {
				var taxData = event.params.data;
				var rowIndex = $(event.target).closest('tr').data('index');
				var taxIndex = _.uniqueId();
				var fields = $.extend({}, this.getValues());

				fields[
					'items[' + rowIndex + '][taxes][' + taxIndex + '][tax_id]'
				] = taxData.id;
				fields[
					'items[' + rowIndex + '][taxes][' + taxIndex + '][name]'
				] = taxData.name;
				fields[
					'items[' + rowIndex + '][taxes][' + taxIndex + '][rate]'
				] = taxData.rate;
				fields[
					'items[' + rowIndex + '][taxes][' + taxIndex + '][compound]'
				] = taxData?.compound;

				this.updateTotals(fields);
			},
			onRemoveTax: function (event) {
				var self = this;
				var values = self.getValues();
				var taxData = event.params.data;
				var $row = $(event.target).closest('tr');
				var rowIndex = $row.data('index');

				for (var key in values) {
					var match = key.match(
						/^items\[(\d+)\]\[taxes\]\[(\d+)\]\[tax_id\]$/
					);

					if (
						match &&
						match[1] === rowIndex.toString() &&
						values[key] === taxData.id.toString()
					) {
						$row
							.find(
								'input[name^="items[' +
									rowIndex +
									'][taxes][' +
									match[2] +
									']"]'
							)
							.remove();
					}
				}

				setTimeout(function () {
					self.$(event.target).select2('close');
					self.updateTotals();
				}, 0);
			},
			onChangeDiscount: function () {
				this.updateTotals();
			},
			updateTotals: function (extraData) {
				var self = this;
				var data = $.extend({}, this.getValues(), extraData || {}, {
					action: recalcAction,
				});
				var activeElement = document.activeElement;

				self.block();

				var request = $.post(ajaxurl, data, function (response) {
					var parsed = wpAjax.parseAjaxResponse(response);

					if (parsed && !parsed.errors) {
						self
							.$('.contract-pilot-document-items__items')
							.html(parsed.responses[0].data);
						self
							.$('.contract-pilot-document-items__totals')
							.html(parsed.responses[1].data);

						if (!unblockInAlways) {
							self.unblock();
							$(document.body).trigger('contract_pilot_update_ui');
							activeElement.focus();
						} else {
							$(document.body).trigger('contract_pilot_update_ui');
						}
					}
				});

				if (unblockInAlways) {
					request.always(function () {
						self.unblock();
						activeElement.focus();
					});
				}
			},
		};
	}

	// -------------------------------------------------------------------------
	// Settings
	// -------------------------------------------------------------------------

	$(document).ready(function () {
		var $body = $('body');

		$body.on('click', 'input#contract_pilot_business_logo', function (event) {
			event.preventDefault();

			var $input = $(this);
			var mediaFrame = wp.media({
				multiple: false,
				library: {
					type: 'image',
				},
			});

			mediaFrame.on('select', function () {
				var attachment = mediaFrame.state().get('selection').first().toJSON();

				$input.val(attachment.url);
			});

			mediaFrame.open();
		});

		$('.ea-financial-start').datepicker({
			dateFormat: 'dd-mm',
		});
	});

	// -------------------------------------------------------------------------
	// Charts
	// -------------------------------------------------------------------------

	$(document).ready(function () {
		if (
			typeof window.ContractPilotLineChart === 'undefined' ||
			typeof window.ContractPilotLineChart.initAll !== 'function'
		) {
			if ($('canvas.contract-pilot-chart').length) {
				console.warn(
					'Contract Pilot line chart module is not available.'
				);
			}
			return;
		}

		window.ContractPilotLineChart.initAll('canvas.contract-pilot-chart');
	});

	// -------------------------------------------------------------------------
	// Main admin UI enhancements
	// -------------------------------------------------------------------------

	function contractPilotQuery(context, selector) {
		if (!context) {
			return $(selector);
		}

		var $context = $(context);

		if ($context.is(selector)) {
			return $context;
		}

		return $context.find(selector);
	}

	function getContractPilotSearchConfig() {
		if (typeof contract_pilot_admin_vars !== 'undefined') {
			return {
				ajaxUrl: contract_pilot_admin_vars.ajax_url,
				searchNonce: contract_pilot_admin_vars.search_nonce,
			};
		}

		if (typeof window.ajaxurl !== 'undefined') {
			return {
				ajaxUrl: window.ajaxurl,
				searchNonce: '',
			};
		}

		return null;
	}

	function initContractPilotSelect2(context) {
		var searchConfig = getContractPilotSearchConfig();

		if (typeof $.fn.selectWoo !== 'function' || !searchConfig) {
			return;
		}

		contractPilotQuery(context, '.contract_pilot_select2')
			.filter(':not(.select2-hidden-accessible)')
			.each(function () {
				var $select = $(this);
				var $modal = $select.closest('.contract-pilot-modal');
				var ajaxAction = $select.data('action');
				var selectOptions = {
					allowClear:
						($select.data('allow-clear') &&
							!$select.prop('multiple')) ||
						true,
					placeholder:
						$select.data('placeholder') ||
						$select.attr('data-placeholder') ||
						'',
					width: '100%',
					minimumInputLength:
						$select.data('minimum-input-length') || 0,
					minimumResultsForSearch: 0,
					readOnly: $select.data('readonly') || false,
				};

				if (ajaxAction) {
					selectOptions.ajax = {
						url: searchConfig.ajaxUrl,
						dataType: 'json',
						type: 'POST',
						delay: 250,
						data: function (params) {
							return {
								term: params.term || '',
								action: ajaxAction,
								type: $select.data('type') || '',
								subtype: $select.data('subtype') || '',
								_wpnonce: searchConfig.searchNonce,
								exclude: $select.data('exclude') || '',
								include: $select.data('include') || '',
								limit: $select.data('limit') || 20,
								page: params.page || 1,
							};
						},
						processResults: function (response) {
							var payload = response;

							if (
								response &&
								typeof response === 'object' &&
								response.data &&
								typeof response.data === 'object'
							) {
								payload = response.data;
							}

							return {
								results:
									(payload && payload.results) || [],
								pagination:
									(payload && payload.pagination) || {
										more: false,
									},
							};
						},
						cache: true,
					};
				}

				if ($modal.length) {
					selectOptions.dropdownParent = $modal.find(
						'.contract-pilot-modal__content'
					);
				} else {
					var $field = $select.closest('.contract-pilot-form-field');
					var $wrap = $('.contract-pilot-wrap').first();

					if ($field.length) {
						selectOptions.dropdownParent = $field;
					} else if ($wrap.length) {
						selectOptions.dropdownParent = $wrap;
					}
				}

				if ($select.hasClass('select2-hidden-accessible')) {
					$select.selectWoo('destroy');
				}

				$select.addClass('enhanced').selectWoo(selectOptions);
			});
	}

	function initContractPilotDatepickers(context) {
		var $fields = contractPilotQuery(
			context,
			'.contract_pilot_datepicker, .contract_pilot_datetimepicker'
		);

		$fields.filter(':not(.hasDatepicker)').each(function () {
			var $input = $(this);

			$input.datepicker({
				dateFormat: $input.data('format') || 'yy-mm-dd',
				changeMonth: true,
				changeYear: true,
				showButtonPanel: true,
				showOtherMonths: true,
				selectOtherMonths: true,
				yearRange: '-100:+10',
			});
			$input.addClass('enhanced');
		});
	}

	function initContractPilotDatetimepickers(context) {
		initContractPilotDatepickers(context);
	}

	$(document).ready(function () {
		// Date/datetime pickers first so a Select2 error cannot skip them.
		initContractPilotDatepickers();
		initContractPilotDatetimepickers();

		$(document.body).on('click', '.contract_pilot_select2', function () {
			var $select = $(this);

			if (!$select.hasClass('select2-hidden-accessible')) {
				initContractPilotSelect2($select);
			}
		});

		$(document.body).on(
			'click',
			'.contract_pilot_datetimepicker',
			function () {
				var $input = $(this);

				if (!$input.hasClass('hasDatepicker')) {
					initContractPilotDatetimepickers($input);
				}

				if (!$input.hasClass('hasDatepicker')) {
					return;
				}

				if ($.datepicker) {
					$.datepicker._lastInput = null;
				}

				window.setTimeout(function () {
					$input.datepicker('show');
				}, 0);
			}
		);

		var enhanceAdminUI = function (context) {
			initContractPilotDatepickers(context);
			initContractPilotDatetimepickers(context);
			initContractPilotSelect2(context);

			contractPilotQuery(context, '.contract_pilot_tooltip')
				.filter(':not(.enhanced)')
				.each(function () {
					$(this)
						.addClass('enhanced')
						.tooltip({
							position: {
								my: 'center bottom-15',
								at: 'center top',
							},
							tooltipClass: 'contract_pilot_tooltip',
						});
				});

			var moneyApi = getMoneyApi();

			if (moneyApi) {
			contractPilotQuery(context, ':input.contract_pilot_amount')
				.not('.enhanced')
				.each(function () {
					var $input = $(this);
					var $source = $input.closest('form').find($input.data('source'));
					var fieldName = $input.attr('name');
					var initialValue = $input.val() || '';
					var maskOptions = moneyApi.getCurrencyMaskOptions(
						$input.data('currency')
					);

					$input
						.siblings('input[type="hidden"][name="' + fieldName + '"]')
						.remove();

					var $hidden = $('<input>', {
						type: 'hidden',
						name: fieldName,
						value: initialValue,
					}).insertAfter($input);

					$input
						.addClass('enhanced')
						.removeAttr('name')
						.attr('autocomplete', 'off')
						.contractPilotAmountMask({
							options: maskOptions,
							$hidden: $hidden,
							value: initialValue,
						});

					if ($source.length) {
						$source.on('change', function () {
							var currency =
								'currency' === $(this).attr('name')
									? $source.val()
									: $source.select2('data')?.[0]?.currency;

							$input.contractPilotAmountMask(
								'option',
								moneyApi.getCurrencyMaskOptions(currency)
							);
							$input.data('currency', currency);
						});
					}
				});
			}

			$('.contract-pilot-card').each(function () {
				if (
					!$(this).children('[class*="contract-pilot-card__"]').length &&
					!parseInt($(this).css('padding'), 10)
				) {
					$(this).css('padding', '8px 12px');
				}
			});

			$('.contract-pilot-card__body').each(function () {
				if (
					!$(this).text().trim().length &&
					0 === $(this).children().length
				) {
					$(this).remove();
				}
			});
		};

		enhanceAdminUI();
		$(document.body).on('contract_pilot_update_ui', function (event, context) {
			enhanceAdminUI(context);
		});

		$('.contract-pilot-file-upload')
			.filter(':not(.enhanced)')
			.each(function () {
				var $upload = $(this);
				var $button = $upload.find('.contract-pilot-file-upload__button');
				var $value = $upload.find('.contract-pilot-file-upload__value');
				var $icon = $upload.find('.contract-pilot-file-upload__icon img');
				var $nameLink = $upload.find('.contract-pilot-file-upload__name a');
				var $size = $upload.find('.contract-pilot-file-upload__size');
				var $remove = $upload.find('a.contract-pilot-file-upload__remove');

				$button.on('click', function (event) {
					event.preventDefault();

					var mediaFrame = wp.media({
						title: $button.data('uploader-title'),
						multiple: false,
					});

					mediaFrame.on('ready', function () {
						mediaFrame.uploader.options.uploader.params = {
							type: 'contract_pilot_file',
						};
					});

					mediaFrame.on('select', function () {
						var attachment = mediaFrame
							.state()
							.get('selection')
							.first()
							.toJSON();
						var previewUrl =
							'image' === attachment.type
								? attachment.url
								: attachment.icon;

						$value.val(attachment.id);
						$icon.attr('src', previewUrl).show();
						$icon.attr('alt', attachment.filename);
						$nameLink.text(attachment.filename).attr('href', attachment.url);
						$size.text(attachment.filesizeHumanReadable);
						$remove.show();
						$upload.addClass('has--file');
					});

					mediaFrame.open();
				});

				$remove.on('click', function (event) {
					event.preventDefault();
					$upload.removeClass('has--file');
					$value.val('');
					$icon.attr('src', '').hide();
					$nameLink.text('').attr('href', '');
					$size.text('');
				});
			});

		$('.del_confirm').on('click', function (event) {
			if (!confirm(contract_pilot_admin_vars.i18n.confirm_delete)) {
				event.preventDefault();
				return false;
			}
		});

		$(document.body)
			.on('keydown', '#contract-pilot-note', function (event) {
				if (
					13 === event.keyCode &&
					(event.metaKey || event.ctrlKey)
				) {
					event.preventDefault();
					$('#contract-pilot-add-note').click();
				}
			})
			.on('click', '#contract-pilot-add-note', function (event) {
				event.preventDefault();

				var $button = $(this);
				var $noteInput = $('#contract-pilot-note');
				var $notesList = $('.contract-pilot-notes');
				var requestData = {
					action: 'contract_pilot_add_note',
					nonce: $button.data('nonce'),
					parent_id: $button.data('parent_id'),
					parent_type: $button.data('parent_type'),
					content: $noteInput.val(),
				};

				if (!requestData.content) {
					return;
				}

				$button.prop('disabled', true);

				$.ajax({
					type: 'POST',
					url: contract_pilot_admin_vars.ajax_url,
					data: requestData,
					success: function (response) {
						var parsed = wpAjax.parseAjaxResponse(response);

						parsed = parsed.responses[0];
						$notesList.prepend(parsed.data);
						$noteInput.val('');
						$button.prop('disabled', false);
						$notesList.find('.no-items').hide();
					},
				}).fail(function () {
					$button.prop('disabled', false);
				});
			})
			.on('click', '.contract-pilot-notes .note__delete', function (event) {
				event.preventDefault();

				var $deleteButton = $(this);
				var $note = $deleteButton.closest('.note');
				var $notesList = $note.closest('.contract-pilot-notes');
				var requestData = {
					action: 'contract_pilot_delete_note',
					nonce: $deleteButton.data('nonce'),
					note_id: $deleteButton.data('note_id'),
				};

				if (!confirm(contract_pilot_admin_vars.i18n.confirm_delete)) {
					return;
				}

				$.ajax({
					type: 'POST',
					url: contract_pilot_admin_vars.ajax_url,
					data: requestData,
					success: function (response) {
						if ('1' === response) {
							$note.remove();
						}

						if (0 === $notesList.find('li').length) {
							$notesList.find('.no-items').show();
						}
					},
				});
			});

		$('.contract_pilot_print_document').on('click', function (event) {
			event.preventDefault();

			var $target = $($(this).data('target'));

			if ($target.length) {
				$target.printThis({
					printContainer: true,
					printDelay: 0,
					header: null,
					footer: null,
				});
			}
		});

		$.fn.block = function (unblock) {
			return this.each(function () {
				var $element = $(this);

				if (unblock && $element.find('.blockUI').length) {
					$element.find('.blockUI').remove();
				} else if (!$element.find('.blockUI').length) {
					if ('static' === $element.css('position')) {
						$element.css('position', 'relative');
					}

					$('<div class="blockUI"></div>')
						.css({
							position: 'absolute',
							top: 0,
							left: 0,
							width: '100%',
							height: '100%',
							backgroundColor: 'rgb(255, 255, 255)',
							opacity: 0.1,
							cursor: 'wait',
							zIndex: 9999,
						})
						.appendTo($element);
				}
			});
		};
	});

	// -------------------------------------------------------------------------
	// Invoice edit form
	// -------------------------------------------------------------------------

	$(document).ready(function () {
		$('#contract-pilot-edit-invoice').contract_pilot_form(
			createDocumentFormConfig({
				addressAction: 'contract_pilot_get_invoice_address',
				recalcAction: 'contract_pilot_get_recalculated_invoice',
				unblockInAlways: false,
				priceFromItem: function (item, exchangeRate) {
					return (item.price || item.cost) * exchangeRate;
				},
			})
		);

		$('.contract-pilot-add-invoice-payment').on('click', function (event) {
			event.preventDefault();

			var $button = $(this);
			var invoiceId = $button.data('id');

			$button.prop('disabled', true);

			contractPilotAdminAjax({
				action: 'contract_pilot_get_invoice_for_payment',
				id: invoiceId,
			})
				.then(function (invoice) {
					$button.contract_pilot_modal({
						template: 'contract-pilot-invoice-payment',
						events: {
							'change :input[name="account_id"]': 'onChangeAccount',
							submit: 'onSubmit',
						},
						onChangeAccount: function (changeEvent) {
							var $form = $(changeEvent.target).closest('form');
							var currency =
								($(changeEvent.target).select2('data')?.[0] || {})
									.currency || contract_pilot_base_currency;

							$form.find(':input[name="exchange_rate"]').val('1');
							$form
								.find(':input[id="amount"]')
								.removeClass('enhanced')
								.data('currency', currency)
								.val(invoice.due_amount);
							$(document.body).trigger('contract_pilot_update_ui');
						},
						onSubmit: function (submitEvent) {
							submitEvent.preventDefault();

							var modal = this;
							var values = modal.getValues();

							contractPilotAdminAjax(
								$.extend({}, values, {
									action: 'contract_pilot_create_invoice_payment',
									amount: getMoneyApi().unformat(
										values.amount,
										values.currency
									),
									editable: false,
									_cp_idempotency_key:
										contractPilotCreateIdempotencyKey(),
								})
							).then(function () {
									modal.close();
									location.reload();
								})
								.catch(function (error) {
									if (error.message) {
										alert(error.message);
									} else {
										alert(
											'Something went wrong. Please try again.'
										);
									}
								});
						},
					});
				})
				.finally(function () {
					$button.prop('disabled', false);
				});
		});
	});
})(jQuery);
