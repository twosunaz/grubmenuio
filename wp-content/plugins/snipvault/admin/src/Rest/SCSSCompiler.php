<?php
namespace SnipVault\Rest;
use ScssPhp\ScssPhp\Compiler;
// Prevent direct access to this file
defined("ABSPATH") || exit();

/**
 * Class SCSScompiler
 *
 * Handles SCSS compilers
 *
 * @package SnipVault\Rest
 */
class SCSSCompiler
{
  /**
   * Constructor
   */
  public function __construct()
  {
    add_action("rest_api_init", [$this, "register_endpoints"]);
  }

  /**
   * Register REST API endpoints
   */
  public function register_endpoints()
  {
    register_rest_route("snipvault/v1", "/scss/compile", [
      "methods" => "POST",
      "callback" => [$this, "handle_scss_compile"],
      "permission_callback" => [$this, "check_permission"],
      "args" => [
        "scss" => [
          "required" => true,
          "type" => "string",
          "sanitize_callback" => [$this, "sanitize_scss"],
        ],
      ],
    ]);
  }

  /**
   * Sanitize SCSS input
   *
   * @param string $scss The SCSS content to sanitize
   * @return string Sanitized SCSS
   */
  public function sanitize_scss($scss)
  {
    // Remove potential PHP tags
    $scss = str_replace(["<?", "?>"], ["&lt;?", "?&gt;"], $scss);

    // Basic input validation - set a reasonable size limit
    if (strlen($scss) > 100000) {
      // 100KB limit
      return substr($scss, 0, 100000);
    }

    return $scss;
  }

  /**
   * Check if user has permission to use SCSS compiler
   */
  public function check_permission()
  {
    return current_user_can("manage_options");
  }

  /**
   * Handle SCSS compilation request
   */
  public function handle_scss_compile(\WP_REST_Request $request)
  {
    $scssString = $request->get_param("scss");

    // Add rate limiting
    $user_id = get_current_user_id();
    $rate_key = "scss_compile_" . $user_id;
    $rate_count = get_transient($rate_key) ?: 0;

    if ($rate_count > 40) {
      // Limit to 20 compilations per hour
      return new \WP_Error("rate_limit_exceeded", "Too many compilation requests. Please try again later.", ["status" => 429]);
    }

    // Update rate limit counter
    set_transient($rate_key, $rate_count + 1, HOUR_IN_SECONDS);

    try {
      $compiler = new Compiler();

      // Set import path
      $upload_dir = wp_upload_dir();
      $snippets_dir = $upload_dir["basedir"] . "/snipvault";
      $compiler->setImportPaths($snippets_dir);

      // Set memory limit option if available in your SCSS compiler
      if (method_exists($compiler, "setMaxMemory")) {
        $compiler->setMaxMemory("64M");
      }

      // Compile the SCSS string to CSS
      try {
        $start_time = microtime(true);
        $css = $compiler->compileString($scssString)->getCss();
        $execution_time = microtime(true) - $start_time;

        // Log if compilation takes too long
        if ($execution_time > 2) {
          error_log("SCSS compilation took {$execution_time} seconds");
        }

        return [
          "success" => true,
          "compiled" => $css,
          "execution_time" => round($execution_time, 3),
        ];
      } catch (\Exception $e) {
        return new \WP_Error("scss_compilation_error", $e->getMessage(), ["status" => 400]);
      }
    } catch (\Exception $e) {
      return new \WP_Error("scss_compiler_error", $e->getMessage(), ["status" => 500]);
    }
  }
}
