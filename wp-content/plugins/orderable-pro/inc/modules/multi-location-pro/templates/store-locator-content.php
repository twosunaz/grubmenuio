<?php
/**
 * Store locator popup content — modified to select a time instead of zip.
 *
 * @package Orderable_Pro
 **/

// Handle AJAX-based time storage
add_action('wp_ajax_orderable_store_time_slot', 'store_time_slot');
add_action('wp_ajax_nopriv_orderable_store_time_slot', 'store_time_slot');
error_log('🧠 store_time_slot() function file loaded.');
function store_time_slot() {
    error_log('🧠 Current orderable_settings: ' . print_r(get_option('orderable_settings'), true));
    error_log('📩 Received AJAX request');
    error_log('🧪 Raw POST: ' . print_r($_POST, true));

    if (!isset($_POST['time']) || !isset($_POST['_ajax_nonce'])) {
        error_log('❌ Missing fields!');
        wp_send_json_error('Missing required fields');
    }

    if (!wp_verify_nonce($_POST['_ajax_nonce'], 'orderable_time_nonce')) {
        error_log('❌ Nonce verification failed: ' . $_POST['_ajax_nonce']);
        wp_send_json_error('Invalid nonce');
    }

    $time = sanitize_text_field($_POST['time']);
    WC()->session->set('orderable_table_number', $time); // your custom slot

    // 🧠 Emulate Orderable's session structure
    WC()->session->set('orderable_multi_location_postcode', $time);
    WC()->session->set('orderable_multi_location_id', '');
    WC()->session->set('orderable_multi_location_delivery_type', '');

    error_log('✅ Time saved to session: ' . $time);
    wp_send_json_success();
}

$location = Orderable_Multi_Location_Pro::get_selected_location_data_from_session();
$selected_table = ! empty( $location['postcode'] ) ? $location['postcode'] : '';
error_log('🧠 get_orderable_timeslot_labels(): ' . print_r(get_orderable_timeslot_labels(), true));
$tables = get_orderable_timeslot_labels(); // replaces the old hardcoded array

?>

<style>
/* Scoped CSS */
.opml-store-locator--table .opml-store-locator-input::before {
    display: none !important;
    content: none !important;
}
.opml-store-locator--table .opml-store-locator-input__label {
    display: none !important;
    top: 10px !important;
    left: 16px !important;
    font-size: 14px !important;
    pointer-events: none;
}
.opml-store-locator--table select.opml-store-locator-input__input {
    width: 100%;
    padding: 0 0 0 16px;
    height: 60px;
    border-radius: 47px;
    border: 2px solid #c7d4db;
    background: #fff;
    appearance: none;
    font-size: 16px;
}
.opml-store-locator__submit-btn {
    background-color: #24242d;
    color: #fff;
    padding: 16px 32px;
    border: none;
    border-radius: 999px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    margin-top: 1rem;
    transition: background-color 0.2s ease;
}
.opml-store-locator__submit-btn:hover {
    background-color: #3b3b47;
}
.opml-store-locator__submit-btn:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(36, 36, 45, 0.5);
}
.opml-store-locator.is-hidden,
.opml-popup.is-hidden {
    display: none !important;
}

</style>

<?php if ( empty($selected_table) ) : ?>
<div class="opml-store-locator opml-store-locator--table">
    <div class="opml-store-locator__wrap">
        <h2 class="opml-store-locator__heading"><?php esc_html_e( 'Select your time', 'orderable-pro' ); ?></h2>
        <p class="opml-store-locator__content"><?php esc_html_e( 'Please select your delivery time.', 'orderable-pro' ); ?></p>

        <form method="post" class="orderable-table-selection-form">
            <div class="opml-store-locator__input">
                <div class="opml-store-locator-input">
                    <label class="opml-store-locator-input__label"><?php esc_html_e( 'Choose Time', 'orderable-pro' ); ?></label>
                    <select class="opml-store-locator-input__input" name="orderable_table_select" required>
                        <option value=""><?php esc_html_e( 'Select a time...', 'orderable-pro' ); ?></option>
                        <?php foreach ( $tables as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_table, $value ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="opml-store-locator__submit">
                <button type="submit" class="opml-store-locator__submit-btn"><?php esc_html_e( 'Confirm Time', 'orderable-pro' ); ?></button>
            </div>
        </form>

        <div class="opml-store-locator__results" style="display:none;"></div>
    </div>
</div>
<?php endif; ?>
<script>
window.orderableAjax = {
    ajax_url: "<?php echo admin_url('admin-ajax.php'); ?>",
    nonce: "<?php echo wp_create_nonce('orderable_time_nonce'); ?>"
};
</script>
<script>
window.orderableAjax = {
    ajax_url: "<?php echo admin_url('admin-ajax.php'); ?>",
    nonce: "<?php echo wp_create_nonce('orderable_time_nonce'); ?>",
};

jQuery(document).ready(function ($) {
    // 🔎 Debugging submit event binding
    $('.orderable-table-selection-form').on('submit', function () {
        console.log('🧪 [DEBUG] Native .on submit event fired');
    });

    // Original handler with fix applied
    $(document).on('submit', '.orderable-table-selection-form', function (e) {
        console.log('🧪 [CHECK] .on("submit") triggered via delegated event');

        e.preventDefault();
        const select = this.querySelector('[name="orderable_table_select"]');

        if (!select || !select.value) {
            alert('Please select a time.');
            return;
        }

        $.ajax({
            url: window.orderableAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'orderable_store_time_slot',
                time: select.value,
                _ajax_nonce: window.orderableAjax.nonce,
            },
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            processData: true,
            success: function () {
                localStorage.setItem('orderable_time_slot', select.value);
                setTimeout(() => {
                    $('.opml-popup, .opml-store-locator').fadeOut(200);
                }, 150);
                $('.opml-store-locator__submit-btn').html('<span class="spinner"></span> Saving...').prop('disabled', true);

            },
            error: function (xhr) {
                console.error('❌ Failed to store time:', xhr.responseText);
                $('.opml-store-locator__submit-btn').text('Try Again').prop('disabled', false);
            }
        });
    });

    $(document).on('submit', '.orderable-drawer form.cart', function () {
        const storedTime = localStorage.getItem('orderable_time_slot');
        if (storedTime) {
            $(this).find('input[name="orderable_table_select"]').remove();
            $(this).append('<input type="hidden" name="orderable_table_select" value="' + storedTime + '" />');
            console.log('🛒 Injected time into Add to Cart form:', storedTime);
        } else {
            console.warn('⚠️ No stored time found in localStorage!');
        }
    });

    // Inject time into targeted AJAX calls
    $(document).ajaxSend(function (event, jqxhr, settings) {
        const timeSlot = localStorage.getItem('orderable_time_slot');
        if (!timeSlot || !settings || !settings.url) return;

        const targets = ['orderable_add_to_cart', 'orderable_get_product_options'];
        const isTargeted = targets.some(endpoint => settings.url.includes(endpoint));
        if (isTargeted && settings.type === 'POST' && typeof settings.data === 'string') {
            if (!settings.data.includes('orderable_table_select')) {
                settings.data += '&orderable_table_select=' + encodeURIComponent(timeSlot);
                console.log('🧩 Injected time into AJAX call:', settings.url, '->', timeSlot);
            }
        }
    });
});
</script>

