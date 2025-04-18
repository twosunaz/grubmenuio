<?php
namespace SnipVault\Rest;

// Prevent direct access to this file
defined("ABSPATH") || exit();

/**
 * Class FileSystemEndpoint
 *
 * Handles file system operations via REST API for the SnipVault plugin
 *
 * @package SnipVault\Rest
 */
class FileSystemEndpoint
{
  /**
   * Option name for storing endpoint activation state
   */
  const ACTIVATION_OPTION = "snipvault_filesystem_activation";
  /**
   * Constructor
   */
  public function __construct()
  {
    add_action("admin_init", [$this, "register_endpoints"]);
    add_action("rest_api_init", [$this, "register_endpoints"]);
  }

  /**
   * Register REST API endpoints
   */
  public function register_endpoints()
  {
    // Enable file browser (admin only)
    register_rest_route("snipvault/v1", "/filesystem/enable", [
      "methods" => "POST",
      "callback" => [$this, "enable_filesystem"],
      "permission_callback" => [$this, "check_permission_for_activation"],
    ]);

    // Disable file browser (admin only)
    register_rest_route("snipvault/v1", "/filesystem/disable", [
      "methods" => "POST",
      "callback" => [$this, "disable_filesystem"],
      "permission_callback" => [$this, "check_permission_for_activation"],
    ]);

    // Check status of file browser (admin only)
    register_rest_route("snipvault/v1", "/filesystem/status", [
      "methods" => "GET",
      "callback" => [$this, "get_filesystem_status"],
      "permission_callback" => [$this, "check_permission_for_activation"],
    ]);

    // List directory contents (using query parameter)
    register_rest_route("snipvault/v1", "/filesystem/list", [
      "methods" => "GET",
      "callback" => [$this, "get_directory_contents"],
      "permission_callback" => [$this, "check_permission"],
      "args" => [
        "path" => [
          "required" => false,
          "default" => "",
          "sanitize_callback" => [$this, "sanitize_path"],
        ],
        "search" => [
          "required" => false,
          "default" => "",
          "sanitize_callback" => "sanitize_text_field",
        ],
      ],
    ]);

    // Read file (using query parameter)
    register_rest_route("snipvault/v1", "/filesystem/read", [
      "methods" => "GET",
      "callback" => [$this, "read_file"],
      "permission_callback" => [$this, "check_permission"],
      "args" => [
        "path" => [
          "required" => true,
          "sanitize_callback" => [$this, "sanitize_path"],
        ],
      ],
    ]);

    // Write file (using query parameter)
    register_rest_route("snipvault/v1", "/filesystem/write", [
      "methods" => "POST",
      "callback" => [$this, "write_file"],
      "permission_callback" => [$this, "check_permission"],
      "args" => [
        "path" => [
          "required" => true,
          "sanitize_callback" => [$this, "sanitize_path"],
        ],
        "filename" => [
          "required" => true,
          "sanitize_callback" => [$this, "sanitize_path"],
        ],
        "content" => [
          "required" => true,
          "validate_callback" => function ($content) {
            return is_string($content);
          },
        ],
      ],
    ]);

    // Check if file exists (using query parameter)
    register_rest_route("snipvault/v1", "/filesystem/exists", [
      "methods" => "GET",
      "callback" => [$this, "check_file_exists"],
      "permission_callback" => [$this, "check_permission"],
      "args" => [
        "path" => [
          "required" => true,
          "sanitize_callback" => [$this, "sanitize_path"],
        ],
      ],
    ]);

    // Delete file or directory (using query parameter)
    register_rest_route("snipvault/v1", "/filesystem/delete", [
      "methods" => "DELETE",
      "callback" => [$this, "delete_item"],
      "permission_callback" => [$this, "check_permission"],
      "args" => [
        "path" => [
          "required" => true,
          "sanitize_callback" => [$this, "sanitize_path"],
        ],
      ],
    ]);

    // Create directory (using query parameter)
    register_rest_route("snipvault/v1", "/filesystem/create-directory", [
      "methods" => "POST",
      "callback" => [$this, "create_directory"],
      "permission_callback" => [$this, "check_permission"],
      "args" => [
        "path" => [
          "required" => true,
          "sanitize_callback" => [$this, "sanitize_path"],
        ],
      ],
    ]);
  }

  /**
   * Check if user has permission to use the filesystem API
   *
   * @return bool
   */
  public function check_permission_for_activation()
  {
    return current_user_can("manage_options") && current_user_can("administrator");
  }

  /**
   * Check if user has permission to use the filesystem API
   *
   * @return bool
   */
  public function check_permission()
  {
    $status = self::get_activation_status();
    $disabled = self::is_explicitly_disabled();

    return current_user_can("manage_options") && current_user_can("administrator") && $status["active"] === true && $disabled !== true;
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
    if (isset($options["file_browsing_disabled"]) && $options["file_browsing_disabled"]) {
      return true;
    }
    return false;
  }

  /**
   * Disable the file system endpoints
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function disable_filesystem(\WP_REST_Request $request)
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
      "message" => "File browser disabled",
      "was_active" => !empty($previous["active"]),
    ]);
  }

  /**
   * Get the current status of the file system endpoints
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function get_filesystem_status(\WP_REST_Request $request)
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
   * Enable the file system endpoints for 1 hour
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function enable_filesystem(\WP_REST_Request $request)
  {
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
      "message" => "File browser enabled for 1 hour",
      "expires" => $expiration,
      "expires_formatted" => date("Y-m-d H:i:s", $expiration),
    ]);
  }

  /**
   * Get the current activation status, handling expiration
   *
   * @return array Status information
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
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : "Unknown";
      }
    }

    return "Unknown";
  }

  /**
   * Sanitize the path parameter
   *
   * @param string $path
   * @return string
   */
  public function sanitize_path($path)
  {
    // Remove any ../ to prevent directory traversal
    $path = preg_replace("/\.\.\//", "", $path);
    // Remove any leading slashes
    $path = ltrim($path, "/");
    return sanitize_text_field($path);
  }

  /**
   * Check if the requested path is allowed
   *
   * @param string $path
   * @return bool
   */
  private function is_path_allowed($path)
  {
    // Empty path means root directory, which is allowed
    if ($path === "") {
      return true;
    }

    // Convert to absolute path
    $absolute_path = ABSPATH . $path;

    // For existing files/directories, use realpath
    if (file_exists($absolute_path)) {
      $real_path = realpath($absolute_path);
      if ($real_path === false || strpos($real_path, ABSPATH) !== 0) {
        return false;
      }
    } else {
      // For new files, validate the directory exists and is within allowed boundaries
      $dir_path = dirname($absolute_path);

      // Special case for files in the root directory
      if ($dir_path === ABSPATH || $dir_path . "/" === ABSPATH) {
        // Root directory exists by definition, so we're good
      } else {
        if (!file_exists($dir_path)) {
          return false; // Parent directory must exist
        }

        $real_dir_path = realpath($dir_path);
        if ($real_dir_path === false || strpos($real_dir_path, ABSPATH) !== 0) {
          return false;
        }
      }

      // Also verify the filename doesn't contain path traversal characters
      $filename = basename($absolute_path);
      if (strpos($filename, "..") !== false || strpos($filename, "/") !== false) {
        return false;
      }
    }

    // Block access to sensitive files
    $sensitive_files = [".htaccess", "wp-content/uploads/.htaccess"];
    foreach ($sensitive_files as $file) {
      if (strpos($path, $file) !== false) {
        return false;
      }
    }

    return true;
  }

  /**
   * Modified get_directory_contents method with search functionality
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function get_directory_contents(\WP_REST_Request $request)
  {
    $path = $request->get_param("path");
    $search = $request->get_param("search");

    // If path is not set, use empty string (root directory)
    if ($path === null) {
      $path = "";
    } else {
      $path = $this->sanitize_path($path);
    }

    if (!$this->is_path_allowed($path, !empty($search))) {
      return new \WP_Error("forbidden_path", $search, ["status" => 403]);
    }

    $absolute_path = ABSPATH . $path;

    if (!is_dir($absolute_path)) {
      return new \WP_Error("directory_not_found", "Directory not found.", ["status" => 404]);
    }

    // If search is provided, use recursive search function
    if (!empty($search)) {
      $entries = $this->search_files_recursive($absolute_path, $path, $search);

      return rest_ensure_response([
        "success" => true,
        "path" => $path,
        "search" => $search,
        "entries" => $entries,
      ]);
    }

    // Original directory listing logic (unchanged)
    $entries = [];
    $dir = dir($absolute_path);

    if ($dir) {
      while (($entry = $dir->read()) !== false) {
        // Skip dots and hidden files (starting with .)
        if ($entry === "." || $entry === ".." || substr($entry, 0, 1) === ".") {
          continue;
        }

        $full_path = $absolute_path . "/" . $entry;
        $is_dir = is_dir($full_path);

        $entries[] = [
          "name" => $entry,
          "path" => $path ? $path . "/" . $entry : $entry,
          "type" => $is_dir ? "directory" : "file",
          "size" => $is_dir ? null : filesize($full_path),
          "modified" => date("Y-m-d H:i:s", filemtime($full_path)),
        ];
      }

      $dir->close();
    }

    return rest_ensure_response([
      "success" => true,
      "path" => $path,
      "entries" => $entries,
    ]);
  }

  /**
   * Recursively search for files and directories matching the search term
   *
   * @param string $absolute_path The absolute directory path to search in
   * @param string $relative_path The relative path from WordPress root
   * @param string $search The search term to match against file/directory names
   * @return array List of matching entries
   */
  private function search_files_recursive($absolute_path, $relative_path, $search)
  {
    $results = [];

    // Set a reasonable limit to prevent excessive resource usage
    static $max_depth = 10;
    static $current_depth = 0;
    static $max_results = 1000;
    static $result_count = 0;

    // Avoid infinite loops or extremely deep recursion
    if ($current_depth >= $max_depth || $result_count >= $max_results) {
      return $results;
    }

    $current_depth++;

    // Skip this directory if it's not allowed
    if (!$this->is_path_allowed($relative_path, true)) {
      $current_depth--;
      return $results;
    }

    // Get all files and directories in the current path
    $items = scandir($absolute_path);

    foreach ($items as $item) {
      // Skip dots and hidden files
      if ($item === "." || $item === ".." || substr($item, 0, 1) === ".") {
        continue;
      }

      $full_path = $absolute_path . "/" . $item;
      $rel_path = $relative_path ? $relative_path . "/" . $item : $item;
      $is_dir = is_dir($full_path);

      // Skip this item if it's not allowed
      if (!$this->is_path_allowed($rel_path, true)) {
        continue;
      }

      // Check if this item's name matches the search term
      if (stripos($item, $search) !== false) {
        $results[] = [
          "name" => $item,
          "path" => $rel_path,
          "type" => $is_dir ? "directory" : "file",
          "size" => $is_dir ? null : filesize($full_path),
          "modified" => date("Y-m-d H:i:s", filemtime($full_path)),
        ];

        $result_count++;

        // Stop if we've reached the maximum number of results
        if ($result_count >= $max_results) {
          $current_depth--;
          return $results;
        }
      }

      // If this is a directory, search inside it too
      if ($is_dir) {
        $sub_results = $this->search_files_recursive($full_path, $rel_path, $search);
        $results = array_merge($results, $sub_results);

        // Update result count
        $result_count = count($results);

        // Stop if we've reached the maximum number of results
        if ($result_count >= $max_results) {
          $current_depth--;
          return $results;
        }
      }
    }

    $current_depth--;
    return $results;
  }

  /**
   * Read a file
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function read_file(\WP_REST_Request $request)
  {
    $path = $this->sanitize_path($request->get_param("path"));

    if (!$this->is_path_allowed($path)) {
      return new \WP_Error("forbidden_path", "Access to this path is not allowed.", ["status" => 403]);
    }

    $absolute_path = ABSPATH . $path;

    if (!file_exists($absolute_path) || is_dir($absolute_path)) {
      return new \WP_Error("file_not_found", "File not found.", ["status" => 404]);
    }

    $content = file_get_contents($absolute_path);

    // Determine language based on file extension
    $language = $this->get_language_from_path($path);

    // Get filename from path
    $filename = basename($path);

    // Check if this is a core WordPress file
    $is_core_file = $this->is_wordpress_core_file($path);

    if ($content === false) {
      return new \WP_Error("read_error", "Failed to read file.", ["status" => 500]);
    }

    return rest_ensure_response([
      "success" => true,
      "path" => $path,
      "content" => $content,
      "filename" => $filename,
      "language" => $language,
      "size" => filesize($absolute_path),
      "modified" => date("Y-m-d H:i:s", filemtime($absolute_path)),
      "is_core_file" => $is_core_file,
    ]);
  }

  /**
   * Check if a file is part of WordPress core
   *
   * @param string $path
   * @return bool
   */
  private function is_wordpress_core_file($path)
  {
    // If the file is in the root directory (no slashes), it's considered core
    if (strpos($path, "/") === false) {
      return true;
    }

    // Check if the file is inside wp-admin or wp-includes directories
    if (strpos($path, "wp-admin/") === 0 || strpos($path, "wp-includes/") === 0) {
      return true;
    }

    // These directories are considered part of WordPress core
    $core_paths = [
      "wp-admin/",
      "wp-includes/",
      "wp-admin",
      "wp-includes",
      "index.php",
      "wp-login.php",
      "wp-blog-header.php",
      "wp-cron.php",
      "wp-config-sample.php",
      "wp-settings.php",
      "wp-links-opml.php",
      "wp-mail.php",
      "wp-signup.php",
      "wp-trackback.php",
      "xmlrpc.php",
    ];

    // Check if the path starts with or matches any core paths
    foreach ($core_paths as $core_path) {
      if ($path === $core_path || strpos($path, $core_path) === 0) {
        return true;
      }
    }

    return false;
  }

  /**
   * Get programming language from file path
   *
   * @param string $path
   * @return string
   */
  private function get_language_from_path($path)
  {
    $extension = pathinfo($path, PATHINFO_EXTENSION);

    $language_map = [
      // Web
      "php" => "php",
      "html" => "html",
      "htm" => "html",
      "js" => "javascript",
      "jsx" => "javascript",
      "ts" => "typescript",
      "tsx" => "typescript",
      "css" => "css",
      "scss" => "scss",
      "sass" => "sass",
      "less" => "less",
      "vue" => "vue",

      // Server-side languages
      "py" => "python",
      "rb" => "ruby",
      "java" => "java",
      "c" => "c",
      "cpp" => "cpp",
      "h" => "c",
      "hpp" => "cpp",
      "cs" => "csharp",
      "go" => "go",
      "rs" => "rust",
      "swift" => "swift",
      "kt" => "kotlin",

      // Config/Data
      "json" => "json",
      "xml" => "xml",
      "yml" => "yaml",
      "yaml" => "yaml",
      "toml" => "toml",
      "ini" => "ini",
      "md" => "markdown",
      "sql" => "sql",
      "graphql" => "graphql",

      // Shell/scripts
      "sh" => "bash",
      "bash" => "bash",
      "bat" => "batch",
      "ps1" => "powershell",

      // WordPress specific
      "pot" => "pot",
      "po" => "po",
      "mo" => "binary",

      // Default
      "txt" => "plaintext",
    ];

    $lowercase_ext = strtolower($extension);

    return isset($language_map[$lowercase_ext]) ? $language_map[$lowercase_ext] : "plaintext";
  }

  /**
   * Check for PHP syntax errors in the file
   *
   * @param string $file_path
   * @return bool True if errors detected, false otherwise
   */
  private function check_for_php_errors($file_path)
  {
    // Skip check for non-PHP files
    if (pathinfo($file_path, PATHINFO_EXTENSION) !== "php") {
      return false;
    }

    // Read the file content
    $content = file_get_contents($file_path);

    // Use PHP's tokenizer to check for syntax errors
    try {
      $tokens = @token_get_all($content);

      // If tokenizer fails, it's definitely a syntax error
      if ($tokens === false) {
        return true;
      }

      // Check for unbalanced brackets, parentheses, etc.
      $brackets = ["(" => 0, "{" => 0, "[" => 0];

      foreach ($tokens as $token) {
        if (is_string($token)) {
          switch ($token) {
            case "(":
              $brackets["("]++;
              break;
            case ")":
              $brackets["("]--;
              break;
            case "{":
              $brackets["{"]++;
              break;
            case "}":
              $brackets["{"]--;
              break;
            case "[":
              $brackets["["]++;
              break;
            case "]":
              $brackets["["]--;
              break;
          }
        }
      }

      // If any bracket count is not 0, we have unbalanced brackets
      foreach ($brackets as $count) {
        if ($count !== 0) {
          return true;
        }
      }

      // Basic syntax check passed
      return false;
    } catch (\Exception $e) {
      // Exception in tokenizer, likely syntax error
      return true;
    }
  }

  /**
   * Check if the site is still functioning after file update
   * Includes a delay to allow PHP opcode cache to refresh
   *
   * @return string|false Error message or false if site is functioning
   */
  private function check_site_health()
  {
    // Sleep for a short period to allow opcache to refresh
    // and any file operations to complete
    sleep(1);

    // Clear any PHP opcode cache if possible
    if (function_exists("opcache_reset")) {
      @opcache_reset();
    }

    // Get WordPress home URL
    $url = home_url();

    // Add a timestamp to bypass cache
    $test_url = add_query_arg("snipvault_check", time(), $url);

    // Make multiple requests with slight delays between them
    // to increase chances of catching intermittent issues
    $attempts = 2;
    $error = false;

    for ($i = 0; $i < $attempts; $i++) {
      // If this isn't the first attempt, add a small delay
      if ($i > 0) {
        sleep(1);
      }

      // Make request with browser-like headers
      $args = [
        "timeout" => 10,
        "sslverify" => false,
        "user-agent" => "Mozilla/5.0 (compatible; SnipVaultHealthCheck/1.0)",
        "headers" => [
          "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
          "Cache-Control" => "no-cache",
          "Pragma" => "no-cache",
        ],
      ];

      // Make the request
      $response = wp_remote_get($test_url, $args);

      // Check for basic request error
      if (is_wp_error($response)) {
        $error = "Site request failed: " . $response->get_error_message();
        continue; // Try again if we have attempts left
      }

      // Check status code
      $status_code = wp_remote_retrieve_response_code($response);
      if ($status_code >= 500) {
        $error = "Site returned error " . $status_code;
        continue; // Try again if we have attempts left
      }

      // Get the response body
      $body = wp_remote_retrieve_body($response);

      // Check for common PHP error patterns in the response
      $error_patterns = [
        "Parse error:",
        "Fatal error:",
        "Warning:",
        "Notice:",
        "Uncaught Error:",
        "Call to undefined function",
        "Database error",
        "Error establishing a database connection",
        "syntax error",
        "unexpected",
      ];

      $found_error = false;
      foreach ($error_patterns as $pattern) {
        if (stripos($body, $pattern) !== false) {
          // Extract a snippet of the error context
          $pos = stripos($body, $pattern);
          $start = max(0, $pos - 20);
          $length = strlen($pattern) + 40; // Get some context after the error too
          $context = substr($body, $start, $length);

          $error = "Site returned error: " . $context;
          $found_error = true;
          break;
        }
      }

      if ($found_error) {
        continue; // Try again if we have attempts left
      }

      // Check for minimal valid HTML structure
      if (!preg_match("/<html[^>]*>/i", $body) || !preg_match("/<\/html>/i", $body)) {
        $error = "Site returned invalid HTML structure";
        continue; // Try again if we have attempts left
      }

      // If we got here, the site is functioning correctly on this attempt
      return false;
    }

    // If we've tried all attempts and still have an error, return it
    return $error;
  }

  /**
   * Write to a file with error checking and reversion capability
   * Also supports renaming files if filename parameter differs from path
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function write_file(\WP_REST_Request $request)
  {
    $path = $this->sanitize_path($request->get_param("path"));
    $filename = $this->sanitize_path($request->get_param("filename"));
    $content = $request->get_param("content");

    // Check if paths are allowed
    if (!$this->is_path_allowed($path)) {
      return new \WP_Error("forbidden_path", "Access to source path is not allowed.", ["status" => 403]);
    }

    $absolute_path = ABSPATH . $path;

    // Determine if we're renaming the file
    $is_rename = false;
    $new_path = $path;
    $new_absolute_path = $absolute_path;

    // Check if filename differs from the original path
    if ($filename !== basename($path)) {
      $is_rename = true;
      $directory = dirname($path);
      $new_path = $directory === "." ? $filename : $directory . "/" . $filename;

      // Check if the new path is allowed
      if (!$this->is_path_allowed($new_path)) {
        return new \WP_Error("forbidden_path", "Access to destination path is not allowed.", ["status" => 403]);
      }

      $new_absolute_path = ABSPATH . $new_path;

      // Check if target file already exists
      if (file_exists($new_absolute_path) && $new_absolute_path !== $absolute_path) {
        return new \WP_Error("file_exists", "Target file already exists.", ["status" => 409]);
      }
    }

    // Create directories if they don't exist
    $dir = dirname($new_absolute_path);
    if (!is_dir($dir)) {
      if (!wp_mkdir_p($dir)) {
        return new \WP_Error("directory_creation_failed", "Failed to create directory.", ["status" => 500]);
      }
    }

    // Check if source file exists and backup original content
    $is_update = file_exists($absolute_path);
    $original_content = null;

    if ($is_update) {
      $original_content = $this->backup_file($absolute_path);
      if ($original_content === false) {
        return new \WP_Error("backup_error", "Failed to backup original file content.", ["status" => 500]);
      }
    }

    // For PHP files, check for syntax errors before saving
    if (pathinfo($new_absolute_path, PATHINFO_EXTENSION) === "php") {
      // Create a temporary file to check syntax
      $temp_file = ABSPATH . "wp-content/uploads/snipvault-temp-" . uniqid() . ".php";
      file_put_contents($temp_file, $content);

      // Check for syntax errors
      $syntax_error = $this->check_for_php_errors($temp_file);

      // Delete the temporary file
      @unlink($temp_file);

      if ($syntax_error) {
        return new \WP_Error("php_error", "PHP syntax error detected. The file has not been updated.", ["status" => 400]);
      }
    }

    // Write content to new location
    $result = file_put_contents($new_absolute_path, $content);

    if ($result === false) {
      return new \WP_Error("write_error", "Failed to write to file.", ["status" => 500]);
    }

    // If it's a rename and files are different, delete the old file after successful write
    if ($is_rename && file_exists($absolute_path) && $absolute_path !== $new_absolute_path) {
      @unlink($absolute_path);

      // Also update metadata if it exists
      if (class_exists("SnipVault_FileMetadata") && SnipVault_FileMetadata::has_metadata($path)) {
        $metadata = SnipVault_FileMetadata::get_metadata($path);
        if ($metadata) {
          SnipVault_FileMetadata::save_metadata($new_path, $metadata);
          SnipVault_FileMetadata::delete_metadata($path);
        }
      }
    }

    // For PHP files in plugin or theme directories, check site health
    if (pathinfo($new_absolute_path, PATHINFO_EXTENSION) === "php") {
      // Check if the site is still functioning
      $site_error = $this->check_site_health();

      if ($site_error) {
        // Revert to original content and location
        if ($is_rename && $absolute_path !== $new_absolute_path) {
          // If renamed, delete the new file and restore the old one
          @unlink($new_absolute_path);
          if ($is_update) {
            $this->restore_file($absolute_path, $original_content);
          }
        } else {
          // If not renamed, just restore content
          $this->restore_file($new_absolute_path, $original_content);
        }

        return new \WP_Error("wordpress_error", "File update caused WordPress errors: " . $site_error . ". The file has been reverted to its original state.", ["status" => 400]);
      }
    }

    return rest_ensure_response([
      "success" => true,
      "path" => $new_path,
      "original_path" => $is_rename ? $path : null,
      "was_renamed" => $is_rename,
      "was_updated" => $is_update,
      "size" => filesize($new_absolute_path),
      "modified" => date("Y-m-d H:i:s", filemtime($new_absolute_path)),
    ]);
  }

  /**
   * Check if a file or directory exists
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function check_file_exists(\WP_REST_Request $request)
  {
    $path = $this->sanitize_path($request->get_param("path"));

    if (!$this->is_path_allowed($path)) {
      return new \WP_Error("forbidden_path", "Access to this path is not allowed.", ["status" => 403]);
    }

    $absolute_path = ABSPATH . $path;
    $exists = file_exists($absolute_path);
    $type = null;

    if ($exists) {
      $type = is_dir($absolute_path) ? "directory" : "file";
    }

    return rest_ensure_response([
      "success" => true,
      "path" => $path,
      "exists" => $exists,
      "type" => $type,
      // Provide additional information if the file/directory exists
      "info" => $exists
        ? [
          "size" => is_file($absolute_path) ? filesize($absolute_path) : null,
          "modified" => date("Y-m-d H:i:s", filemtime($absolute_path)),
          "is_readable" => is_readable($absolute_path),
          "is_writable" => is_writable($absolute_path),
          "is_core_file" => is_file($absolute_path) ? $this->is_wordpress_core_file($path) : false,
        ]
        : null,
    ]);
  }

  /**
   * Create a backup of the file before modification
   *
   * @param string $file_path The absolute path to the file
   * @return string|false The original content or false on failure
   */
  private function backup_file($file_path)
  {
    if (!file_exists($file_path) || !is_readable($file_path)) {
      return false;
    }
    // Read original content
    $original_content = file_get_contents($file_path);
    // Also create a timestamped backup file for emergencies
    $backup_dir = WP_CONTENT_DIR . "/snipvault-backups";
    if (!is_dir($backup_dir) && !wp_mkdir_p($backup_dir)) {
      // Can't create backup directory, but continue anyway with in-memory backup
      return $original_content;
    }
    // Create a backup with timestamp
    $rel_path = str_replace(ABSPATH, "", $file_path);
    $safe_path = str_replace("/", "_", $rel_path);
    $backup_path = $backup_dir . "/" . $safe_path . "." . time() . ".bak";
    file_put_contents($backup_path, $original_content);

    // Clean up old backups after creating a new one
    $this->cleanup_old_backups($backup_dir);

    return $original_content;
  }

  /**
   * Clean up backup files older than 5 minutes
   *
   * @param string $backup_dir The backup directory path
   * @return void
   */
  private function cleanup_old_backups($backup_dir)
  {
    if (!is_dir($backup_dir)) {
      return;
    }

    // Get all backup files
    $files = glob($backup_dir . "/*.bak");
    if (empty($files)) {
      return;
    }

    // Current time minus 5 minutes (300 seconds)
    $expiration_time = time() - 300;

    foreach ($files as $file) {
      // Extract timestamp from filename
      if (preg_match('/\.(\d+)\.bak$/', $file, $matches)) {
        $file_time = (int) $matches[1];

        // Delete if older than 5 minutes
        if ($file_time < $expiration_time) {
          @unlink($file);
        }
      }
    }
  }

  /**
   * Restore a file from its backup content
   *
   * @param string $file_path The absolute path to the file
   * @param string $original_content The original content to restore
   * @return bool Success or failure
   */
  private function restore_file($file_path, $original_content)
  {
    if (empty($original_content)) {
      return false;
    }

    return (bool) file_put_contents($file_path, $original_content);
  }

  /**
   * Delete a file or directory
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function delete_item(\WP_REST_Request $request)
  {
    $path = $this->sanitize_path($request->get_param("path"));

    if (!$this->is_path_allowed($path)) {
      return new \WP_Error("forbidden_path", "Access to this path is not allowed.", ["status" => 403]);
    }

    $absolute_path = ABSPATH . $path;

    if (!file_exists($absolute_path)) {
      return new \WP_Error("not_found", "File or directory not found.", ["status" => 404]);
    }

    $success = is_dir($absolute_path) ? $this->delete_directory_recursive($absolute_path) : unlink($absolute_path);

    if (!$success) {
      return new \WP_Error("delete_error", "Failed to delete file or directory.", ["status" => 500]);
    }

    return rest_ensure_response([
      "success" => true,
      "path" => $path,
    ]);
  }

  /**
   * Create a directory
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function create_directory(\WP_REST_Request $request)
  {
    $path = $this->sanitize_path($request->get_param("path"));

    if (!$this->is_path_allowed($path)) {
      return new \WP_Error("forbidden_path", "Access to this path is not allowed.", ["status" => 403]);
    }

    $absolute_path = ABSPATH . $path;

    if (file_exists($absolute_path)) {
      return new \WP_Error("exists", "File or directory already exists.", ["status" => 409]);
    }

    if (!wp_mkdir_p($absolute_path)) {
      return new \WP_Error("directory_creation_failed", "Failed to create directory.", ["status" => 500]);
    }

    return rest_ensure_response([
      "success" => true,
      "path" => $path,
    ]);
  }

  /**
   * Recursively delete a directory
   *
   * @param string $dir_path
   * @return bool
   */
  private function delete_directory_recursive($dir_path)
  {
    if (!is_dir($dir_path)) {
      return false;
    }

    $files = scandir($dir_path);

    foreach ($files as $file) {
      if ($file === "." || $file === "..") {
        continue;
      }

      $path = $dir_path . "/" . $file;

      if (is_dir($path)) {
        $this->delete_directory_recursive($path);
      } else {
        unlink($path);
      }
    }

    return rmdir($dir_path);
  }
}
