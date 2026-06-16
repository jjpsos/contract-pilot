/**
 * printThis jQuery plugin — print a portion of the page in a hidden iframe.
 * @see https://github.com/jasonday/printThis
 */
(function ($) {
	'use strict';

	/**
	 * Append raw HTML or a jQuery collection (cloned) to a container.
	 *
	 * @param {jQuery} $container Target element.
	 * @param {jQuery|string}    content    HTML string or jQuery object.
	 */
	function appendContent($container, content) {
		if (!content) {
			return;
		}

		$container.append(content.jquery ? content.clone() : content);
	}

	/**
	 * @param {jQuery} $iframe Hidden print iframe.
	 * @returns {Document}
	 */
	function getIframeDocument($iframe) {
		var iframeNode = $iframe.get(0);
		var iframeWindow =
			iframeNode.contentWindow ||
			iframeNode.contentDocument ||
			iframeNode;

		return (
			iframeWindow.document ||
			iframeWindow.contentDocument ||
			iframeWindow
		);
	}

	/**
	 * @param {jQuery} $iframe
	 * @param {string} doctypeString
	 */
	function writeDocType($iframe, doctypeString) {
		var doc = getIframeDocument($iframe);

		doc.open();
		doc.write(doctypeString);
		doc.close();
	}

	/**
	 * Clone source markup into the print iframe body.
	 *
	 * @param {jQuery} $printBody  Body inside the iframe.
	 * @param {jQuery} $source     Elements to print.
	 * @param {Object} options     printThis options.
	 */
	function copyMarkupIntoPrintBody($printBody, $source, options) {
		var $cloned = $source.clone(options.formValues);

		if (options.formValues) {
			var formFieldSelector = 'select, textarea';
			var $sourceFields = $source.find(formFieldSelector);

			$cloned.find(formFieldSelector).each(function (index, field) {
				$(field).val($sourceFields.eq(index).val());
			});
		}

		if (options.removeScripts) {
			$cloned.find('script').remove();
		}

		if (options.printContainer) {
			$cloned.appendTo($printBody);
		} else {
			$cloned.each(function () {
				$(this).children().appendTo($printBody);
			});
		}
	}

	/**
	 * @param {jQuery}   $iframe
	 * @param {Function} callback
	 */
	function bindBeforePrintEvent($iframe, callback) {
		if ('function' !== typeof callback) {
			return;
		}

		var iframeNode = $iframe.get(0);
		var printWindow =
			iframeNode.contentWindow || iframeNode.contentDocument || iframeNode;

		if ('matchMedia' in printWindow) {
			printWindow.matchMedia('print').addListener(function (mediaQuery) {
				if (mediaQuery.matches) {
					callback();
				}
			});
		} else {
			printWindow.onbeforeprint = callback;
		}
	}

	/**
	 * @param {Object} [userOptions]
	 * @returns {jQuery}
	 */
	$.fn.printThis = function (userOptions) {
		var options = $.extend({}, $.fn.printThis.defaults, userOptions);
		var $elements = this instanceof jQuery ? this : $(this);
		var iframeId = 'printThis-' + new Date().getTime();

		// Legacy IE: cross-domain iframe needs document.domain script.
		if (
			window.location.hostname !== document.domain &&
			navigator.userAgent.match(/msie/i)
		) {
			var domainScript =
				'javascript:document.write("<head><script>document.domain=\\"' +
				document.domain +
				'\\";<\/script></head><body></body>")';
			var iframeElement = document.createElement('iframe');

			iframeElement.name = 'printIframe';
			iframeElement.id = iframeId;
			iframeElement.className = 'MSIE';
			document.body.appendChild(iframeElement);
			iframeElement.src = domainScript;
		} else {
			$(
				"<iframe id='" + iframeId + "' name='printIframe' />"
			).appendTo('body');
		}

		var $iframe = $('#' + iframeId);

		if (!options.debug) {
			$iframe.css({
				position: 'absolute',
				width: '0px',
				height: '0px',
				left: '-600px',
				top: '-600px',
			});
		}

		if ('function' === typeof options.beforePrint) {
			options.beforePrint();
		}

		setTimeout(function () {
			if (options.doctypeString) {
				writeDocType($iframe, options.doctypeString);
			}

			var $iframeDocument = $iframe.contents();
			var $printHead = $iframeDocument.find('head');
			var $printBody = $iframeDocument.find('body');
			var $pageBase = $('base');
			var baseHref;

			if (true === options.base && $pageBase.length > 0) {
				baseHref = $pageBase.attr('href');
			} else if ('string' === typeof options.base) {
				baseHref = options.base;
			} else {
				baseHref =
					document.location.protocol + '//' + document.location.host;
			}

			$printHead.append('<base href="' + baseHref + '">');

			if (options.importCSS) {
				$('link[rel=stylesheet]').each(function () {
					var stylesheetUrl = $(this).attr('href');

					if (stylesheetUrl) {
						var media = $(this).attr('media') || 'all';
						$printHead.append(
							"<link type='text/css' rel='stylesheet' href='" +
								stylesheetUrl +
								"' media='" +
								media +
								"'>"
						);
					}
				});
			}

			if (options.importStyle) {
				$('style').each(function () {
					$printHead.append(this.outerHTML);
				});
			}

			if (options.pageTitle) {
				$printHead.append('<title>' + options.pageTitle + '</title>');
			}

			if (options.loadCSS) {
				if ($.isArray(options.loadCSS)) {
					$.each(options.loadCSS, function (index, url) {
						$printHead.append(
							"<link type='text/css' rel='stylesheet' href='" +
								url +
								"'>"
						);
					});
				} else {
					$printHead.append(
						"<link type='text/css' rel='stylesheet' href='" +
							options.loadCSS +
							"'>"
					);
				}
			}

			var $pageHtml = $('html')[0];

			$iframeDocument.find('html').prop('style', $pageHtml.style.cssText);

			var tagClassCopy = options.copyTagClasses;

			if (tagClassCopy) {
				tagClassCopy = true === tagClassCopy ? 'bh' : tagClassCopy;

				if (-1 !== tagClassCopy.indexOf('b')) {
					$printBody.addClass($('body')[0].className);
				}

				if (-1 !== tagClassCopy.indexOf('h')) {
					$iframeDocument.find('html').addClass($pageHtml.className);
				}
			}

			var tagStyleCopy = options.copyTagStyles;

			if (tagStyleCopy) {
				tagStyleCopy = true === tagStyleCopy ? 'bh' : tagStyleCopy;

				if (-1 !== tagStyleCopy.indexOf('b')) {
					$printBody.attr('style', $('body')[0].style.cssText);
				}

				if (-1 !== tagStyleCopy.indexOf('h')) {
					$iframeDocument
						.find('html')
						.attr('style', $pageHtml.style.cssText);
				}
			}

			appendContent($printBody, options.header);

			if (options.canvas) {
				var canvasIndex = 0;

				$elements.find('canvas').addBack('canvas').each(function () {
					$(this).attr('data-printthis', canvasIndex++);
				});
			}

			copyMarkupIntoPrintBody($printBody, $elements, options);

			if (options.canvas) {
				$printBody.find('canvas').each(function () {
					var marker = $(this).data('printthis');
					var $sourceCanvas = $('[data-printthis="' + marker + '"]');

					this.getContext('2d').drawImage($sourceCanvas[0], 0, 0);

					if ($.isFunction($.fn.removeAttr)) {
						$sourceCanvas.removeAttr('data-printthis');
					} else {
						$.each($sourceCanvas, function (index, node) {
							node.removeAttribute('data-printthis');
						});
					}
				});
			}

			if (options.removeInline) {
				var inlineSelector = options.removeInlineSelector || '*';

				if ($.isFunction($.removeAttr)) {
					$printBody.find(inlineSelector).removeAttr('style');
				} else {
					$printBody.find(inlineSelector).attr('style', '');
				}
			}

			appendContent($printBody, options.footer);
			bindBeforePrintEvent($iframe, options.beforePrintEvent);

			setTimeout(function () {
				if ($iframe.hasClass('MSIE')) {
					window.frames.printIframe.focus();
					$printHead.append('<script>  window.print(); <\/script>');
				} else if (document.queryCommandSupported('print')) {
					$iframe[0].contentWindow.document.execCommand(
						'print',
						false,
						null
					);
				} else {
					$iframe[0].contentWindow.focus();
					$iframe[0].contentWindow.print();
				}

				if (!options.debug) {
					setTimeout(function () {
						$iframe.remove();
					}, 1000);
				}

				if ('function' === typeof options.afterPrint) {
					options.afterPrint();
				}
			}, options.printDelay);
		}, 333);

		return this;
	};

	$.fn.printThis.defaults = {
		debug: false,
		importCSS: true,
		importStyle: true,
		printContainer: true,
		loadCSS: '',
		pageTitle: '',
		removeInline: false,
		removeInlineSelector: '*',
		printDelay: 1000,
		header: null,
		footer: null,
		base: false,
		formValues: true,
		canvas: true,
		doctypeString: '<!DOCTYPE html>',
		removeScripts: false,
		copyTagClasses: true,
		copyTagStyles: true,
		beforePrintEvent: null,
		beforePrint: null,
		afterPrint: null,
	};
})(jQuery);
