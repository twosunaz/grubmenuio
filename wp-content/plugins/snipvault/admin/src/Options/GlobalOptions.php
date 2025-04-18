<?php
/**
 * GlobalOptions Class
 *
 * Handles registration and management of global plugin settings.
 * This class registers the settings with WordPress and provides
 * sanitization methods for user input.
 *
 * @package SnipVault\Options
 * @since 3.2.13
 */
namespace SnipVault\Options;

// Prevent direct access to this file
defined("ABSPATH") || exit();

/**
 * Class GlobalOptions
 *
 * Registers and manages global options for the SnipVault plugin.
 * This includes license key and instance ID management through
 * the WordPress Settings API and REST API.
 *
 * @package SnipVault\Options
 * @since 3.2.13
 */
class GlobalOptions
{
  /**
   * GlobalOptions constructor.
   *
   * Sets up WordPress hooks to register settings during admin initialization
   * and REST API initialization.
   *
   * @return void
   * @since 3.2.13
   */
  public function __construct()
  {
    add_action("admin_init", ["SnipVault\Options\GlobalOptions", "create_global_option"]);
    add_action("rest_api_init", ["SnipVault\Options\GlobalOptions", "create_global_option"]);
  }

  /**
   * Creates global option
   *
   * Registers the plugin settings with WordPress using the Settings API.
   * Defines the data schema, default values, and REST API exposure.
   *
   * @return void
   * @since 3.2.13
   */
  public static function create_global_option()
  {
    $args = [
      "type" => "object",
      "sanitize_callback" => ["SnipVault\Options\GlobalOptions", "sanitize_global_settings"],
      "default" => [],
      "capability" => "manage_options",
      "show_in_rest" => [
        "schema" => [
          "type" => "object",
          "properties" => [
            "license_key" => [
              "type" => "string",
            ],
            "instance_id" => [
              "type" => "string",
            ],
            "anthropic_key" => [
              "type" => "string",
            ],
            "file_browsing_disabled" => [
              "type" => "boolean",
              "default" => false,
            ],
            "sql_editor_disabled" => [
              "type" => "boolean",
              "default" => false,
            ],
            "wp_sql_disabled" => [
              "type" => "boolean",
              "default" => false,
            ],
            "code_font_size" => [
              "type" => "number",
              "default" => 22,
            ],
            "php_global_variables" => [
              "type" => "array",
              "default" => [],
            ],
          ],
        ],
      ],
    ];
    register_setting("snipvault", "snipvault_settings", $args);
  }

  /**
   * Sanitizes the global settings values
   *
   * Performs sanitization on user input for global settings before saving to database.
   * Maintains existing option values that are not being updated in the current request.
   *
   * @param array $value The raw input values to be sanitized
   * @return array The sanitized values merged with existing options
   * @since 3.2.13
   */
  public static function sanitize_global_settings($value)
  {
    $sanitized_value = [];
    $options = get_option("snipvault_settings", false);
    $options = !$options ? [] : $options;

    if (isset($value["license_key"])) {
      $sanitized_value["license_key"] = sanitize_text_field($value["license_key"]);
    }
    if (isset($value["instance_id"])) {
      $sanitized_value["instance_id"] = sanitize_text_field($value["instance_id"]);
    }
    if (isset($value["anthropic_key"])) {
      $sanitized_value["anthropic_key"] = sanitize_text_field($value["anthropic_key"]);
    }
    if (isset($value["file_browsing_disabled"])) {
      $sanitized_value["file_browsing_disabled"] = (bool) $value["file_browsing_disabled"];
    }
    if (isset($value["sql_editor_disabled"])) {
      $sanitized_value["sql_editor_disabled"] = (bool) $value["sql_editor_disabled"];
    }
    if (isset($value["wp_sql_disabled"])) {
      $sanitized_value["wp_sql_disabled"] = (bool) $value["wp_sql_disabled"];
    }
    if (isset($value["code_font_size"])) {
      $sanitized_value["code_font_size"] = (int) $value["code_font_size"];
    }

    // Handle PHP global variables
    if (isset($value["php_global_variables"]) && is_array($value["php_global_variables"])) {
      $sanitized_global_vars = [];

      foreach ($value["php_global_variables"] as $variable) {
        if (isset($variable["key"]) && isset($variable["value"])) {
          // Sanitize the key to ensure it's a valid PHP variable name
          $key = preg_replace("/[^a-zA-Z0-9_]/", "", sanitize_text_field($variable["key"]));

          // Only add if key is not empty after sanitization
          if (!empty($key)) {
            $sanitized_global_vars[] = [
              "key" => $key,
              "value" => sanitize_text_field($variable["value"]),
            ];
          }
        }
      }

      $sanitized_value["php_global_variables"] = $sanitized_global_vars;
    }

    return array_merge($options, $sanitized_value);
  }
}
