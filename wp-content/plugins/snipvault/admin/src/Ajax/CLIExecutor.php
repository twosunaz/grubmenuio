<?php
/**
 * WP CLI Executor
 *
 * A class to securely execute WP-CLI commands through WordPress admin AJAX
 * with security verification, path detection, and command sanitization.
 *
 * @package SnipVault\Ajax
 */

namespace SnipVault\Ajax;

// Exit if accessed directly
if (!defined("ABSPATH")) {
  exit();
}

/**
 * WP CLI Executor Class
 */
class CLIExecutor
{
  /**
   * Option name for storing CLI activation state
   */
  const ACTIVATION_OPTION = "snipvault_cli_activation";
  /**
   * PHP executable path
   *
   * @var string
   */
  private $php_executable;

  /**
   * PHP INI path
   *
   * @var string
   */
  private $php_ini_path;

  /**
   * WordPress path
   *
   * @var string
   */
  private $wp_path;

  /**
   * WP-CLI phar path
   *
   * @var string
   */
  private $wp_cli_phar;

  /**
   * Command to execute
   *
   * @var string
   */
  private $command;

  /**
   * Constructor
   *
   * Sets up the AJAX action hook
   */
  public function __construct()
  {
    // Set up AJAX handler
    add_action("wp_ajax_snirvault_run_wp_cli", [__CLASS__, "handle_ajax_request"]);
  }

  /**
   * AJAX handler for WP-CLI execution
   *
   * @return void
   */
  public static function handle_ajax_request()
  {
    // Check if user is logged in and has admin capabilities
    if (!current_user_can("manage_options")) {
      wp_send_json_error(
        [
          "error" => true,
          "message" => "Access denied: You need administrator privileges to run WP-CLI commands.",
        ],
        403
      );
    }

    // Verify nonce for AJAX request
    if (!check_ajax_referer("snirvault_wpcli_nonce", "security_nonce", false)) {
      wp_send_json_error(
        [
          "error" => true,
          "message" => "Security verification failed: Invalid nonce.",
        ],
        403
      );
    }

    // Verify snipvault cli is activated
    if (!self::get_activation_status()) {
      wp_send_json_error(
        [
          "error" => true,
          "message" => "SnipVault CLI is not activated",
        ],
        403
      );
    }

    // Verify snipvault cli is activated
    if (self::is_explicitly_disabled()) {
      wp_send_json_error(
        [
          "error" => true,
          "message" => "SnipVault CLI is disabled",
        ],
        403
      );
    }

    // Get command from request
    $command = isset($_REQUEST["command"]) ? sanitize_text_field($_REQUEST["command"]) : "cli version";

    // Safety check for command
    if (!self::is_command_allowed($command)) {
      wp_send_json_error(
        [
          "success" => false,
          "error" => true,
          "message" => "Command not allowed for security reasons.",
        ],
        403
      );
    }

    // Initialize executor instance and configure it
    $executor = new self();
    // Initialize constants if needed
    $executor->initialize_constants();
    $executor->configure($command);
    $result = $executor->execute();

    wp_send_json($result);
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
   * Check if a command is allowed
   *
   * @param string $command The command to check
   * @return bool True if allowed, false otherwise
   */
  private static function is_command_allowed($command)
  {
    // Properly normalize the command
    $normalized = trim(strtolower($command));

    // Remove leading 'wp' if present
    if (strpos($normalized, "wp ") === 0) {
      $normalized = substr($normalized, 3);
    }
    $normalized = trim($normalized);

    // List of disallowed command patterns for security
    $disallowed_patterns = [
      // Dangerous WP-CLI commands
      "db query", // Prevent direct SQL queries
      "package install", // Prevent package installation
      "eval", // Prevent arbitrary PHP execution
      "eval-file", // Prevent arbitrary PHP file execution
      "shell", // Prevent shell access
      "ssh", // Prevent SSH commands
      "--exec", // Prevent exec flag
      "--debug", // Prevent debug mode
      "cron event run", // Prevent cron event execution

      // Dangerous shell operators and metacharacters
      ">", // Prevent output redirection
      ">>", // Prevent output appending
      "|", // Prevent piping
      "&", // Prevent background execution
      ";", // Prevent command chaining
      "&&", // Prevent command chaining
      "||", // Prevent command chaining
      "`", // Prevent command substitution
      "$(", // Prevent command substitution
      '${', // Prevent variable expansion

      // System commands that might be used in injection
      "rm ", // Prevent file deletion
      "mv ", // Prevent file moving
      "cp ", // Prevent file copying
      "chmod ", // Prevent permission changes
      "chown ", // Prevent ownership changes
      "curl ", // Prevent network requests
      "wget ", // Prevent network requests
      "bash ", // Prevent shell execution
      "sh ", // Prevent shell execution
      "python ", // Prevent Python execution
      "perl ", // Prevent Perl execution
    ];

    // Check for disallowed patterns with better pattern matching
    foreach ($disallowed_patterns as $pattern) {
      // Escape any regex special characters in the pattern
      $escaped_pattern = preg_quote($pattern, "/");

      // For command words, we want to match whole words only
      if (preg_match('/^[a-z\-]+ [a-z\-]+$/', $pattern)) {
        // Command with subcommand format (e.g., "plugin install")
        // Use word boundaries or command-start boundary
        if (preg_match("/(\s|^)" . $escaped_pattern . '(\s|$)/', $normalized)) {
          return false;
        }
      }
      // For single-word commands and flags, we want to match whole words
      elseif (preg_match('/^[a-z\-]+$/', $pattern)) {
        if (preg_match("/(\s|^)" . $escaped_pattern . '(\s|$)/', $normalized)) {
          return false;
        }
      }
      // For shell operators, match exactly as is
      else {
        if (strpos($normalized, $pattern) !== false) {
          return false;
        }
      }
    }

    // Additional safety checks

    // Prevent running any files (--require flag)
    if (preg_match("/--require[= ]/", $normalized)) {
      return false;
    }

    // Prevent running code via callbacks
    if (preg_match("/--hook-extra=/", $normalized)) {
      return false;
    }

    // Check for sneaky paths that might indicate trying to access files outside WP
    if (preg_match("/(\/\.\.\/|\.\.\\\\|~\/|\\\\\.\.\\\\)/", $normalized)) {
      return false;
    }

    return true;
  }

  /**
   * Get activation status
   *
   * @return array
   */
  private static function get_activation_status()
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

    return $status;
  }

  /**
   * Configure the executor with a command and find the necessary paths
   *
   * @param string $command WP-CLI command to execute
   * @return void
   */
  public function configure($command)
  {
    // Set the command after sanitization
    $this->command = $this->sanitize_wp_cli_command($command);

    // Find paths
    $this->php_executable = $this->find_php_executable();
    $this->php_ini_path = $this->find_php_ini_path();
    $this->wp_path = ABSPATH; // Use WordPress constant
    $this->wp_cli_phar = $this->find_wp_cli_path();

    // Verify if PHP executable works
    $this->verify_php_executable();
  }

  /**
   * Initialize required constants if not defined
   *
   * @return void
   */
  private function initialize_constants()
  {
    if (!defined("STDIN")) {
      define("STDIN", fopen("php://stdin", "r"));
    }
    if (!defined("STDOUT")) {
      define("STDOUT", fopen("php://stdout", "w"));
    }
    if (!defined("STDERR")) {
      define("STDERR", fopen("php://stdout", "w"));
    }
  }

  /**
   * Find the PHP executable path
   *
   * @return string The path to PHP executable
   */
  private function find_php_executable()
  {
    // Method 1: Check if we're getting php-fpm instead of php CLI
    if (defined("PHP_BINARY") && PHP_BINARY && strpos(PHP_BINARY, "php-fpm") !== false) {
      // If PHP_BINARY is php-fpm, look for regular php in the same directory
      $dir = dirname(PHP_BINARY);
      $php_cli = $dir . DIRECTORY_SEPARATOR . "php";
      if (file_exists($php_cli)) {
        return $php_cli;
      }

      // Check parent directory
      $parent_dir = dirname($dir);
      $php_cli = $parent_dir . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "php";
      if (file_exists($php_cli)) {
        return $php_cli;
      }
    }

    // Method 2: Using PHP_BINARY constant (available since PHP 5.4) if not php-fpm
    if (defined("PHP_BINARY") && PHP_BINARY && strpos(PHP_BINARY, "php-fpm") === false) {
      return PHP_BINARY;
    }

    // Method 3: Using 'which' command on Unix/Linux/macOS
    if (function_exists("exec") && strtoupper(substr(PHP_OS, 0, 3)) !== "WIN") {
      $output = [];
      exec("which php", $output);
      if (!empty($output[0])) {
        return $output[0];
      }
    }

    // Method 4: Using 'where' command on Windows
    if (function_exists("exec") && strtoupper(substr(PHP_OS, 0, 3)) === "WIN") {
      $output = [];
      exec("where php", $output);
      if (!empty($output[0])) {
        return $output[0];
      }
    }

    // Method 5: Check common paths
    $common_paths = ["/usr/bin/php", "/usr/local/bin/php", "/opt/homebrew/bin/php", "/opt/local/bin/php", "C:\\php\\php.exe", 'C:\\xampp\\php\\php.exe', "C:\\wamp\\bin\\php\\php7.4.0\\php.exe"];

    foreach ($common_paths as $path) {
      if (file_exists($path)) {
        return $path;
      }
    }

    return "php"; // Default to just 'php' command and hope it's in PATH
  }

  /**
   * Find the PHP INI path
   *
   * @return string The path to php.ini
   */
  private function find_php_ini_path()
  {
    // Method 1: Using php_ini_loaded_file()
    if (function_exists("php_ini_loaded_file")) {
      $ini_path = php_ini_loaded_file();
      if ($ini_path) {
        return $ini_path;
      }
    }

    // Method 2: Using php_ini_scanned_files()
    if (function_exists("php_ini_scanned_files")) {
      $scanned_ini_files = php_ini_scanned_files();
      if ($scanned_ini_files) {
        $ini_files = explode(",", $scanned_ini_files);
        if (!empty($ini_files[0])) {
          return trim($ini_files[0]);
        }
      }
    }

    // Method 3: Using ini_get('configuration_file')
    $ini_path = ini_get("configuration_file");
    if ($ini_path) {
      return $ini_path;
    }

    // Method 4: Call phpinfo() and parse output
    if (function_exists("phpinfo") && function_exists("ob_start") && function_exists("ob_get_clean")) {
      ob_start();
      phpinfo(INFO_GENERAL);
      $phpinfo = ob_get_clean();

      if (preg_match("/Loaded Configuration File => (.*)/", $phpinfo, $matches)) {
        return $matches[1];
      }
    }

    return "php.ini"; // Return default name as fallback
  }

  /**
   * Find WP-CLI path
   *
   * @return string The path to wp-cli.phar
   */
  private function find_wp_cli_path()
  {
    // First check if WP-CLI is available as a composer package
    $composer_wp_cli = WP_CONTENT_DIR . "/vendor/bin/wp";
    if (file_exists($composer_wp_cli)) {
      return $composer_wp_cli;
    }

    // Check common locations relative to WordPress root
    $common_wp_cli_paths = [ABSPATH . "wp-cli.phar", ABSPATH . "../wp-cli.phar", WP_CONTENT_DIR . "/wp-cli.phar", WP_PLUGIN_DIR . "/wp-cli.phar"];

    foreach ($common_wp_cli_paths as $path) {
      if (file_exists($path)) {
        return $path;
      }
    }

    // If running on Local by Flywheel
    if (strpos($this->php_executable, "Local/lightning-services") !== false) {
      // Check Local's common WP-CLI locations
      $possible_wp_cli_paths = [dirname($this->php_executable) . "/wp-cli.phar", dirname(dirname($this->php_executable)) . "/wp-cli.phar"];

      foreach ($possible_wp_cli_paths as $path) {
        if (file_exists($path)) {
          return $path;
        }
      }
    }

    // Try to find wp-cli using 'which' on Linux/Unix
    if (function_exists("exec") && strtoupper(substr(PHP_OS, 0, 3)) !== "WIN") {
      $output = [];
      exec("which wp 2>/dev/null", $output);
      if (!empty($output[0]) && file_exists($output[0])) {
        return $output[0];
      }
    }

    return "wp-cli.phar"; // Default to just the filename
  }

  /**
   * Verify if PHP executable works
   *
   * @return void
   */
  private function verify_php_executable()
  {
    if (!function_exists("exec")) {
      return; // Can't verify if exec is not available
    }

    $check_output = [];
    $check_status = -1;
    exec(escapeshellarg($this->php_executable) . " -v 2>&1", $check_output, $check_status);

    if ($check_status !== 0 || empty($check_output) || strpos(implode("\n", $check_output), "PHP") === false) {
      // Fallback to using 'php' from the PATH
      $this->php_executable = "php";
    }
  }

  /**
   * Sanitize WP-CLI commands to prevent command injection
   *
   * @param string $cmd The command to sanitize
   * @return string The sanitized command
   */
  private function sanitize_wp_cli_command($cmd)
  {
    if (empty($cmd)) {
      return "cli version";
    }

    // Remove any shell special characters and operators
    $dangerous = ["&", ";", "`", "|", "&&", "||", ">", ">>", "<", "<<", '$', '$(', '${', "?", "*", "\\", "\n", "\r"];
    $cmd = str_replace($dangerous, "", $cmd);

    // Remove any attempts to break out of the intended command context
    $cmd = preg_replace("/\s+2>&\d+\s*/", " ", $cmd);

    // Whitelist approach - only allow certain characters for WP-CLI commands
    // Allow: alphanumeric, spaces, dashes, underscores, equals, forward slashes, commas, dots, colons, brackets
    $cmd = preg_replace("/[^a-zA-Z0-9\s\-_=\/\.,:\[\]@]/", "", $cmd);

    // Prevent command stacking
    $cmd = preg_replace("/\s+&&\s+.*/", "", $cmd);
    $cmd = preg_replace("/\s+;\s+.*/", "", $cmd);

    return trim($cmd);
  }

  /**
   * Find MySQL binaries directory
   *
   * @return string The path to MySQL binaries directory or empty string if not found
   */
  private function find_mysql_bin_dir()
  {
    // First check specifically for Local by Flywheel patterns
    if (strpos($this->php_executable, "Local/lightning-services") !== false) {
      // Extract site ID from the PHP executable path
      if (preg_match("/Local\/lightning-services\/php[^\/]+\/([^\/]+)\//", $this->php_executable, $matches)) {
        $site_id = $matches[1];

        // Local by Flywheel specific paths
        $local_possible_paths = [
          // Lightning services MySQL path pattern
          dirname(dirname($this->php_executable)) . "/../mysql/mysqld/" . $site_id . "/bin",
          // Another common pattern in Local
          dirname(dirname(dirname($this->php_executable))) . "/mysql/mysqld/" . $site_id . "/bin",
          // Check for mysql in the root lightning-services directory
          preg_replace("/\/lightning-services\/php[^\/]+\/[^\/]+\/.*/", "/lightning-services/mysql/", $this->php_executable),
        ];

        foreach ($local_possible_paths as $path) {
          $mysql_check = $path . DIRECTORY_SEPARATOR . "mysqlcheck";
          if (file_exists($mysql_check)) {
            return $path;
          }
        }
      }
    }

    // If we're in WordPress, use DB_HOST from wp-config to detect socket path
    global $wpdb;
    if (isset($wpdb) && defined("DB_HOST") && strpos(DB_HOST, "/") !== false) {
      // Check if DB_HOST contains a path to a socket file
      $socket_path = DB_HOST;
      // Extract path where MySQL is installed from socket path
      $mysql_bin_dir = dirname(dirname($socket_path)) . "/bin";

      if (file_exists($mysql_bin_dir . DIRECTORY_SEPARATOR . "mysqlcheck")) {
        return $mysql_bin_dir;
      }
    }

    // Common MySQL binary locations
    $possible_paths = [
      "/usr/bin", // Standard Linux/Unix
      "/usr/local/bin", // Standard macOS/FreeBSD
      "/usr/local/mysql/bin", // Common MySQL installation
      "/opt/homebrew/bin", // macOS Homebrew on Apple Silicon
      "/opt/homebrew/opt/mysql/bin", // macOS Homebrew MySQL
      "/opt/homebrew/opt/mysql-client/bin", // macOS Homebrew MySQL Client
      "/opt/local/bin", // macOS MacPorts
      "/opt/local/lib/mysql/bin", // macOS MacPorts MySQL
      "/Applications/MAMP/Library/bin", // MAMP on macOS
      "/Applications/XAMPP/xamppfiles/bin", // XAMPP on macOS
      'C:\\xampp\\mysql\\bin', // XAMPP on Windows
      "C:\\wamp\\bin\\mysql\\mysql5.7.31\\bin", // WAMP on Windows
      "C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin", // Standard Windows install
      "C:\\Program Files (x86)\\MySQL\\MySQL Server 8.0\\bin", // 32-bit on 64-bit Windows
    ];

    // Check for mysqlcheck in each path
    foreach ($possible_paths as $path) {
      $mysql_check = $path . DIRECTORY_SEPARATOR . "mysqlcheck";
      // Add .exe extension for Windows
      if (strtoupper(substr(PHP_OS, 0, 3)) === "WIN") {
        $mysql_check .= ".exe";
      }

      if (file_exists($mysql_check)) {
        return $path;
      }
    }

    // Try to find using 'which' command on Unix systems
    if (function_exists("exec") && strtoupper(substr(PHP_OS, 0, 3)) !== "WIN") {
      $output = [];
      $return_var = -1;
      exec("which mysqlcheck 2>/dev/null", $output, $return_var);

      if ($return_var === 0 && !empty($output[0])) {
        return dirname($output[0]);
      }
    }

    // Try to find using 'where' command on Windows
    if (function_exists("exec") && strtoupper(substr(PHP_OS, 0, 3)) === "WIN") {
      $output = [];
      $return_var = -1;
      exec("where mysqlcheck 2>NUL", $output, $return_var);

      if ($return_var === 0 && !empty($output[0])) {
        return dirname($output[0]);
      }
    }

    return "";
  }

  /**
   * Execute the WP-CLI command
   *
   * @return array Response containing command output and status
   */
  public function execute()
  {
    // Change to the directory of the current file
    chdir(__DIR__);

    // Debug information to track environment
    $debug_info = [];

    // Handle environment variables to prevent paging more reliably
    putenv("PAGER=");
    putenv("WP_CLI_PAGER=");

    // Add MySQL bindir to environment if MySQL commands are being used
    $mysql_bin_dir = $this->find_mysql_bin_dir();
    $debug_info["mysql_bin_dir_found"] = !empty($mysql_bin_dir);

    // Check if the command is database-related
    $is_db_command = strpos($this->command, "db") !== false;

    if (!empty($mysql_bin_dir)) {
      // Properly escape the bin directory path for the environment variable
      $mysql_bin_dir = str_replace(" ", "\ ", $mysql_bin_dir);

      $debug_info["mysql_bin_dir_exists"] = file_exists($mysql_bin_dir);

      $debug_info["mysql_bin_dir"] = $mysql_bin_dir;
      putenv("WP_CLI_MYSQL_BINDIR=" . $mysql_bin_dir);
      // Also add to PATH for broader compatibility
      $current_path = getenv("PATH");
      if ($current_path) {
        putenv("PATH=" . $current_path . PATH_SEPARATOR . $mysql_bin_dir);
      } else {
        putenv("PATH=" . $mysql_bin_dir);
      }
    }

    // Check if WP-CLI is already available as a class in WordPress
    if (class_exists("WP_CLI")) {
      // Log that we're using internal WP-CLI
      $debug_info["using_wp_cli_class"] = true;

      // Capture output by buffering
      ob_start();

      try {
        // Parse command and execute via WP-CLI API
        $wp_cli_args = explode(" ", $this->command);

        // Run the command through WP-CLI
        \WP_CLI::run_command($wp_cli_args);

        $output_text = ob_get_clean();
        $exit_code = 0;
      } catch (\Exception $e) {
        $output_text = ob_get_clean();
        $output_text .= "\nError: " . $e->getMessage();
        $exit_code = 1;
      }
    } else {
      // Prepare WP-CLI command
      $path_param = " --path=" . escapeshellarg($this->wp_path) . " ";

      $executable =
        escapeshellarg($this->php_executable) .
        " -c " .
        escapeshellarg($this->php_ini_path) .
        ' -d error_reporting="E_ALL & ~E_NOTICE" -d memory_limit="2048M" -d max_execution_time=43200 ' .
        escapeshellarg($this->wp_cli_phar) .
        $path_param;

      // Is this a help command or listing command?
      $is_help_command =
        trim($this->command) === "" || trim($this->command) === "help" || strpos($this->command, "--help") !== false || strpos($this->command, " -h") !== false || $this->command === "-h";

      // Special handling for help commands
      if ($is_help_command) {
        $full_command = "WP_CLI_PAGER='' PAGER='' " . $executable . $this->command . " --color=auto 2>&1";
      } elseif ($is_db_command) {
        // For database commands, add debugging information
        $debug_env = getenv("PATH");
        $debug_info["path_env"] = $debug_env;

        // Try to specify MySQL binary directory directly in the command for DB operations
        if (!empty($mysql_bin_dir)) {
          $full_command = "WP_CLI_MYSQL_BINDIR=" . escapeshellarg($mysql_bin_dir) . " " . $executable . $this->command . " 2>&1";
        } else {
          $full_command = $executable . $this->command . " 2>&1";
        }
      } else {
        $full_command = $executable . $this->command . " 2>&1";
      }

      // Execute the command
      $output = [];
      $exit_code = 0;

      if (function_exists("exec")) {
        exec($full_command, $output, $exit_code);
        $output_text = implode("\n", $output);

        // If output is empty but exit code is 0, it might be a help command that didn't produce expected output
        if (empty($output) && $exit_code === 0 && $is_help_command && function_exists("passthru")) {
          ob_start();
          passthru($full_command, $passthru_exit_code);
          $output_text = ob_get_clean();
          $exit_code = $passthru_exit_code;
        }
      } else {
        // Fallback if exec is disabled
        $output_text = "Error: The exec() function is disabled on this server, WP-CLI cannot be executed through this interface.";
        $exit_code = 1;
      }
    }

    // For debugging, if the command failed and is a db command, provide more information
    if ($exit_code !== 0 && $is_db_command) {
      // Add debug info to the output
      $output_text .= "\n\nDebug info: " . json_encode($debug_info, JSON_PRETTY_PRINT);
    }

    return [
      "success" => $exit_code === 0,
      "output" => $output_text,
      "error" => $exit_code !== 0 ? $output_text : "",
      "status" => $exit_code,
      "command" => $this->command,
      "paths" => [
        "php" => $this->php_executable,
        "php_ini" => $this->php_ini_path,
        "wp_cli" => $this->wp_cli_phar,
        "wp_path" => $this->wp_path,
        "bin_path" => $mysql_bin_dir,
        "debug" => $debug_info,
      ],
    ];
  }
}
