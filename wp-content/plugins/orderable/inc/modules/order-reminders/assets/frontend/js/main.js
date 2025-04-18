(function ($, document) {
  $('.orderable-order-date-time-reminders-modal__cancel').on('click', function () {
    $('.orderable-order-date-time-reminders-modal').addClass('orderable-order-date-time-reminders-modal--hidden');
  });
  $('.orderable-order-date-time-reminders-modal__select').on('change', function () {
    const selectedDate = $('.orderable-order-date-time-reminders-modal__date-field').val();
    let slots = $('.orderable-order-date-time-reminders-modal__date-field').find('option:selected').attr('data-orderable-slots');
    let isOrderDateSelected = selectedDate;
    slots = slots && JSON.parse(slots);
    if (!slots || 'all-day' === slots?.[0]?.value) {
      $('.orderable-order-date-time-reminders-modal__time').hide();
    }
    if (slots && 'all-day' !== slots?.[0]?.value) {
      const selectedTime = $('.orderable-order-date-time-reminders-modal__time-field').val();
      $('.orderable-order-date-time-reminders-modal__time').show();
      isOrderDateSelected = !!(selectedDate && selectedTime);
    }
    if (isOrderDateSelected) {
      $('.orderable-order-date-time-reminders-modal__save').prop('disabled', false);
      return;
    }
    $('.orderable-order-date-time-reminders-modal__save').prop('disabled', true);
  });
  $('.orderable-order-date-time-reminders-modal__date-field').on('change', function () {
    let slots = $(this).find('option:selected').attr('data-orderable-slots');
    slots = slots && JSON.parse(slots);
    if (slots && 'all-day' !== slots?.[0]?.value) {
      $('.orderable-order-date-time-reminders-modal__save').prop('disabled', true);
    }
  });
  $('.orderable-order-date-time-reminders-modal__select').trigger('change');
})(jQuery, document);