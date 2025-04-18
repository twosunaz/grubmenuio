(function ($, document) {
  const classes = {
    iconButton: 'orderable-print-icon-button',
    printer: 'orderable-print-icon-button__printer',
    printerIsHide: 'orderable-print-icon-button__printer--is-hidden',
    loading: 'orderable-print-icon-button__loading',
    loadingIsActive: 'orderable-print-icon-button__loading--is-active',
    optionsButton: 'orderable-receipt-layouts__receipt-layout-options-button',
    optionsButtonIsOpen: 'orderable-receipt-layouts__receipt-layout-options-button--is-open',
    optionsList: 'orderable-receipt-layouts__receipt-layout-options-list',
    optionsListIsOpen: 'orderable-receipt-layouts__receipt-layout-options-list--is-open',
    optionPrintLink: 'orderable-receipt-layouts__receipt-layout-option-print-link',
    optionPrintButton: 'orderable-receipt-layouts__receipt-layout-option-print-button',
    optionPrintButtonIsLoading: 'orderable-receipt-layouts__receipt-layout-option-print-button--is-loading',
    loadingOptionPrintButton: 'orderable-receipt-layouts__receipt-layout-option-loading',
    loadingOptionPrintButtonIsActive: 'orderable-receipt-layouts__receipt-layout-option-loading--is-active'
  };

  /**
   * Add Print buttons before Preview buton
   */
  function addPrintButtons() {
    const $orderPreviewButton = $('.order-preview');
    $orderPreviewButton.each(function () {
      const orderId = $(this).attr('data-order-id');
      if (!orderId) {
        return;
      }
      $(this).after(`<a
					href="#"
					class="${classes.iconButton}"
					data-order-id="${orderId}"
					title="${wp.i18n.__('Print', 'orderable')}"
				>
					<span class="${classes.loading} spinner"></span>
					<span class="${classes.printer} dashicons dashicons-printer"></span>
				</a>`);
    });
  }

  /**
   * Get the receipt URL
   *
   * @param {number} orderId
   * @param {number} receiptLayoutId
   * @return Promise
   */
  function getReceiptURL(orderId, receiptLayoutId) {
    const layoutId = receiptLayoutId || orderableReceiptLayouts.receiptLayoutId;
    return wp.apiFetch({
      path: `wc/v3/orders/${orderId}/receipt?force_new=true&orderable_layout_id=${layoutId}`,
      method: 'POST'
    }).then(response => {
      const receiptUrl = response?.receipt_url;
      if (wp.url.isURL(receiptUrl)) {
        return receiptUrl;
      }
    });
  }

  /**
   * Open URL in new tab
   * @param {string} url
   */
  function openInNewTab(url) {
    if (!url) {
      return;
    }
    window.open(url, '_blank');
  }

  /**
   * Handle Print button click
   *
   * @param {Event} event
   */
  function onPrintButtonClick(event) {
    event.preventDefault();
    const $iconButton = $(this);
    const orderId = $iconButton.attr('data-order-id');
    const $loadingIcon = $iconButton.find(`.${classes.loading}`);
    const $printerIcon = $iconButton.find(`.${classes.printer}`);
    if ($loadingIcon.hasClass(classes.loadingIsActive)) {
      return;
    }
    const setLoading = value => {
      if (value) {
        $loadingIcon.addClass(classes.loadingIsActive);
        $printerIcon.addClass(classes.printerIsHide);
      } else {
        $loadingIcon.removeClass(classes.loadingIsActive);
        $printerIcon.removeClass(classes.printerIsHide);
      }
    };
    setLoading(true);
    getReceiptURL(orderId).then(openInNewTab).finally(() => {
      setLoading(false);
    });
  }

  /**
   * Handle option Print button click
   *
   * @param {Event} event
   */
  function onOptionPrintButtonClick(event) {
    event.preventDefault();
    const $optionPrintButton = $(this);
    const $loading = $(this).siblings(`.${classes.loadingOptionPrintButton}`);
    const orderId = $optionPrintButton.attr('data-order-id');
    const receiptLayoutId = $optionPrintButton.attr('data-receipt-layout-id');
    const setLoading = value => {
      if (value) {
        $optionPrintButton.prop('disabled', true);
        $optionPrintButton.addClass(classes.optionPrintButtonIsLoading);
        $loading.addClass(classes.loadingOptionPrintButtonIsActive);
      } else {
        $optionPrintButton.prop('disabled', false);
        $optionPrintButton.removeClass(classes.optionPrintButtonIsLoading);
        $loading.removeClass(classes.loadingOptionPrintButtonIsActive);
      }
    };
    setLoading(true);
    getReceiptURL(orderId, receiptLayoutId).then(openInNewTab).finally(() => {
      setLoading(false);
      closeList();
    });
  }

  /**
   * Handle option Print link click
   */
  function onOptionPrintLinkClick() {
    closeList();
  }

  /**
   * Close the list of receipt layouts
   *
   * @param {Object} $optionButton
   */
  function closeList($optionButton) {
    $optionButton = $optionButton || $(`.${classes.optionsButton}`);
    $optionButton.removeClass(classes.optionsButtonIsOpen);
    $optionButton.siblings(`.${classes.optionsList}`).removeClass(classes.optionsListIsOpen);
  }

  /**
   * Close the list of receipt layouts
   *
   * @param {Object} $optionButton
   */
  function openList($optionButton) {
    $optionButton.addClass(classes.optionsButtonIsOpen);
    $optionButton.siblings(`.${classes.optionsList}`).addClass(classes.optionsListIsOpen);
  }

  /**
   * Handle options button click.
   *
   * It opens the list of receipt layouts
   *
   * @param {Event} event
   */
  function onOptionsButtonClick(event) {
    event.preventDefault();
    const isOpen = $(this).hasClass(classes.optionsButtonIsOpen);
    if (isOpen) {
      closeList($(this));
    } else {
      openList($(this));
    }
  }
  $(document).ready(function () {
    addPrintButtons();
    $(`.${classes.iconButton}`).on('click', onPrintButtonClick);
    $(document.body).on('click', `.${classes.optionPrintLink}`, onOptionPrintLinkClick);
    $(document.body).on('click', `.${classes.optionPrintButton}`, onOptionPrintButtonClick);
    $(document.body).on('click', `.${classes.optionsButton}`, onOptionsButtonClick);
  });
})(jQuery, document);