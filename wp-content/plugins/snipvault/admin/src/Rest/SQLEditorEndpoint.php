<?php
namespace SnipVault\Rest;

// Prevent direct access to this file
defined("ABSPATH") || exit();

/**
 * Class SQLEditorEndpoint
 *
 * Provides REST API access to run SQL queries with appropriate security measures
 * and time-based activation controls.
 *
 * @package SnipVault\Rest
 */
class SQLEditorEndpoint
{
  /**
   * Option name for storing SQL Editor activation state
   */
  const ACTIVATION_OPTION = "snipvault_sql_editor_activation";

  /**
   * Option name for storing SQL Editor audit log
   */
  const AUDIT_LOG_OPTION = "snipvault_sql_editor_audit_log";

  /**
   * Maximum number of audit log entries to keep
   */
  const MAX_AUDIT_LOG_ENTRIES = 100;

  /**
   * Maximum length of individual SQL queries to store in audit log
   */
  const MAX_QUERY_LOG_LENGTH = 1000;

  /**
   * Constructor
   */
  public function __construct()
  {
    // Register activation endpoints (always available)
    add_action("rest_api_init", [$this, "register_activation_endpoints"]);

    // Register SQL query endpoint (only when activated)
    add_action("rest_api_init", [$this, "check_and_register_query_endpoint"]);
  }

  /**
   * Register activation/deactivation endpoints
   */
  public function register_activation_endpoints()
  {
    // Enable SQL Editor (admin only)
    register_rest_route("snipvault/v1", "/sql/enable", [
      "methods" => "POST",
      "callback" => [$this, "enable_sql_editor"],
      "permission_callback" => [$this, "check_admin_permission"],
      "args" => [
        "duration" => [
          "required" => false,
          "type" => "integer",
          "default" => 3600,
          "sanitize_callback" => function ($param) {
            // Limit duration to between 5 minutes and 1 hours
            return max(300, min(3600, intval($param)));
          },
        ],
      ],
    ]);

    // Disable SQL Editor (admin only)
    register_rest_route("snipvault/v1", "/sql/disable", [
      "methods" => "POST",
      "callback" => [$this, "disable_sql_editor"],
      "permission_callback" => [$this, "check_admin_permission"],
    ]);

    // Check status of SQL Editor (admin only)
    register_rest_route("snipvault/v1", "/sql/status", [
      "methods" => "GET",
      "callback" => [$this, "get_sql_editor_status"],
      "permission_callback" => [$this, "check_admin_permission"],
    ]);

    // View audit log (admin only)
    register_rest_route("snipvault/v1", "/sql/audit-log", [
      "methods" => "GET",
      "callback" => [$this, "get_audit_log"],
      "permission_callback" => [$this, "check_admin_permission"],
      "args" => [
        "limit" => [
          "required" => false,
          "type" => "integer",
          "default" => 20,
          "sanitize_callback" => function ($param) {
            return max(1, min(self::MAX_AUDIT_LOG_ENTRIES, intval($param)));
          },
        ],
      ],
    ]);
  }

  /**
   * Register SQL query execution endpoint (only when activated)
   */
  public function check_and_register_query_endpoint()
  {
    $status = $this->get_activation_status();
    $disabled = $this->is_explicitly_disabled();

    if (!$status["active"] || $disabled) {
      return;
    }

    // SQL Query execution endpoint
    register_rest_route("snipvault/v1", "/sql/query", [
      "methods" => "POST",
      "callback" => [$this, "execute_sql_query"],
      "permission_callback" => [$this, "check_admin_permission"],
      "args" => [
        "query" => [
          "required" => true,
          "type" => "string",
          "sanitize_callback" => "sanitize_textarea_field", // Better for SQL
        ],
        "params" => [
          "required" => false,
          "type" => "array",
          "default" => [],
        ],
        "force_confirm" => [
          "required" => false,
          "type" => "boolean",
          "default" => false,
        ],
        "skip_security_check" => [
          "required" => false,
          "type" => "boolean",
          "default" => false,
        ],
      ],
    ]);

    // Get database tables info
    register_rest_route("snipvault/v1", "/sql/tables", [
      "methods" => "GET",
      "callback" => [$this, "get_database_tables"],
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
    if (isset($options["sql_editor_disabled"]) && $options["sql_editor_disabled"]) {
      return true;
    }
    return false;
  }

  /**
   * Execute an SQL query
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function execute_sql_query(\WP_REST_Request $request)
  {
    global $wpdb;

    // Get the query from the request
    $raw_query = $request->get_param("query");
    $params = $request->get_param("params");
    $force_confirm = $request->get_param("force_confirm");
    $skip_security_check = $request->get_param("skip_security_check");

    // Normalize query (remove excessive whitespace, normalize line breaks)
    $query = $this->normalize_query($raw_query);

    // Only do this if wp_ is actually in the query and the prefix isn't wp_
    if (strpos($query, "wp_") !== false && $wpdb->prefix !== "wp_") {
      $original_query = $query;
      $query = str_replace("wp_", $wpdb->prefix, $query);
    }

    // Also ensure the database name is properly referenced
    if (strpos($query, $wpdb->dbname . ".wp_") !== false) {
      $query = str_replace($wpdb->dbname . ".wp_", $wpdb->dbname . "." . $wpdb->prefix, $query);
    }

    // First determine the query type (with better detection)
    $query_type = $this->determine_query_type($query);

    // Security check bypassed with both flags (requires double confirmation)
    $bypass_security = $force_confirm && $skip_security_check;

    // If security checks are not bypassed
    if (!$bypass_security) {
      // For UPDATE statements, only check for multiple queries
      if ($query_type === "update") {
        // Check for multiple queries in UPDATE statement
        if (preg_match("/;.+/s", $query)) {
          return new \WP_Error("dangerous_query", "Multiple queries detected. Please execute one query at a time.", ["status" => 403]);
        }
      }
      // For potentially dangerous query types, require explicit confirmation
      elseif (in_array($query_type, ["alter", "drop", "truncate", "create", "grant"])) {
        if (!$force_confirm) {
          return new \WP_Error("dangerous_query", "This query contains potentially dangerous operations ({$query_type}). Please confirm to proceed.", [
            "status" => 403,
            "requires_confirmation" => true,
          ]);
        }
      }
      // For other query types, check for dangerous patterns
      else {
        // Basic security check - block DROP, ALTER, CREATE, TRUNCATE, GRANT
        $dangerous_patterns = [
          "/\bDROP\b/i",
          "/\bALTER\b/i",
          "/\bCREATE\b/i",
          "/\bTRUNCATE\b/i",
          "/\bGRANT\b/i",
          "/\bREVOKE\b/i",
          "/\bDELETE\b\s+(?!FROM)/i", // Allow DELETE FROM but not just DELETE
          "/;.+/s", // Prevent multiple queries
        ];

        foreach ($dangerous_patterns as $pattern) {
          if (preg_match($pattern, $query) && !$force_confirm) {
            return new \WP_Error("dangerous_query", "This query contains potentially dangerous operations that are not allowed.", ["status" => 403, "requires_confirmation" => true]);
          }
        }
      }
    }

    $results = [];
    $error = null;
    $execution_start = microtime(true);

    try {
      // Execute query based on type
      if ($query_type === "select") {
        // For SELECT queries
        if (!empty($params)) {
          $prepared_query = $wpdb->prepare($query, $params);
          $results = $wpdb->get_results($prepared_query, ARRAY_A);
        } else {
          // Even without parameters, use $wpdb methods for security
          $results = $wpdb->get_results($query, ARRAY_A);
        }

        if ($wpdb->last_error) {
          throw new \Exception($wpdb->last_error);
        }

        // Get column information
        $column_names = [];
        if (!empty($results)) {
          $column_names = array_keys($results[0]);
        }

        // Record the query in audit log
        $this->log_query($query, $params, $query_type, count($results), $bypass_security);

        return rest_ensure_response([
          "success" => true,
          "type" => $query_type,
          "columns" => $column_names,
          "rows" => $results,
          "rowCount" => count($results),
          "executionTime" => round(microtime(true) - $execution_start, 4) . "s",
        ]);
      } else {
        // For non-SELECT queries (INSERT, UPDATE, DELETE)
        if (!empty($params)) {
          $prepared_query = $wpdb->prepare($query, $params);
          $wpdb->query($prepared_query);
        } else {
          $wpdb->query($query);
        }

        if ($wpdb->last_error) {
          throw new \Exception($wpdb->last_error);
        }

        // Record the query in audit log
        $this->log_query($query, $params, $query_type, $wpdb->rows_affected, $bypass_security);

        return rest_ensure_response([
          "success" => true,
          "type" => $query_type,
          "affectedRows" => $wpdb->rows_affected,
          "message" => "Query executed successfully. Affected rows: " . $wpdb->rows_affected,
          "executionTime" => round(microtime(true) - $execution_start, 4) . "s",
        ]);
      }
    } catch (\Exception $e) {
      // Log failed query attempts
      $this->log_query($query, $params, $query_type, 0, $bypass_security, false, $e->getMessage());

      return new \WP_Error("query_error", $e->getMessage(), ["status" => 400]);
    }
  }

  /**
   * Get database tables information
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function get_database_tables(\WP_REST_Request $request)
  {
    global $wpdb;

    // Get all tables (using esc_sql for table names)
    $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
    $table_info = [];

    foreach ($tables as $table) {
      $table_name = $table[0];
      // Properly escape table name for SQL queries
      $escaped_table = esc_sql($table_name);

      // Get columns for this table (using escaped table name)
      $columns = $wpdb->get_results("DESCRIBE `{$escaped_table}`", ARRAY_A);

      // Get row count (using escaped table name)
      $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$escaped_table}`");

      $table_info[] = [
        "name" => $table_name,
        "columns" => $columns,
        "rowCount" => (int) $row_count,
        "prefix" => strpos($table_name, $wpdb->prefix) === 0,
      ];
    }

    // Log this action
    $this->log_query("SHOW TABLES + TABLE INFO", [], "metadata", count($table_info), false);

    return rest_ensure_response([
      "success" => true,
      "tables" => $table_info,
      "dbName" => $wpdb->dbname,
      "prefix" => $wpdb->prefix,
    ]);
  }

  /**
   * Normalize SQL query by removing excessive whitespace and normalizing line breaks
   *
   * @param string $query
   * @return string
   */
  private function normalize_query($query)
  {
    // Trim whitespace
    $query = trim($query);

    // Replace multiple spaces with a single space
    $query = preg_replace("/\s+/", " ", $query);

    // Remove comments
    $query = preg_replace('/--.*$/m', "", $query);
    $query = preg_replace("!/\*.*?\*/!s", "", $query);

    return trim($query);
  }

  /**
   * Determine SQL query type with improved detection
   *
   * @param string $query
   * @return string
   */
  private function determine_query_type($query)
  {
    // Remove any leading comments or whitespace that might be hiding the actual query
    $clean_query = preg_replace('/^\s*(\/\*.*?\*\/\s*|--.*?[\r\n]|#.*?[\r\n])+/s', "", $query);
    $clean_query = trim($clean_query);

    // Extract first word from cleaned query
    $first_word = "";
    if (preg_match('/^([A-Za-z]+)(?:\s|$)/', $clean_query, $matches)) {
      $first_word = strtoupper($matches[1]);
    }

    // Map common SQL commands to their types
    $query_types = [
      "SELECT" => "select",
      "INSERT" => "insert",
      "UPDATE" => "update",
      "DELETE" => "delete",
      "DROP" => "drop",
      "ALTER" => "alter",
      "CREATE" => "create",
      "TRUNCATE" => "truncate",
      "GRANT" => "grant",
      "REVOKE" => "revoke",
      "SHOW" => "metadata",
      "DESCRIBE" => "metadata",
      "EXPLAIN" => "metadata",
    ];

    return isset($query_types[$first_word]) ? $query_types[$first_word] : "other";
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
   * Enable the SQL Editor for specified duration (default: 1 hour)
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function enable_sql_editor(\WP_REST_Request $request)
  {
    // Get duration from request (already sanitized in route definition)
    $duration = $request->get_param("duration");
    $expiration = time() + $duration;

    // Set activation with expiration timestamp
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $user_display = $user ? $user->display_name : "Unknown";

    $activation_data = [
      "active" => true,
      "expires" => $expiration,
      "duration" => $duration,
      "activated_by" => $user_id,
      "activated_by_name" => $user_display,
      "activated_at" => time(),
      "ip_address" => $this->get_client_ip(),
    ];

    update_option(self::ACTIVATION_OPTION, $activation_data);

    // Log this activation
    $this->log_system_event("SQL Editor activated for " . $this->format_time_remaining($duration));

    // Send email notification if admin email is available
    $this->send_notification(
      "SQL Editor Activated",
      "The SQL Editor has been activated by {$user_display} and will remain active for " . $this->format_time_remaining($duration) . " (until " . date("Y-m-d H:i:s", $expiration) . ")."
    );

    return rest_ensure_response([
      "success" => true,
      "message" => "SQL Editor access enabled for " . $this->format_time_remaining($duration),
      "expires" => $expiration,
      "expires_formatted" => date("Y-m-d H:i:s", $expiration),
    ]);
  }

  /**
   * Disable the SQL Editor
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function disable_sql_editor(\WP_REST_Request $request)
  {
    $previous = get_option(self::ACTIVATION_OPTION, ["active" => false]);
    $was_active = !empty($previous["active"]);

    // Get user info
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $user_display = $user ? $user->display_name : "Unknown";

    // Deactivate
    $deactivation_data = [
      "active" => false,
      "deactivated_by" => $user_id,
      "deactivated_by_name" => $user_display,
      "deactivated_at" => time(),
      "ip_address" => $this->get_client_ip(),
      "previous_state" => $previous,
    ];

    update_option(self::ACTIVATION_OPTION, $deactivation_data);

    // Log this deactivation
    $this->log_system_event("SQL Editor manually deactivated");

    return rest_ensure_response([
      "success" => true,
      "message" => "SQL Editor access disabled",
      "was_active" => $was_active,
    ]);
  }

  /**
   * Get the current status of the SQL Editor
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function get_sql_editor_status(\WP_REST_Request $request)
  {
    $status = $this->get_activation_status();

    // Add activation history if available
    $full_status = $status;
    $stored_status = get_option(self::ACTIVATION_OPTION, []);

    if (!empty($stored_status["deactivated_at"])) {
      $full_status["last_deactivated"] = $stored_status["deactivated_at"];
      $full_status["last_deactivated_by"] = $stored_status["deactivated_by_name"] ?? "Unknown";
    }

    if (!empty($stored_status["activated_at"])) {
      $full_status["last_activated"] = $stored_status["activated_at"];
      $full_status["last_activated_by"] = $stored_status["activated_by_name"] ?? "Unknown";
    }

    return rest_ensure_response($full_status);
  }

  /**
   * Get SQL audit log
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response
   */
  public function get_audit_log(\WP_REST_Request $request)
  {
    $limit = $request->get_param("limit");
    $log = get_option(self::AUDIT_LOG_OPTION, []);

    // Return most recent entries first (up to limit)
    $log = array_slice(array_reverse($log), 0, $limit);

    return rest_ensure_response([
      "success" => true,
      "log" => $log,
      "total_entries" => count(get_option(self::AUDIT_LOG_OPTION, [])),
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

      // Record who activated it last
      if (!empty($status["activated_by"])) {
        $status["last_activated_by"] = $status["activated_by"];
        $status["last_activated_by_name"] = $status["activated_by_name"] ?? "Unknown";
      }

      update_option(self::ACTIVATION_OPTION, $status);

      // Log expiration
      $this->log_system_event("SQL Editor automatically deactivated (timeout)");

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

    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $remaining_seconds = $seconds % 60;

    $parts = [];

    if ($hours > 0) {
      $parts[] = "$hours " . ($hours == 1 ? "hour" : "hours");
    }

    if ($minutes > 0) {
      $parts[] = "$minutes " . ($minutes == 1 ? "minute" : "minutes");
    }

    if ($remaining_seconds > 0 && count($parts) == 0) {
      $parts[] = "$remaining_seconds " . ($remaining_seconds == 1 ? "second" : "seconds");
    }

    return implode(", ", $parts);
  }

  /**
   * Get client IP address with better validation
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

  /**
   * Log a SQL query to the audit log
   *
   * @param string $query The SQL query
   * @param array $params Query parameters
   * @param string $query_type Type of query
   * @param int $affected_rows Number of rows affected or returned
   * @param bool $security_bypassed Whether security checks were bypassed
   * @param bool $success Whether query was successful
   * @param string $error Error message if query failed
   */
  private function log_query($query, $params = [], $query_type = "other", $affected_rows = 0, $security_bypassed = false, $success = true, $error = "")
  {
    // Get user info
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $user_display = $user ? $user->display_name : "Unknown";

    // Truncate query if too long
    if (strlen($query) > self::MAX_QUERY_LOG_LENGTH) {
      $query = substr($query, 0, self::MAX_QUERY_LOG_LENGTH) . "... [truncated]";
    }

    // Create log entry
    $log_entry = [
      "timestamp" => time(),
      "date" => date("Y-m-d H:i:s"),
      "user_id" => $user_id,
      "user" => $user_display,
      "ip" => $this->get_client_ip(),
      "query_type" => $query_type,
      "query" => $query,
      "has_params" => !empty($params),
      "param_count" => count($params),
      "affected_rows" => $affected_rows,
      "security_bypassed" => $security_bypassed,
      "success" => $success,
    ];

    // Add error if present
    if (!empty($error)) {
      $log_entry["error"] = $error;
    }

    // Get existing log
    $log = get_option(self::AUDIT_LOG_OPTION, []);

    // Add new entry
    array_push($log, $log_entry);

    // Limit log size
    if (count($log) > self::MAX_AUDIT_LOG_ENTRIES) {
      $log = array_slice($log, -self::MAX_AUDIT_LOG_ENTRIES);
    }

    // Save updated log
    update_option(self::AUDIT_LOG_OPTION, $log);

    // Notify admin about potentially dangerous operations
    if ($security_bypassed || in_array($query_type, ["drop", "alter", "truncate", "create"])) {
      $this->send_notification(
        "Potentially Dangerous SQL Query Executed",
        "A potentially dangerous SQL query ({$query_type}) was executed by {$user_display}.\n\n" .
          "Query: {$query}\n" .
          "Security Bypassed: " .
          ($security_bypassed ? "Yes" : "No") .
          "\n" .
          "Affected Rows: {$affected_rows}\n" .
          "Time: " .
          date("Y-m-d H:i:s")
      );
    }
  }

  /**
   * Log system events to the audit log
   *
   * @param string $message Event message
   */
  private function log_system_event($message)
  {
    // Get user info
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $user_display = $user ? $user->display_name : "Unknown";

    // Create log entry
    $log_entry = [
      "timestamp" => time(),
      "date" => date("Y-m-d H:i:s"),
      "user_id" => $user_id,
      "user" => $user_display,
      "ip" => $this->get_client_ip(),
      "query_type" => "system",
      "query" => $message,
      "success" => true,
    ];

    // Get existing log
    $log = get_option(self::AUDIT_LOG_OPTION, []);

    // Add new entry
    array_push($log, $log_entry);

    // Limit log size
    if (count($log) > self::MAX_AUDIT_LOG_ENTRIES) {
      $log = array_slice($log, -self::MAX_AUDIT_LOG_ENTRIES);
    }

    // Save updated log
    update_option(self::AUDIT_LOG_OPTION, $log);
  }

  /**
   * Send notification to admin
   *
   * @param string $subject Email subject
   * @param string $message Email message
   */
  private function send_notification($subject, $message)
  {
    $admin_email = get_option("admin_email");

    if (!empty($admin_email)) {
      $site_name = get_bloginfo("name");
      $headers = ["Content-Type: text/plain; charset=UTF-8"];

      wp_mail($admin_email, "[{$site_name}] SQL Editor: {$subject}", $message, $headers);
    }
  }
}
