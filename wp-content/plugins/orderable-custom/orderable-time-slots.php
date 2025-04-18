<?php
/**
 * Plugin Name: Orderable Time Slots Settings
 * Description: Adds time slot settings inside Orderable's WooCommerce tab using checkboxes.
 * Version: 1.2
 * Author: Twosun
 */

add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) {
        error_log('❌ WooCommerce not active.');
        return;
    }

    // Inject time slot settings into Orderable's tab
    add_filter('woocommerce_get_settings_orderable', function ($settings) {
        error_log('✅ Injecting Orderable time slot settings with checkboxes.');

        $timeslot_choices = get_orderable_timeslot_choices();

        $injected = [
            [
                'title' => __('Time Slot Settings', 'orderable'),
                'type'  => 'title',
                'id'    => 'orderable_time_slots_title',
            ],
            [
                'title'    => __('Available Time Slots', 'orderable'),
                'desc'     => __('Check the time slots you want to make available.', 'orderable'),
                'id'       => 'orderable_time_slots_checkbox',
                'type'     => 'checkboxes',
                'default'  => [],
                'choices'  => $timeslot_choices,
            ],
            [
                'type' => 'sectionend',
                'id'   => 'orderable_time_slots_end',
            ]
        ];

        return array_merge($settings, $injected);
    });
});

// Define available choices for checkboxes
function get_orderable_timeslot_choices() {
    return [
        '11' => '11AM',
        '16' => '4PM',
        '20' => '8PM',
        '22' => '10PM',
        '8'  => '8AM',
    ];
}

function get_orderable_timeslot_labels() {
    $settings = get_option('orderable_settings');
    $selected = isset($settings['time_slots_time_slots_section_custom_time_slots']) ? (array) $settings['time_slots_time_slots_section_custom_time_slots'] : [];
    $allChoices = array(
        '00' => '12AM',  '01' => '1AM',   '02' => '2AM',   '03' => '3AM',   '04' => '4AM',
        '05' => '5AM',   '06' => '6AM',   '07' => '7AM',   '08' => '8AM',   '09' => '9AM',
        '10' => '10AM',  '11' => '11AM',  '12' => '12PM',  '13' => '1PM',   '14' => '2PM',
        '15' => '3PM',   '16' => '4PM',   '17' => '5PM',   '18' => '6PM',   '19' => '7PM',
        '20' => '8PM',   '21' => '9PM',   '22' => '10PM',  '23' => '11PM',
    );

    $output = [];

    foreach ($selected as $key) {
        if (isset($allChoices[$key])) {
            $output[$key] = $allChoices[$key];
        }
    }
    error_log(print_r(get_option('orderable_settings'), true));
    error_log('🧠 get_orderable_timeslot_labels(): ' . print_r($output, true));
    return $output;
}

