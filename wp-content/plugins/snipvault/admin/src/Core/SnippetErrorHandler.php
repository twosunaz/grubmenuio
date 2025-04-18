<?php
/**
 * Error handler for custom snippets execution.
 *
 * This class provides a custom error handling mechanism for safely executing
 * code snippets within WordPress without crashing the entire site when
 * fatal errors occur.
 *
 * @package SnipVault\Core
 * @since 1.0.0
 */
namespace SnipVault\Core;

// Prevent direct access to this file
defined("ABSPATH") || exit();

/**
 * Class SnippetErrorHandler
 *
 * Manages error handling during custom snippet execution to prevent
 * fatal errors from breaking the entire WordPress site.
 */
class SnippetErrorHandler
{
  /**
   * Flag to track if a snippet is currently being executed.
   *
   * @var bool
   */
  private static $is_executing_snippet = false;

  /**
   * Stores the original WordPress error handler state.
   *
   * @var bool|null
   */
  private static $original_handler = null;

  /**
   * Initialize the error handler.
   *
   * Sets up the custom error handling system by storing the original handler,
   * adding our filter, and registering a shutdown function.
   *
   * @return void
   */
  public static function init()
  {
    // Store the original error handler
    self::$original_handler = \wp_fatal_error_handler_enabled();
    // Add our custom handler
    add_filter("wp_fatal_error_handler_enabled", [self::class, "maybe_disable_wp_handler"], 10, 1);
    // Register our own shutdown function
    register_shutdown_function([self::class, "handle_fatal_error"]);
  }

  /**
   * Mark the beginning of snippet execution.
   *
   * Sets the flag to indicate that a snippet is being executed.
   *
   * @return void
   */
  public static function start_snippet_execution()
  {
    self::$is_executing_snippet = true;
  }

  /**
   * Mark the end of snippet execution.
   *
   * Resets the flag to indicate that snippet execution has completed.
   *
   * @return void
   */
  public static function end_snippet_execution()
  {
    self::$is_executing_snippet = false;
  }

  /**
   * Conditionally disable WordPress error handler.
   *
   * Determines whether to use the WordPress default error handler or
   * disable it based on whether a snippet is currently executing.
   *
   * @param bool $enabled The current state of the WordPress error handler.
   * @return bool Modified state of the WordPress error handler.
   */
  public static function maybe_disable_wp_handler($enabled)
  {
    // Disable WP's handler during snippet execution
    if (self::$is_executing_snippet) {
      return false;
    }
    return $enabled;
  }

  /**
   * Custom handler for fatal errors during snippet execution.
   *
   * Catches fatal errors that occur during snippet execution, logs them,
   * and allows the rest of the WordPress page to continue loading normally.
   * This prevents snippets from breaking the entire site when they contain errors.
   *
   * @return void
   */
  public static function handle_fatal_error()
  {
    if (!self::$is_executing_snippet) {
      return;
    }

    $error = error_get_last();
    if ($error && in_array($error["type"], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
      // Clean any output that might have occurred
      if (ob_get_length()) {
        ob_end_clean();
      }

      // Log the error
      error_log(sprintf("Snippet Fatal Error: %s in %s on line %d", $error["message"], $error["file"], $error["line"]));

      // Allow normal page execution to continue
      self::$is_executing_snippet = false;
    }
  }
}
