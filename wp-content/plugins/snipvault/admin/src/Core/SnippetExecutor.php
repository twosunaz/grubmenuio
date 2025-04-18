<?php
namespace SnipVault\Core;
use SnipVault\Core\SnippetErrorHandler;
use SnipVault\Core\ConditionsEvaluator;

// Prevent direct access to this file
defined("ABSPATH") || exit();

/**
 * Class SnippetExecutor
 *
 * Handles the secure execution of PHP snippets
 */
class SnippetExecutor
{
  private static $signing_key;
  private static $user_styles = [];
  private static $user_scripts = [];
  private static $user_html = [];

  /**
   * Constructor
   */
  public function __construct()
  {
    add_action("plugins_loaded", ["SnipVault\Core\SnippetExecutor", "set_signing_key"], 0);
    add_action("plugins_loaded", ["SnipVault\Core\SnippetExecutor", "set_php_globals"], 0);
    add_action("plugins_loaded", ["SnipVault\Core\SnippetExecutor", "execute_active_snippets"], 2);
    add_action("wp_enqueue_scripts", ["SnipVault\Core\SnippetExecutor", "enqueue_front_scripts"]);
    add_action("admin_enqueue_scripts", ["SnipVault\Core\SnippetExecutor", "enqueue_admin_scripts"]);

    add_action("admin_head", ["SnipVault\Core\SnippetExecutor", "output_html_to_head"]);
    add_action("admin_footer", ["SnipVault\Core\SnippetExecutor", "output_html_to_footer"]);
    add_action("wp_head", ["SnipVault\Core\SnippetExecutor", "output_html_to_head"]);
    add_action("wp_footer", ["SnipVault\Core\SnippetExecutor", "output_html_to_footer"]);
  }

  /**
   * Sets signing key
   *
   * @return null
   */
  public static function set_signing_key()
  {
    self::$signing_key = get_option("snipvault_secure_snippets_key");
  }

  /**
   * Sets PHP global variables
   *
   * @return null
   */
  public static function set_php_globals()
  {
    $settings = get_option("snipvault_settings", []);

    if (isset($settings["php_global_variables"]) && is_array($settings["php_global_variables"])) {
      foreach ($settings["php_global_variables"] as $variable) {
        if (isset($variable["key"]) && !empty($variable["key"]) && isset($variable["value"])) {
          // Only define if the constant doesn't already exist
          if (!defined($variable["key"])) {
            define($variable["key"], $variable["value"]);
          }
        }
      }
    }
  }

  /**
   * Get and execute all active PHP snippets
   *
   * @return array Array of execution results
   */
  public static function execute_active_snippets()
  {
    global $pagenow;

    // Stop code execution on snipvault page
    if (is_admin() && current_user_can("manage_options") && $pagenow == "options-general.php" && isset($_GET["page"]) && $_GET["page"] == "snipvault") {
      return;
    }

    // Manually stop SnipVault
    if (defined("SNIPVAULT_DISABLE_PHP_SNIPPETS") && SNIPVAULT_DISABLE_PHP_SNIPPETS) {
      return;
    }

    $results = [];
    $snippets = self::get_active_snippets();

    foreach ($snippets as $snippet) {
      $result = self::execute_single_snippet($snippet);
    }
  }

  /**
   * Get all published PHP snippets
   *
   * @return array Array of snippet posts
   */
  private static function get_active_snippets()
  {
    $cached = get_site_option("snipvault_active_snippets", []);
    return $cached;
  }

  /**
   * Execute a single snippet after verification
   *
   * @param WP_Post $snippet The snippet post object
   * @return mixed Execution result or WP_Error
   */
  private static function execute_single_snippet($snippet)
  {
    try {
      // Get snippet data
      $meta_values = get_post_meta($snippet->ID);

      //error_log("meta: " . $meta_values);

      $content = isset($meta_values["snippet_content"]) ? maybe_unserialize($meta_values["snippet_content"][0]) : "";
      $signature = isset($meta_values["snippet_signature"]) ? maybe_unserialize($meta_values["snippet_signature"][0]) : "";
      $file_path = isset($meta_values["snippet_file_path"]) ? maybe_unserialize($meta_values["snippet_file_path"][0]) : "";
      $snippet_settings = isset($meta_values["snippet_settings"]) ? maybe_unserialize($meta_values["snippet_settings"][0]) : [];

      $language = isset($snippet_settings["language"]) ? $snippet_settings["language"] : "PHP";
      $footer = isset($snippet_settings["footer"]) ? $snippet_settings["footer"] : false;
      $is_module = isset($snippet_settings["module"]) ? $snippet_settings["module"] : false;
      $location = isset($snippet_settings["location"]) ? $snippet_settings["location"] : "everywhere";
      $load_for = isset($snippet_settings["load_for"]) ? $snippet_settings["load_for"] : "everyone";
      $capability = isset($snippet_settings["capability"]) ? $snippet_settings["capability"] : "";

      // Verify signature
      if (!self::verify_snippet($content, $signature)) {
        //return new \WP_Error("invalid_signature", "Snippet signature verification failed.");
        error_Log("verification failed");
        return;
      }

      // Additional security checks
      if (self::contains_dangerous_code($content)) {
        //return new \WP_Error("dangerous_code", "Snippet contains potentially dangerous code.");
        return;
      }

      // Verify file content matches stored content
      if ($file_path && file_exists($file_path)) {
        $file_content = file_get_contents($file_path);
        if ($file_content !== $content) {
          //return new \WP_Error("content_mismatch", "File content does not match stored content.");
          error_log("File content does not match stored content.");
          return;
        }
      }

      // validate load for logic
      if ($load_for === "logged_in" && !is_user_logged_in()) {
        return;
      }
      if ($load_for === "logged_out" && is_user_logged_in()) {
        return;
      }
      // Check capability
      if ($capability && $capability != "" && !current_user_can($capability)) {
        return;
      }

      // Only load in admin
      if ($location == "admin" && !is_admin()) {
        return;
      }

      // Only load in front
      if ($location == "frontend" && is_admin()) {
        return;
      }

      if ($language == "PHP") {
        $has_conditions = isset($snippet_settings["conditions"]) && self::has_valid_conditions($snippet_settings["conditions"]);
        $default_hook = $has_conditions ? "wp" : "plugins_loaded";
        $hook = isset($snippet_settings["hook"]) && $snippet_settings["hook"] ? $snippet_settings["hook"] : $default_hook;

        // We are already on plugins loaded so execute directly
        if ($hook === "plugins_loaded") {
          self::safe_execute_php_file($file_path, $content, $snippet->ID, $snippet_settings);
        } else {
          $snippetID = $snippet->ID;
          add_action($hook, function () use ($file_path, $content, $snippetID, $snippet_settings) {
            self::safe_execute_php_file($file_path, $content, $snippetID, $snippet_settings);
          });
        }
        return;
      }

      // Hanlde css files
      if ($language == "CSS") {
        self::$user_styles[] = [
          "path" => $file_path,
          "id" => $snippet->ID,
          "title" => $snippet->post_title,
          "location" => $location,
          "footer" => $footer,
          "settings" => $snippet_settings,
        ];
        return;
      }

      // Hanlde SCSS files
      if ($language == "SCSS") {
        $file_path = self::replaceScssExtension($file_path);
        self::$user_styles[] = [
          "path" => $file_path,
          "id" => $snippet->ID,
          "title" => $snippet->post_title,
          "location" => $location,
          "footer" => $footer,
          "settings" => $snippet_settings,
        ];
        return;
      }

      // Handle JavaScript files
      if ($language == "JAVASCRIPT") {
        self::$user_scripts[] = [
          "path" => $file_path,
          "id" => $snippet->ID,
          "title" => $snippet->post_title,
          "location" => $location,
          "footer" => $footer,
          "is_module" => $is_module,
          "settings" => $snippet_settings,
        ];
        return;
      }

      // Handle HTML files
      if ($language == "HTML") {
        self::$user_html[] = [
          "path" => $file_path,
          "id" => $snippet->ID,
          "title" => $snippet->post_title,
          "location" => $location,
          "footer" => $footer,
          "settings" => $snippet_settings,
        ];
        return;
      }
    } catch (\Exception $e) {
      //return new \WP_Error("execution_error", $e->getMessage());
      error_log($e->getMessage());
    }
  }

  /**
   * Check if conditions array has at least one group with at least one condition
   *
   * @param array $conditions The conditions array
   * @return bool True if there's at least one group with at least one condition
   */
  private static function has_valid_conditions($conditions)
  {
    // Check if conditions is set and has groups
    if (empty($conditions) || !isset($conditions["groups"]) || !is_array($conditions["groups"])) {
      return false;
    }

    // Check if any group has at least one condition
    foreach ($conditions["groups"] as $group) {
      if (isset($group["conditions"]) && is_array($group["conditions"]) && !empty($group["conditions"])) {
        return true;
      }
    }

    // No groups with conditions found
    return false;
  }

  /**
   * Replace the last instance of .scss with .css in a file path
   *
   * @param string $path The file path to modify
   * @return string The modified file path
   */
  private static function replaceScssExtension($path)
  {
    // Method 1: Using pathinfo (simplest, most readable)
    $info = pathinfo($path);
    if (strtolower($info["extension"]) === "scss") {
      return $info["dirname"] . "/" . $info["filename"] . ".css";
    }
    return $path;
  }

  /**
   * Outputs user HTML files to the head section
   *
   * Determines if the current page is in admin area and then loads
   * appropriate HTML files accordingly.
   *
   * @return void
   */
  public static function output_html_to_head()
  {
    $admin = is_admin();
    self::load_html_files($admin, true);
  }

  /**
   * Outputs user HTML files to the head section
   *
   * Determines if the current page is in admin area and then loads
   * appropriate HTML files accordingly.
   *
   * @return void
   */
  public static function output_html_to_footer()
  {
    $admin = is_admin();
    self::load_html_files($admin, false);
  }

  /**
   * Loads and outputs HTML files based on their designated location
   *
   * Iterates through HTML files in self::$user_html and outputs their contents
   * based on whether they are designated for admin area or frontend.
   * Files will only be output if they exist at the specified path.
   *
   * @param bool $is_admin Whether the current page is in the admin area
   * @return void
   */
  public static function load_html_files($is_admin, $is_head)
  {
    // Loop HTML files
    foreach (self::$user_html as $html_file) {
      // Front end style
      if ($html_file["location"] == "frontend" && $is_admin) {
        continue;
      }

      // Admin only style
      if ($html_file["location"] == "admin" && !$is_admin) {
        continue;
      }

      // Needs to be loaded in the footer
      if ($html_file["footer"] && $is_head) {
        continue;
      }

      // Not to be loaded in footer so ensure it is only loaded once in the head
      if (!$html_file["footer"] && !$is_head) {
        continue;
      }

      // Match conditions
      if (isset($html_file["settings"]["conditions"])) {
        $conditions = new ConditionsEvaluator();
        $met = $conditions->evaluate($html_file["settings"]["conditions"]);
        if (!$met) {
          continue;
        }
      }

      // File is missing
      if (!file_exists($html_file["path"])) {
        continue;
      }

      // Check path exists
      if (strpos($html_file["path"], "../") !== false || !file_exists($html_file["path"])) {
        continue;
      }

      $max_size = 1024 * 1024; // 1MB limit
      if (filesize($html_file["path"]) > $max_size) {
        continue;
      }

      // AEnsure mime type
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime_type = finfo_file($finfo, $html_file["path"]);
      finfo_close($finfo);

      if (strpos($mime_type, "text/") !== 0) {
        continue;
      }

      // Output the file contents
      echo file_get_contents($html_file["path"]);
    }
  }

  /**
   * Enqueues scripts and styles for the frontend of the site.
   *
   * Calls load_styles_and_scripts() with "admin" as the exclusion parameter
   * to ensure only frontend assets are loaded.
   *
   * @return void
   */
  public static function enqueue_front_scripts()
  {
    self::load_styles_and_scripts("admin");
  }

  /**
   * Enqueues scripts and styles for the WordPress admin area.
   *
   * Calls load_styles_and_scripts() with "frontend" as the exclusion parameter
   * to ensure only admin assets are loaded.
   *
   * @return void
   */
  public static function enqueue_admin_scripts()
  {
    self::load_styles_and_scripts("frontend");
  }

  /**
   * Loads registered styles and scripts based on their intended location.
   *
   * Processes the static arrays $user_styles and $user_scripts, converting file paths
   * to URLs and enqueuing them with WordPress if they don't match the excluded location.
   * Uses the snippet's modified time as the version number for cache busting.
   *
   * @param string $location_not The location to exclude ('admin' or 'frontend')
   * @return void
   *
   * @uses wp_upload_dir()
   * @uses get_post_meta()
   * @uses wp_enqueue_style()
   * @uses wp_enqueue_script()
   * @uses self::slugify()
   *
   * @access private
   * @static
   */
  private static function load_styles_and_scripts($location_not)
  {
    // Get the upload directory info
    $upload_dir = wp_upload_dir();

    foreach (self::$user_styles as $style) {
      // Admin only style
      if ($style["location"] == $location_not) {
        continue;
      }

      // Match conditions
      if (isset($style["settings"]["conditions"])) {
        $conditions = new ConditionsEvaluator();
        $met = $conditions->evaluate($style["settings"]["conditions"]);
        if (!$met) {
          continue;
        }
      }

      // Convert the file path to a URL by replacing the base directory path with the base URL
      $file_url = str_replace($upload_dir["basedir"], $upload_dir["baseurl"], $style["path"]);

      $ver = get_post_meta($style["id"], "snippet_modified", true);

      wp_enqueue_style(self::slugify($style["title"]), $file_url, [], $ver);
    }

    // Loop javascript
    foreach (self::$user_scripts as $script) {
      // Admin only style
      if ($script["location"] == $location_not) {
        continue;
      }

      // Match conditions
      if (isset($script["settings"]["conditions"])) {
        $conditions = new ConditionsEvaluator();
        $met = $conditions->evaluate($script["settings"]["conditions"]);
        if (!$met) {
          continue;
        }
      }

      // Convert the file path to a URL by replacing the base directory path with the base URL
      $file_url = str_replace($upload_dir["basedir"], $upload_dir["baseurl"], $script["path"]);

      $ver = get_post_meta($script["id"], "snippet_modified", true);

      if ($script["is_module"]) {
        // Setup script object

        $script_function = function () use ($script, $ver, $file_url) {
          $builderScript = [
            "id" => esc_attr(self::slugify($script["title"])),
            "type" => esc_attr("module"),
            "src" => esc_url($file_url . "?ver={$ver}"),
          ];
          wp_print_script_tag($builderScript);
        };

        if ($script["footer"]) {
          add_action("admin_footer", $script_function);
        } else {
          $script_function();
        }
      } else {
        wp_enqueue_script(self::slugify($script["title"]), $file_url, [], $ver, $script["footer"]);
      }
    }
  }

  /**
   * Converts a string into a URL-friendly slug.
   *
   * Transforms a string by:
   * 1. Converting to lowercase
   * 2. Transliterating special characters to ASCII
   * 3. Replacing non-alphanumeric characters with hyphens
   * 4. Removing consecutive hyphens
   * 5. Trimming hyphens from start and end
   *
   * @param string $string The string to convert into a slug
   * @return string The formatted slug
   *
   * @access private
   * @static
   */
  private static function slugify($string)
  {
    $slug = strtolower(trim($string));
    $slug = iconv("UTF-8", "ASCII//TRANSLIT", $slug);
    $slug = preg_replace("/[^a-z0-9-]/", "-", $slug);
    $slug = preg_replace("/-+/", "-", $slug);
    $slug = trim($slug, "-");

    return $slug;
  }

  /**
   * Safely executes a PHP file containing a function definition.
   *
   * This function attempts to safely load and execute a PHP file containing a function
   * definition by:
   * 1. Checking if the function already exists to prevent redefinition
   * 2. Using output buffering to prevent unwanted output
   * 3. Handling any errors that occur during execution
   *
   * @param string $file_path The path to the PHP file to be executed
   * @param string $content   The content of the PHP file, used to extract function name
   *
   * @return void
   *
   * @throws \Throwable Catches but logs any errors that occur during file execution
   *
   * @uses SnippetErrorHandler::start_snippet_execution()
   * @uses SnippetErrorHandler::end_snippet_execution()
   * @uses ob_start()
   * @uses ob_end_clean()
   * @uses function_exists()
   * @uses preg_match()
   */
  private static function safe_execute_php_file($file_path, $content, $snippet_id, $snippet_settings)
  {
    // Match conditions
    if (isset($snippet_settings["conditions"])) {
      $conditions = new ConditionsEvaluator();
      $met = $conditions->evaluate($snippet_settings["conditions"]);
      if (!$met) {
        return;
      }
    }

    // Extract all function declarations from the content
    if (preg_match_all('/function\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\(/i', $content, $matches)) {
      $function_names = $matches[1];

      // Check for duplicates within the snippet
      $duplicate_functions = array_filter(array_count_values($function_names), function ($count) {
        return $count > 1;
      });

      if (!empty($duplicate_functions)) {
        $duplicate_list = implode(", ", array_keys($duplicate_functions));

        $error = [
          "message" => "Duplicate function declaration(s) found: {$duplicate_list}",
          "code" => 0,
          "file" => $file_path,
          "line" => 0,
          "timestamp" => current_time("timestamp", true),
          "type" => "WARNING",
        ];
        self::log_error($error, $snippet_id);

        //error_log("Duplicate function declaration(s) found: {$duplicate_list} - skipping snippet execution");
        return;
      }

      // Check if any function already exists in global scope
      foreach ($function_names as $function_name) {
        if (function_exists($function_name)) {
          $error = [
            "message" => "Function {$function_name} already exists",
            "code" => 0,
            "file" => $file_path,
            "line" => 0,
            "timestamp" => current_time("timestamp", true),
            "type" => "WARNING",
          ];
          self::log_error($error, $snippet_id);

          //error_log("Function {$function_name} already exists - skipping snippet execution");
          return;
        }
      }
    }

    // Start output buffering
    ob_start();

    // Mark that we're executing a snippet
    SnippetErrorHandler::start_snippet_execution();

    try {
      require_once $file_path;
    } catch (\Throwable $e) {
      // Catch it here
      //error_log($e->getCode());

      $error = [
        "message" => $e->getMessage(),
        "code" => $e->getCode(),
        "file" => $e->getFile(),
        "line" => $e->getLine(),
        "timestamp" => current_time("timestamp", true),
        "type" => self::getErrorType(error_get_last()["type"] ?? E_ERROR),
      ];
      self::log_error($error, $snippet_id);
      //error_log("Snippet execution error: " . $e->getMessage());
    }

    // End snippet execution mode
    SnippetErrorHandler::end_snippet_execution();

    // Clean the output buffer
    ob_end_clean();
  }

  // Returns error type
  private static function getErrorType($code)
  {
    switch ($code) {
      case E_ERROR:
      case E_CORE_ERROR:
      case E_COMPILE_ERROR:
      case E_USER_ERROR:
        return "FATAL";
      case E_WARNING:
      case E_CORE_WARNING:
      case E_COMPILE_WARNING:
      case E_USER_WARNING:
        return "WARNING";
      case E_NOTICE:
      case E_USER_NOTICE:
        return "NOTICE";
      case E_PARSE:
        return "PARSE";
      case E_DEPRECATED:
      case E_USER_DEPRECATED:
        return "DEPRECATED";
      default:
        return "UNKNOWN";
    }
  }

  // Logs errors from php snippets
  private static function log_error($error, $snippet_id)
  {
    // Get current errors
    $errors = get_post_meta($snippet_id, "snippet_errors", true);

    // Ensure we're working with an array
    if (!is_array($errors)) {
      $errors = [];
    }

    // Add timestamp to new error if not present
    if (!isset($error["timestamp"])) {
      $error["timestamp"] = current_time("timestamp");
    }

    // Add new error to the array
    $errors[] = $error;

    // Sort errors by timestamp, newest first
    usort($errors, function ($a, $b) {
      $a_time = isset($a["timestamp"]) ? $a["timestamp"] : 0;
      $b_time = isset($b["timestamp"]) ? $b["timestamp"] : 0;
      return $b_time - $a_time;
    });

    // Keep only the most recent 20 errors
    if (count($errors) > 20) {
      $errors = array_slice($errors, 0, 20);
    }

    // Update the post meta
    update_post_meta($snippet_id, "snippet_errors", $errors);
  }

  /**
   * Verify snippet signature
   */
  private static function verify_snippet($content, $signature)
  {
    $expected_signature = hash_hmac("sha256", $content, self::$signing_key);
    return hash_equals($expected_signature, $signature);
  }

  /**
   * Check for potentially dangerous code patterns
   */
  private static function contains_dangerous_code($content)
  {
    $dangerous_patterns = [
      "system\(",
      "exec\(",
      "shell_exec\(",
      "passthru\(",
      "eval\(",
      "base64_decode\(",
      "create_function\(",
      "proc_open\(",
      "pcntl_exec\(",
      "assert\(",
      "preg_replace.*\/e",
      'include\($_',
      'require\($_',
      'include_once\($_',
      'require_once\($_',
      'file_get_contents\($_',
      'file_put_contents\($_',
      "unlink\(",
      "rmdir\(",
      "mkdir\(",
      "chmod\(",
      "chown\(",
    ];

    $pattern = "/(" . implode("|", $dangerous_patterns) . ")/i";
    return preg_match($pattern, $content) === 1;
  }
}
