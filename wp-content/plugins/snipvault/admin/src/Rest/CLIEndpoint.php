<?php
namespace SnipVault\Rest;

// Prevent direct access to this file
defined("ABSPATH") || exit();

/**
 * Class CLIEndpoint
 *
 * Provides REST API access to CLI commands with appropriate security measures
 * using a simple shell-based approach to execute WP-CLI commands.
 *
 * @package SnipVault\Rest
 */
class CLIEndpoint
{
  /**
   * Option name for storing CLI activation state
   */
  const ACTIVATION_OPTION = "snipvault_cli_activation";

  /**
   * Constructor
   */
  public function __construct()
  {
    // Register activation endpoints (always available)
    add_action("rest_api_init", [$this, "register_activation_endpoints"]);

    // Register CLI command endpoint (only when activated)
    add_action("rest_api_init", [$this, "check_and_register_command_endpoint"]);
  }

  /**
   * Register activation/deactivation endpoints
   */
  public function register_activation_endpoints()
  {
    // Enable CLI (admin only)
    register_rest_route("snipvault/v1", "/cli/enable", [
      "methods" => "POST",
      "callback" => [$this, "enable_cli"],
      "permission_callback" => [$this, "check_admin_permission"],
    ]);

    // Disable CLI (admin only)
    register_rest_route("snipvault/v1", "/cli/disable", [
      "methods" => "POST",
      "callback" => [$this, "disable_cli"],
      "permission_callback" => [$this, "check_admin_permission"],
    ]);

    // Check status of CLI (admin only)
    register_rest_route("snipvault/v1", "/cli/status", [
      "methods" => "GET",
      "callback" => [$this, "get_cli_status"],
      "permission_callback" => [$this, "check_admin_permission"],
    ]);
  }

  /**
   * Register command execution endpoint (only when activated)
   */
  public function check_and_register_command_endpoint()
  {
    if ($this->is_explicitly_disabled()) {
      return;
    }

    $status = $this->get_activation_status();

    if (!$status["active"]) {
      return;
    }

    // Get available commands
    register_rest_route("snipvault/v1", "/cli/commands", [
      "methods" => "GET",
      "callback" => [$this, "get_available_commands"],
      "permission_callback" => [$this, "check_admin_permission"],
    ]);
  }

  /**
   * Checks if cli has been disabled by user
   *
   * @return bool
   */
  private static function is_explicitly_disabled()
  {
    // Explicitly disabled
    $options = get_option("snipvault_settings", []);
    if (isset($options["wp_sql_disabled"]) && $options["wp_sql_disabled"]) {
      return true;
    }
    return false;
  }

  /**
   * Get available CLI commands
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function get_available_commands(\WP_REST_Request $request)
  {
    // Get core commands from predefined list
    $commands = [];

    // Try to get actual commands from WP-CLI if possible

    global $wpdb;

    return rest_ensure_response([
      "success" => true,
      "format" => "list",
      "commands" => $commands,
      "info" => [
        "php_version" => PHP_VERSION,
        "wp_version" => get_bloginfo("version"),
        "mysql_version" => $wpdb->db_version(),
        "note" => "These are commonly used commands. For a complete list, use 'wp help' in the terminal.",
      ],
    ]);
  }
  /**
   * Check if user is an administrator
   *
   * @return bool
   */
  public function check_admin_permission()
  {
    return current_user_can("administrator");
  }

  /**
   * Enable the CLI for 1 hour
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function enable_cli(\WP_REST_Request $request)
  {
    if (version_compare(PHP_VERSION, "8.1.0", "<")) {
      return rest_ensure_response([
        "success" => false,
        "error" => "Unable to activate WP-CLI",
        "message" => "Requires PHP version 8.1 or higher",
      ]);
    }

    // Set activation with expiration timestamp (current time + 1 hour)
    $expiration = time() + 3600; // 1 hour = 3600 seconds
    $activation_data = [
      "active" => true,
      "expires" => $expiration,
      "activated_by" => get_current_user_id(),
      "activated_at" => time(),
      "ip_address" => $this->get_client_ip(),
    ];

    update_option(self::ACTIVATION_OPTION, $activation_data);

    return rest_ensure_response([
      "success" => true,
      "message" => "CLI access enabled for 1 hour",
      "expires" => $expiration,
      "expires_formatted" => date("Y-m-d H:i:s", $expiration),
    ]);
  }

  /**
   * Disable the CLI
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function disable_cli(\WP_REST_Request $request)
  {
    $previous = get_option(self::ACTIVATION_OPTION, ["active" => false]);

    // Deactivate
    $deactivation_data = [
      "active" => false,
      "deactivated_by" => get_current_user_id(),
      "deactivated_at" => time(),
      "ip_address" => $this->get_client_ip(),
    ];

    update_option(self::ACTIVATION_OPTION, $deactivation_data);

    return rest_ensure_response([
      "success" => true,
      "message" => "CLI access disabled",
      "was_active" => !empty($previous["active"]),
    ]);
  }

  /**
   * Get the current status of the CLI
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function get_cli_status(\WP_REST_Request $request)
  {
    $status = $this->get_activation_status();

    return rest_ensure_response([
      "active" => $status["active"],
      "expires" => $status["expires"] ?? null,
      "expires_formatted" => $status["expires"] ? date("Y-m-d H:i:s", $status["expires"]) : null,
      "time_remaining" => $status["time_remaining"] ?? 0,
      "time_remaining_formatted" => $status["time_remaining_formatted"] ?? "0 seconds",
    ]);
  }

  /**
   * Get activation status
   *
   * @return array
   */
  private function get_activation_status()
  {
    $default = ["active" => false];
    $status = get_option(self::ACTIVATION_OPTION, $default);

    // If no status or already marked inactive, return default
    if (empty($status) || empty($status["active"])) {
      return $default;
    }

    // Check if expired
    $now = time();
    if (!empty($status["expires"]) && $status["expires"] < $now) {
      // Auto-disable if expired
      $status["active"] = false;
      $status["expired_at"] = $now;
      update_option(self::ACTIVATION_OPTION, $status);

      return $default;
    }

    // Calculate remaining time
    if (!empty($status["expires"])) {
      $status["time_remaining"] = max(0, $status["expires"] - $now);
      $status["time_remaining_formatted"] = $this->format_time_remaining($status["time_remaining"]);
    }

    return $status;
  }

  /**
   * Format seconds into a human-readable time string
   *
   * @param int $seconds Number of seconds
   * @return string Formatted time string
   */
  private function format_time_remaining($seconds)
  {
    if ($seconds <= 0) {
      return "0 seconds";
    }

    $minutes = floor($seconds / 60);
    $remaining_seconds = $seconds % 60;

    if ($minutes <= 0) {
      return "$remaining_seconds seconds";
    }

    return "$minutes minutes, $remaining_seconds seconds";
  }

  /**
   * Get client IP address
   *
   * @return string IP address
   */
  private function get_client_ip()
  {
    // Various headers to check for client IP
    $headers = [
      "HTTP_CF_CONNECTING_IP", // Cloudflare
      "HTTP_CLIENT_IP",
      "HTTP_X_FORWARDED_FOR",
      "HTTP_X_FORWARDED",
      "HTTP_X_CLUSTER_CLIENT_IP",
      "HTTP_FORWARDED_FOR",
      "HTTP_FORWARDED",
      "REMOTE_ADDR",
    ];

    foreach ($headers as $header) {
      if (!empty($_SERVER[$header])) {
        $ip = $_SERVER[$header];
        // If comma-separated list (X-Forwarded-For can be), get first IP
        if (strpos($ip, ",") !== false) {
          $ip = trim(explode(",", $ip)[0]);
        }
        // Validate IP format (both IPv4 and IPv6)
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
          return $ip;
        }
      }
    }

    return "Unknown";
  }
}
