<?php
namespace SnipVault\PostTypes;
use ScssPhp\ScssPhp\Compiler;

// Prevent direct access to this file
defined("ABSPATH") || exit();

/**
 * Class Snippets
 *
 * Main class for managing the SnipVault snippets custom post type.
 * Handles creation, storage, security and management of code snippets in WordPress.
 *
 * Features include:
 * - Custom post type registration for snippets
 * - Meta fields for snippet content, settings and error tracking
 * - File-based storage of snippet contents in the uploads directory
 * - Security features including content signatures and key rotation
 * - REST API integration for CRUD operations
 * - Caching mechanism for active snippets
 *
 * @package SnipVault\PostTypes
 * @since 1.0.0
 */
class Snippets
{
  /**
   * Constructor.
   *
   * Initializes the Snippets custom post type and registers all necessary
   * WordPress hooks and filters to manage snippet lifecycle.
   *
   * @since 1.0.0
   * @access public
   * @return void
   */
  public function __construct()
  {
    add_action("init", ["SnipVault\PostTypes\Snippets", "register_post_type"]);
    add_action("init", ["SnipVault\PostTypes\Snippets", "register_meta_fields"]);
    add_action("rest_api_init", ["SnipVault\PostTypes\Snippets", "register_meta_fields"]);

    // Add REST API update handler
    add_action("rest_after_insert_snipvault-snippets", [$this, "update_snippet_signature"], 10, 2);
    add_action("delete_post", [$this, "delete_snippet_file"], 10, 1);

    // Watch for changes and update cached list of snippets
    // Fires when a post is saved or updated
    add_action("save_post_snipvault-snippets", ["SnipVault\PostTypes\Snippets", "handle_snippet_save"], 10, 3);

    // Fires before a post is deleted
    add_action("before_delete_post", ["SnipVault\PostTypes\Snippets", "handle_snippet_delete"], 10, 1);

    // Hook into the scheduled event to perform key rotation
    add_action("snipvault_weekly_key_rotation", ["SnipVault\PostTypes\Snippets", "rotate_signing_key"]);
  }

  /**
   * Schedules weekly key rotation using WordPress cron.
   *
   * Sets up a weekly cron job to rotate the signing key used for
   * snippet content verification, enhancing security.
   *
   * @since 1.0.0
   * @access public
   * @static
   * @return void
   */
  public static function schedule_key_rotation()
  {
    if (!wp_next_scheduled("snipvault_weekly_key_rotation")) {
      wp_schedule_event(time(), "weekly", "snipvault_weekly_key_rotation");
    }
  }

  /**
   * Cleans up scheduled events when plugin is deactivated.
   *
   * Removes any scheduled key rotation events from the WordPress cron system
   * when the plugin is deactivated to prevent orphaned tasks.
   *
   * @since 1.0.0
   * @access public
   * @static
   * @return void
   */
  public static function deactivate_scheduled_events()
  {
    $timestamp = wp_next_scheduled("snipvault_weekly_key_rotation");
    if ($timestamp) {
      wp_unschedule_event($timestamp, "snipvault_weekly_key_rotation");
    }
  }

  /**
   * Handles snippet save events.
   *
   * Called when a snippet is saved or updated to refresh the
   * cached list of active snippets.
   *
   * @since 1.0.0
   * @access public
   * @static
   * @param int     $post_id The post ID.
   * @param WP_Post $post    Post object.
   * @param bool    $update  Whether this is an existing post being updated.
   * @return void
   */
  public static function handle_snippet_save($post_id, $post, $update)
  {
    self::cache_active_snippets();
  }

  /**
   * Handles snippet deletion events.
   *
   * Called before a snippet is deleted to refresh the
   * cached list of active snippets.
   *
   * @since 1.0.0
   * @access public
   * @static
   * @param int $post_id The post ID being deleted.
   * @return void
   */
  public static function handle_snippet_delete($post_id)
  {
    self::cache_active_snippets();
  }

  /**
   * Caches all active snippets.
   *
   * Retrieves all published snippets and stores them in a site option
   * for faster access during runtime.
   *
   * @since 1.0.0
   * @access private
   * @static
   * @return void
   */
  private static function cache_active_snippets()
  {
    $args = [
      "post_type" => "snipvault-snippets",
      "post_status" => "publish",
      "posts_per_page" => -1,
    ];

    $posts = get_posts($args);

    update_site_option("snipvault_active_snippets", $posts);
  }

  /**
   * Triggers a post revision when meta is updated.
   *
   * Monitors changes to tracked meta fields and creates a post
   * revision when they are modified.
   *
   * @since 1.0.0
   * @access public
   * @param int    $meta_id    ID of the metadata entry.
   * @param int    $post_id    Post ID.
   * @param string $meta_key   Metadata key.
   * @param mixed  $_meta_value Metadata value.
   * @return void
   */
  public function trigger_meta_revision($meta_id, $post_id, $meta_key, $_meta_value)
  {
    // Check if this is for our post type
    if (get_post_type($post_id) !== "snipvault-snippets") {
      return;
    }

    // List of meta keys we want to track
    $tracked_keys = ["snippet_content"];

    // Only proceed if this is a tracked meta key
    if (!in_array($meta_key, $tracked_keys)) {
      return;
    }
  }

  /**
   * Deletes the snippet file when the post is deleted.
   *
   * Removes the associated file from the filesystem when
   * a snippet post is deleted to prevent orphaned files.
   *
   * @since 1.0.0
   * @access public
   * @param int $post_id The post ID being deleted.
   * @return void
   */
  public function delete_snippet_file($post_id)
  {
    // Get post type
    $post_type = get_post_type($post_id);

    // Only proceed if this is a snippet post type
    if ($post_type !== "snipvault-snippets") {
      return;
    }

    // Delete old file if it exists
    self::cleanup_old_files($post_id);
  }

  /**
   * Updates snippet signature when post is updated via REST.
   *
   * When a snippet is updated through the REST API, this method:
   * - Verifies user permissions
   * - Retrieves or generates a signing key
   * - Writes snippet content to a file
   * - Generates and stores a signature for content verification
   * - Creates a post revision
   *
   * @since 1.0.0
   * @access public
   * @param WP_Post         $post    Post object.
   * @param WP_REST_Request $request Request object.
   * @return WP_Error|void WP_Error on failure, void on success.
   */
  public function update_snippet_signature($post, $request)
  {
    if (!current_user_can("manage_options")) {
      return new \WP_Error("insufficient_permissions", "You do not have permission to modify snippets.");
    }
    // Get the signing key
    $signing_key = get_option("snipvault_secure_snippets_key");
    if (!$signing_key) {
      // Generate a new key if none exists
      $signing_key = bin2hex(random_bytes(32));
      update_option("snipvault_secure_snippets_key", $signing_key);
    }

    // Get the content to sign
    $content = get_post_meta($post->ID, "snippet_content", true);
    if (!empty($content)) {
      // Write content to file
      $file_path = $this->write_snippet_to_file($post->ID, $content);
      if (is_wp_error($file_path)) {
        return $file_path;
      }

      // Generate signature
      $signature = hash_hmac("sha256", $content, $signing_key);

      // Update the signature and modified timestamp
      update_post_meta($post->ID, "snippet_signature", $signature);
      update_post_meta($post->ID, "snippet_modified", time());
      update_post_meta($post->ID, "snippet_file_path", $file_path);
    }

    wp_save_post_revision($post->ID);
  }

  /**
   * Rotates the signing key for enhanced security.
   *
   * Generates a new signing key and re-signs all existing snippets
   * with the new key to maintain content verification integrity.
   * Runs on a scheduled basis via WordPress cron.
   *
   * @since 1.0.0
   * @access public
   * @static
   * @return bool True on successful key rotation.
   */
  public static function rotate_signing_key()
  {
    $old_key = get_option("snipvault_secure_snippets_key");
    $new_key = bin2hex(random_bytes(32));

    // Re-sign all snippets with the new key
    $snippets = get_posts([
      "post_type" => "snipvault-snippets",
      "posts_per_page" => -1,
    ]);

    foreach ($snippets as $snippet) {
      $content = get_post_meta($snippet->ID, "snippet_content", true);
      if (!empty($content)) {
        $signature = hash_hmac("sha256", $content, $new_key);
        update_post_meta($snippet->ID, "snippet_signature", $signature);
      }
    }

    // Save the new key
    update_option("snipvault_secure_snippets_key", $new_key);

    // Add a log entry for auditing
    error_log("SnipVault: Security key rotation completed at " . current_time("mysql"));

    return true;
  }

  /**
   * Writes snippet content to a file in the uploads directory.
   *
   * Creates a secure file with the snippet content and appropriate
   * file extension based on the snippet language. Implements security
   * measures like .htaccess protection to prevent direct access.
   *
   * @since 1.0.0
   * @access private
   * @static
   * @param int    $post_id The post ID.
   * @param string $content The snippet content.
   * @return string|WP_Error The file path on success, WP_Error on failure.
   */
  private static function write_snippet_to_file($post_id, $content)
  {
    // Initialize WP Filesystem
    global $wp_filesystem;
    if (!function_exists("WP_Filesystem")) {
      require_once ABSPATH . "wp-admin/includes/file.php";
    }
    WP_Filesystem();

    // Get upload directory
    $upload_dir = wp_upload_dir();
    $snippets_dir = $upload_dir["basedir"] . "/snipvault";

    // Create snippets directory if it doesn't exist
    if (!file_exists($snippets_dir)) {
      if (!wp_mkdir_p($snippets_dir)) {
        return new \WP_Error("directory_creation_failed", "Failed to create snippets directory");
      }
    }

    if (!file_exists($snippets_dir . "/.htaccess")) {
      // Create .htaccess to prevent direct access
      $htaccess_content =
        "# Set default to deny access\nOrder Deny,Allow\nDeny from all\n\n# Allow access to CSS files\n<Files ~ \"\\.css$\">\n    Allow from all\n</Files>\n\n# Allow access to JavaScript files\n<Files ~ \"\\.js$\">\n    Allow from all\n</Files>";
      if (!$wp_filesystem->put_contents($snippets_dir . "/.htaccess", $htaccess_content)) {
        return new \WP_Error("htaccess_creation_failed", "Failed to create .htaccess file");
      }
    }

    if (!file_exists($snippets_dir . "/index.php")) {
      // Create index.php for additional security
      $index_content = "<?php\n// Silence is golden";
      if (!$wp_filesystem->put_contents($snippets_dir . "/index.php", $index_content)) {
        return new \WP_Error("index_creation_failed", "Failed to create index.php file");
      }
    }

    // Generate unique filename
    $snippet_settings = get_post_meta($post_id, "snippet_settings", true) ?: [];
    $language = $snippet_settings["language"] ?: "php";
    $language = strtolower($language);
    $language = $language === "javascript" ? "js" : $language;
    $extension = $language === "css" || $language === "js" || $language === "scss" ? $language : "php";

    // Post title
    $snippet_title = get_the_title($post_id) ?: "snippet";
    $slug_name = self::slugify($snippet_title);

    $filename = sprintf("%s/%s_%d.%s", $snippets_dir, $slug_name, $post_id, $extension);

    // Delete old file if it exists
    self::cleanup_old_files($post_id);

    // Write new file
    if (!$wp_filesystem->put_contents($filename, $content)) {
      return new \WP_Error("file_write_failed", "Failed to write snippet file");
    }

    if ($extension == "scss") {
      $compiled_filename = sprintf("%s/%s_%d.%s", $snippets_dir, $slug_name, $post_id, "css");

      $compiled_css = self::compileScssString($content);

      if (is_wp_error($compiled_css)) {
        return $compiled_css;
      }

      // Write new file
      if ($compiled_css) {
        if (!$wp_filesystem->put_contents($compiled_filename, $compiled_css)) {
          return new \WP_Error("file_write_failed", "Failed to write snippet file");
        }
      }
    }

    return $filename;
  }

  /**
   * Compiles SCSS string to CSS
   *
   * @param string $scssString The SCSS code to compile
   * @param array $variables Optional variables to pass to the compiler
   * @return string The compiled CSS
   */
  private static function compileScssString($scssString)
  {
    $compiler = new Compiler();

    // Set import path
    $upload_dir = wp_upload_dir();
    $snippets_dir = $upload_dir["basedir"] . "/snipvault";
    $compiler->setImportPaths($snippets_dir);

    // Compile the SCSS string to CSS
    try {
      return $compiler->compileString($scssString)->getCss();
    } catch (\Exception $e) {
      return new \WP_Error("Error compiling SCSS:", $e->getMessage());
      //error_log("Error compiling SCSS: " . $e->getMessage());
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
   * @since 1.0.0
   * @access private
   * @static
   * @param string $string The string to convert into a slug.
   * @return string The formatted slug.
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
   * Cleans up old snippet files from the filesystem.
   *
   * Removes any previous files associated with a specific snippet ID
   * to prevent orphaned or duplicate files.
   *
   * @since 1.0.0
   * @access private
   * @static
   * @param int $post_id The post ID to clean up files for.
   * @return void
   */
  private static function cleanup_old_files($post_id)
  {
    global $wp_filesystem;

    // Initialize WP Filesystem if not already done
    if (!function_exists("WP_Filesystem")) {
      require_once ABSPATH . "wp-admin/includes/file.php";
    }
    WP_Filesystem();

    // Get upload directory
    $upload_dir = wp_upload_dir();
    $snippets_dir = $upload_dir["basedir"] . "/snipvault";

    // Find all files matching this post ID pattern
    $files = glob($snippets_dir . "/*_" . $post_id . ".*");
    if ($files) {
      foreach ($files as $file) {
        $wp_filesystem->delete($file);
      }
    }
  }

  /**
   * Registers the custom post type for snippets.
   *
   * Creates the 'snipvault-snippets' custom post type with
   * appropriate labels and settings. The post type is not
   * publicly visible but is accessible via the REST API.
   *
   * @since 1.0.0
   * @access public
   * @static
   * @return void
   */
  public static function register_post_type()
  {
    $args = self::return_post_type_args();
    register_post_type("snipvault-snippets", $args);
  }

  /**
   * Registers meta fields for the snippet post type.
   *
   * Sets up all custom meta fields needed for the snippets functionality:
   * - snippet_content: The actual code content
   * - snippet_settings: Configuration options for the snippet
   * - snippet_errors: Error tracking information
   * - snippet_signature: Security signature for verifying content
   * - snippet_file_path: Path to the file storage location
   * - snippet_modified: Last modified timestamp
   * - snippet_author_info: Additional author metadata
   *
   * Includes proper sanitization and authorization callbacks for each field.
   *
   * @since 1.0.0
   * @access public
   * @static
   * @return void
   */
  public static function register_meta_fields()
  {
    // Register code content field
    register_post_meta("snipvault-snippets", "snippet_content", [
      "type" => "string",
      "description" => "The content of the code snippet",
      "single" => true,
      "show_in_rest" => true,
      "sanitize_callback" => function ($content) {
        // First decode any HTML entities that might be present
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, "UTF-8");

        // Basic UTF-8 validation
        $content = wp_check_invalid_utf8($content);

        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // Remove UTF-8 BOM if present
        $content = str_replace("\xEF\xBB\xBF", "", $content);

        // Check for null bytes which could be used in PHP exploitation
        if (strpos($content, "\0") !== false) {
          return ""; // Reject content with null bytes
        }

        // Optional: Limit maximum snippet size to prevent DB abuse
        if (strlen($content) > 1000000) {
          // 1MB limit example
          return substr($content, 0, 1000000);
        }

        return $content;
      },
      "auth_callback" => function () {
        return current_user_can("manage_options");
      },
    ]);

    // Snippet settings
    register_post_meta("snipvault-snippets", "snippet_settings", [
      "type" => "object",
      "description" => "Settings array for snippets",
      "single" => true,
      "default" => [
        "language" => "PHP",
        "hook" => "",
        "footer" => false,
        "module" => false,
        "location" => "everywhere",
        "description" => "",
        "version" => "1.0.0",
        "default" => 0,
        "load_for" => "everyone",
        "capability" => "",
        "conditions" => [
          "operator" => "AND",
          "groups" => [],
        ],
      ],
      "show_in_rest" => [
        "schema" => [
          "type" => "object",
          "properties" => [
            "language" => [
              "type" => "string",
            ],
            "hook" => [
              "type" => "string",
            ],
            "footer" => [
              "type" => "boolean",
            ],
            "module" => [
              "type" => "boolean",
            ],
            "location" => [
              "type" => "string",
            ],
            "description" => [
              "type" => "string",
            ],
            "version" => [
              "type" => "string",
            ],
            "folder" => [
              "type" => "integer",
            ],
            "load_for" => [
              "type" => "string",
            ],
            "capability" => [
              "type" => "string",
            ],
            "conditions" => [
              "type" => "object",
              "required" => false,
              "default" => [
                "operator" => "AND",
                "groups" => [],
              ],
              "properties" => [
                "operator" => [
                  "type" => "string",
                  "enum" => ["AND", "OR"],
                ],
                "groups" => [
                  "type" => "array",
                  "items" => [
                    "type" => "object",
                    "properties" => [
                      "operator" => [
                        "type" => "string",
                        "enum" => ["AND", "OR"],
                      ],
                      "name" => [
                        "type" => "string",
                      ],
                      "conditions" => [
                        "type" => "array",
                        "items" => [
                          "type" => "object",
                          "properties" => [
                            "field" => [
                              "type" => "string",
                            ],
                            "comparison" => [
                              "type" => "string",
                            ],
                            "value" => [
                              "type" => ["string", "number", "boolean", "array"],
                            ],
                          ],
                          "required" => ["field", "comparison", "value"],
                        ],
                      ],
                    ],
                    "required" => ["operator", "conditions"],
                  ],
                ],
              ],
              "required" => ["operator", "groups"],
            ],
          ],
        ],
      ],
      "sanitize_callback" => ["SnipVault\PostTypes\Snippets", "sanitize_snippet_settings"],
      "auth_callback" => function () {
        return current_user_can("manage_options");
      },
    ]);

    // Snippet settings
    register_post_meta("snipvault-snippets", "snippet_errors", [
      "type" => "array",
      "description" => "Errors array for snippets",
      "single" => true,
      "default" => [],
      "show_in_rest" => [
        "prepare_callback" => function ($value) {
          return !empty($value) ? $value : [];
        },
        "schema" => [
          "type" => "array",
          "items" => [
            "type" => "object",
            "properties" => [
              "message" => [
                "type" => "string",
                "required" => true,
              ],
              "code" => [
                "type" => "number",
                "required" => true,
              ],
              "file" => [
                "type" => "string",
                "required" => true,
              ],
              "line" => [
                "type" => "number",
                "required" => true,
              ],
              "timestamp" => [
                "type" => "number",
                "required" => true,
              ],
              "type" => [
                "type" => "string",
                "required" => true,
              ],
            ],
          ],
        ],
      ],
      "sanitize_callback" => ["SnipVault\PostTypes\Snippets", "sanitize_snippet_errors"],
      "auth_callback" => function () {
        return current_user_can("manage_options");
      },
    ]);

    // Snippet settings
    register_post_meta("snipvault-snippets", "ai_messages", [
      "type" => "array",
      "description" => "Messages array for ai chats",
      "single" => true,
      "default" => [],
      "show_in_rest" => [
        "prepare_callback" => function ($value) {
          return !empty($value) ? $value : [];
        },
        "schema" => [
          "type" => "array",
          "items" => [
            "type" => "object",
            "properties" => [
              "id" => [
                "type" => ["string", "number"],
                "required" => true,
              ],
              "role" => [
                "type" => "string",
                "required" => true,
              ],
              "content" => [
                "type" => "string",
                "required" => true,
              ],
              "timestamp" => [
                "type" => "number",
                "required" => true,
              ],
            ],
          ],
        ],
      ],
      "sanitize_callback" => ["SnipVault\PostTypes\Snippets", "sanitize_snippet_ai_chats"],
      "auth_callback" => function () {
        return current_user_can("manage_options");
      },
    ]);

    // Register signature field
    register_post_meta("snipvault-snippets", "snippet_signature", [
      "type" => "string",
      "description" => "The security signature of the snippet",
      "single" => true,
      "show_in_rest" => true,
      "sanitize_callback" => "sanitize_text_field",
      "auth_callback" => function () {
        return current_user_can("manage_options");
      },
    ]);

    // Register file path field
    register_post_meta("snipvault-snippets", "snippet_file_path", [
      "type" => "string",
      "description" => "The file system path to the snippet file",
      "single" => true,
      "show_in_rest" => true,
      "sanitize_callback" => "sanitize_text_field",
      "auth_callback" => function () {
        return current_user_can("manage_options");
      },
    ]);

    // Register last modified timestamp
    register_post_meta("snipvault-snippets", "snippet_modified", [
      "type" => "integer",
      "description" => "The last modified timestamp of the snippet",
      "single" => true,
      "show_in_rest" => true,
      "sanitize_callback" => "absint",
      "auth_callback" => function () {
        return current_user_can("manage_options");
      },
    ]);

    // Register author information
    register_post_meta("snipvault-snippets", "snippet_author_info", [
      "type" => "object",
      "description" => "Additional author information for the snippet",
      "single" => true,
      "show_in_rest" => [
        "schema" => [
          "type" => "object",
          "properties" => [
            "author_name" => [
              "type" => "string",
            ],
            "author_email" => [
              "type" => "string",
            ],
            "author_url" => [
              "type" => "string",
            ],
          ],
        ],
      ],
      "auth_callback" => function () {
        return current_user_can("manage_options");
      },
    ]);
  }

  /**
   * Sanitizes snippet error information.
   *
   * Cleans and validates the error data structure to ensure
   * it contains only safe, expected values.
   *
   * @since 1.0.0
   * @access public
   * @static
   * @param array $errors Array of error objects to sanitize.
   * @return array Sanitized array of error objects.
   */
  public static function sanitize_snippet_errors($errors)
  {
    // If not array or empty, return empty array
    if (!is_array($errors)) {
      return [];
    }

    // Sanitize each error object
    return array_map(function ($error) {
      // Ensure error is an array/object
      if (!is_array($error)) {
        return null;
      }

      // Initialize sanitized error with default values
      $sanitized_error = [
        "message" => "",
        "code" => "",
        "file" => "",
        "line" => "",
        "timestamp" => "",
        "type" => "",
      ];

      // Sanitize message
      if (isset($error["message"])) {
        $sanitized_error["message"] = sanitize_text_field($error["message"]);
      }

      // Sanitize error code
      if (isset($error["code"])) {
        $sanitized_error["code"] = sanitize_text_field($error["code"]);
      }

      // Sanitize file path
      if (isset($error["file"])) {
        // Remove any potentially harmful characters from file path
        $sanitized_error["file"] = sanitize_text_field($error["file"]);
      }

      // Sanitize error type
      if (isset($error["type"])) {
        // Remove any potentially harmful characters from error type
        $sanitized_error["type"] = sanitize_text_field($error["type"]);
      }

      // Sanitize time stamp
      if (isset($error["timestamp"])) {
        // Remove any potentially harmful characters from time stamp
        $sanitized_error["timestamp"] = sanitize_text_field($error["timestamp"]);
      }

      // Sanitize line number
      if (isset($error["line"])) {
        // Ensure line is a valid number or string representation of a number
        $line = sanitize_text_field($error["line"]);
        $sanitized_error["line"] = is_numeric($line) ? $line : "";
      }

      return $sanitized_error;
    }, $errors);
  }

  /**
   * Sanitizes the snippet settings array for the SnipVault plugin.
   *
   * This function processes and sanitizes all fields in the snippet settings object,
   * ensuring each field is properly cleaned and type-cast according to its expected format.
   * It handles string sanitization using WordPress sanitize_text_field and proper boolean casting.
   * The conditions object is sanitized recursively to ensure all nested groups and conditions are valid.
   *
   * @since 1.0.0
   * @access public
   * @static
   * @param array $settings The settings array to sanitize.
   * @return array Sanitized settings array. Returns default array if input is not an array.
   *               Keys will only be present if they existed in input array.
   *
   * @filter snipvault_sanitize_snippet_settings Filters the sanitized settings
   *         @param array $sanitized The sanitized settings array
   *         @param array $settings  The original settings array
   */
  public static function sanitize_snippet_settings($settings)
  {
    // If settings is not an array/object, return the default settings
    if (!is_array($settings)) {
      return [
        "language" => "PHP",
        "footer" => false,
        "module" => false,
        "location" => "everywhere",
        "description" => "",
        "version" => "",
        "folder" => 0,
        "hook" => "",
        "load_for" => "everyone",
        "capability" => "",
        "conditions" => [
          "operator" => "AND",
          "groups" => [],
        ],
      ];
    }

    $sanitized = [];

    // Ensure we have at least default values for all fields
    $defaults = [
      "language" => "PHP",
      "footer" => false,
      "module" => false,
      "location" => "everywhere",
      "description" => "",
      "version" => "",
      "folder" => 0,
      "hook" => "",
      "load_for" => "everyone",
      "capability" => "",
      "conditions" => [
        "operator" => "AND",
        "groups" => [],
      ],
    ];

    // Merge defaults with incoming settings
    $settings = wp_parse_args($settings, $defaults);

    // Sanitize language (string)
    if (isset($settings["language"])) {
      $sanitized["language"] = sanitize_text_field($settings["language"]);
    }

    // Sanitize footer (boolean)
    if (isset($settings["footer"])) {
      $sanitized["footer"] = (bool) $settings["footer"];
    }

    // Sanitize module (boolean)
    if (isset($settings["module"])) {
      $sanitized["module"] = (bool) $settings["module"];
    }

    // Sanitize location (string)
    if (isset($settings["location"])) {
      $sanitized["location"] = sanitize_text_field($settings["location"]);
    }

    // Sanitize location (string)
    if (isset($settings["hook"])) {
      $sanitized["hook"] = sanitize_text_field($settings["hook"]);
    }

    // Sanitize version (string)
    if (isset($settings["version"])) {
      $sanitized["version"] = sanitize_text_field($settings["version"]);
    }

    // Sanitize description (string)
    if (isset($settings["description"])) {
      $sanitized["description"] = sanitize_text_field($settings["description"]);
    }

    // Sanitize folder (integer)
    if (isset($settings["folder"])) {
      $sanitized["folder"] = absint($settings["folder"]);
    }

    // Sanitize load_for (string)
    if (isset($settings["load_for"])) {
      $sanitized["load_for"] = sanitize_text_field($settings["load_for"]);
    }

    // Sanitize capability (string)
    if (isset($settings["capability"])) {
      $sanitized["capability"] = sanitize_text_field($settings["capability"]);
    }

    // Sanitize conditions (object)
    if (isset($settings["conditions"])) {
      $sanitized["conditions"] = self::sanitize_conditions($settings["conditions"]);
    }

    // Apply WordPress filters to allow additional sanitization
    return apply_filters("snipvault_sanitize_snippet_settings", $sanitized, $settings);
  }

  /**
   * Sanitizes the conditions object for snippet settings.
   *
   * Recursively sanitizes the hierarchical conditions structure containing
   * groups and individual conditions with proper operators.
   *
   * @since 1.0.0
   * @access private
   * @static
   * @param array $conditions The conditions object to sanitize.
   * @return array Sanitized conditions object.
   */
  private static function sanitize_conditions($conditions)
  {
    // If not an array, return a default empty conditions structure
    if (!is_array($conditions)) {
      return [
        "operator" => "AND",
        "groups" => [],
      ];
    }

    $sanitized = [];

    // Sanitize top-level operator (string: AND or OR)
    if (isset($conditions["operator"])) {
      $operator = strtoupper(sanitize_text_field($conditions["operator"]));
      // Ensure operator is either AND or OR
      $sanitized["operator"] = in_array($operator, ["AND", "OR"]) ? $operator : "AND";
    } else {
      $sanitized["operator"] = "AND"; // Default
    }

    // Sanitize groups array
    if (isset($conditions["groups"]) && is_array($conditions["groups"])) {
      $sanitized["groups"] = [];

      foreach ($conditions["groups"] as $group) {
        if (!is_array($group)) {
          continue; // Skip invalid groups
        }

        $sanitized_group = [];

        // Sanitize group operator
        if (isset($group["operator"])) {
          $group_operator = strtoupper(sanitize_text_field($group["operator"]));
          $sanitized_group["operator"] = in_array($group_operator, ["AND", "OR"]) ? $group_operator : "AND";
        } else {
          $sanitized_group["operator"] = "AND"; // Default
        }

        // Sanitize group name if present
        if (isset($group["name"])) {
          $sanitized_group["name"] = sanitize_text_field($group["name"]);
        }

        // Sanitize conditions within this group
        if (isset($group["conditions"]) && is_array($group["conditions"])) {
          $sanitized_group["conditions"] = [];

          foreach ($group["conditions"] as $condition) {
            if (!is_array($condition)) {
              continue; // Skip invalid conditions
            }

            $sanitized_condition = [];

            // Sanitize field (string)
            if (isset($condition["field"])) {
              $sanitized_condition["field"] = sanitize_text_field($condition["field"]);
            } else {
              continue; // Skip conditions without a field
            }

            // Sanitize comparison (string)
            if (isset($condition["comparison"])) {
              $sanitized_condition["comparison"] = sanitize_text_field($condition["comparison"]);
            } else {
              continue; // Skip conditions without a comparison
            }

            // Sanitize value (could be various types)
            if (isset($condition["value"])) {
              $value = $condition["value"];

              // Handle different value types
              if (is_bool($value)) {
                $sanitized_condition["value"] = (bool) $value;
              } elseif (is_numeric($value)) {
                $sanitized_condition["value"] = is_float($value) ? (float) $value : (int) $value;
              } elseif (is_string($value)) {
                $sanitized_condition["value"] = sanitize_text_field($value);
              } elseif (is_array($value)) {
                // For array values, sanitize each element
                $sanitized_condition["value"] = array_map("sanitize_text_field", $value);
              } else {
                // Default fallback - convert to string
                $sanitized_condition["value"] = sanitize_text_field((string) $value);
              }
            } else {
              continue; // Skip conditions without a value
            }

            $sanitized_group["conditions"][] = $sanitized_condition;
          }
        } else {
          $sanitized_group["conditions"] = []; // Default empty conditions array
        }

        // Only add groups that have at least one valid condition or have a name
        if (!empty($sanitized_group["conditions"]) || isset($sanitized_group["name"])) {
          $sanitized["groups"][] = $sanitized_group;
        }
      }
    } else {
      $sanitized["groups"] = []; // Default empty groups array
    }

    return $sanitized;
  }

  /**
   * Sanitize AI chat messages for storing as post meta
   *
   * @param array $messages Array of chat message objects
   * @return array Sanitized message objects
   */
  public static function sanitize_snippet_ai_chats($messages)
  {
    if (!is_array($messages)) {
      return [];
    }

    $sanitized_messages = [];

    foreach ($messages as $message) {
      // Skip invalid messages
      if (!is_array($message) || !isset($message["id"]) || !isset($message["role"]) || !isset($message["content"]) || !isset($message["timestamp"])) {
        continue;
      }

      // Sanitize and validate each field
      $sanitized_message = [
        // Ensure ID is a string (could be a UUID)
        "id" => sanitize_text_field($message["id"]),

        // Only allow valid roles
        "role" => in_array($message["role"], ["user", "assistant", "system"]) ? $message["role"] : "user",

        // Allow some HTML formatting but prevent unsafe tags
        "content" => wp_kses_post($message["content"]),

        // Ensure timestamp is numeric and valid
        "timestamp" => is_numeric($message["timestamp"]) ? $message["timestamp"] : time() * 1000, // Default to current time in milliseconds if invalid
      ];

      $sanitized_messages[] = $sanitized_message;
    }

    // Limit the number of messages to prevent excessive data storage
    // Adjust the limit as needed for your use case
    $max_messages = 100;
    if (count($sanitized_messages) > $max_messages) {
      $sanitized_messages = array_slice($sanitized_messages, -$max_messages);
    }

    return $sanitized_messages;
  }

  /**
   * Returns post type arguments for the snippets custom post type.
   *
   * Defines all labels and configuration settings for the
   * 'snipvault-snippets' custom post type registration.
   *
   * @since 3.2.13
   * @access private
   * @static
   * @return array Array of post type arguments.
   */
  private static function return_post_type_args()
  {
    $labels = [
      "name" => _x("Snippet", "post type general name", "snipvault"),
      "singular_name" => _x("Snippet", "post type singular name", "snipvault"),
      "menu_name" => _x("Snippets", "admin menu", "snipvault"),
      "name_admin_bar" => _x("Snippet", "add new on admin bar", "snipvault"),
      "add_new" => _x("Add New", "Template", "snipvault"),
      "add_new_item" => __("Add New Snippet", "snipvault"),
      "new_item" => __("New Snippet", "snipvault"),
      "edit_item" => __("Edit Snippet", "snipvault"),
      "view_item" => __("View Snippet", "snipvault"),
      "all_items" => __("All Snippets", "snipvault"),
      "search_items" => __("Search Snippets", "snipvault"),
      "not_found" => __("No Snippets found.", "snipvault"),
      "not_found_in_trash" => __("No Snippets found in Trash.", "snipvault"),
    ];

    $args = [
      "labels" => $labels,
      "description" => __("Post type used for SnipVault snippets", "snipvault"),
      "public" => false,
      "publicly_queryable" => false,
      "show_ui" => false,
      "show_in_menu" => false,
      "query_var" => false,
      "has_archive" => false,
      "hierarchical" => false,
      "supports" => ["title", "custom-fields", "revisions", "comments"],
      "show_in_rest" => true,
      "rest_base" => "svsnippets",
    ];

    return $args;
  }
}
