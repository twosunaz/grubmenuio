<?php
namespace SnipVault\Rest;

// Prevent direct access to this file
defined("ABSPATH") || exit();

class ExportEndpoint
{
  public function __construct()
  {
    add_action("rest_api_init", [$this, "register_endpoints"]);
  }

  /**
   * Register REST API endpoints
   */
  public function register_endpoints()
  {
    register_rest_route("snipvault/v1", "/export", [
      "methods" => "POST",
      "callback" => [$this, "handle_export"],
      "permission_callback" => [$this, "check_permission"],
      "args" => [
        "snippet_ids" => [
          "required" => true,
          "type" => "array",
          "items" => [
            "type" => "integer",
          ],
          "sanitize_callback" => function ($ids) {
            return array_map("absint", $ids);
          },
          "validate_callback" => function ($ids) {
            return !empty($ids) && is_array($ids);
          },
        ],
        "plugin_name" => [
          "required" => true,
          "type" => "string",
          "sanitize_callback" => "sanitize_text_field",
        ],
        "plugin_description" => [
          "required" => true,
          "type" => "string",
          "sanitize_callback" => "sanitize_text_field",
        ],
        "plugin_author" => [
          "required" => true,
          "type" => "string",
          "sanitize_callback" => "sanitize_text_field",
        ],
      ],
    ]);
  }

  /**
   * Check if user has permission to export
   */
  public function check_permission()
  {
    return current_user_can("manage_options");
  }

  /**
   * Handle export request
   */
  public function handle_export(\WP_REST_Request $request)
  {
    $snippet_ids = $request->get_param("snippet_ids");
    $plugin_name = $request->get_param("plugin_name");
    $plugin_author = $request->get_param("plugin_author");
    $plugin_description = $request->get_param("plugin_description");

    // Validate that all IDs are valid snippets
    foreach ($snippet_ids as $id) {
      $post_type = get_post_type($id);
      if ($post_type !== "snipvault-snippets") {
        return new \WP_Error("invalid_snippet", sprintf("Invalid snippet ID: %d", $id), ["status" => 400]);
      }
    }

    try {
      // Create the plugin package
      $packager = new \SnipVault\Core\PluginPackager();
      $result = $packager->create_plugin($snippet_ids, $plugin_name, $plugin_author, $plugin_description);

      if (is_wp_error($result)) {
        return $result;
      }

      return [
        "success" => true,
        "download_url" => $result,
      ];
    } catch (\Exception $e) {
      return new \WP_Error("export_failed", $e->getMessage(), ["status" => 500]);
    }
  }
}
