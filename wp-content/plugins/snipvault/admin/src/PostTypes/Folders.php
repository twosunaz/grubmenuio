<?php
namespace SnipVault\PostTypes;

// Prevent direct access to this file
defined("ABSPATH") || exit();

/**
 * Class Folders
 *
 * Handles folder management for SnipVault snippets
 */
class Folders
{
  /**
   * Constructor.
   *
   * Initializes the folders functionality
   */
  public function __construct()
  {
    add_action("init", [__CLASS__, "register_post_type"]);
    add_action("init", [__CLASS__, "register_meta_fields"]);
    add_action("rest_api_init", [__CLASS__, "register_meta_fields"]);

    // Handle folder operations
  }

  /**
   * Register the folders post type
   */
  public static function register_post_type()
  {
    $args = self::return_post_type_args();
    register_post_type("snipvault-folders", $args);
  }

  /**
   * Register meta fields for the folders post type
   */
  public static function register_meta_fields()
  {
    register_post_meta("snipvault-folders", "folder_parent", [
      "type" => "integer",
      "description" => "Parent folder ID",
      "single" => true,
      "show_in_rest" => [
        "schema" => [
          "type" => "integer",
          "description" => "The ID of the parent folder",
          "default" => 0,
        ],
      ],
      "default" => 0,
      "sanitize_callback" => "absint",
      "auth_callback" => function () {
        return current_user_can("manage_options");
      },
    ]);

    // Add physical path meta
    register_post_meta("snipvault-folders", "physical_path", [
      "type" => "string",
      "description" => "Physical path of the folder on server",
      "single" => true,
      "show_in_rest" => false, // Don't expose in REST API for security
      "auth_callback" => function () {
        return current_user_can("manage_options");
      },
    ]);
  }

  /**
   * Convert string to URL-friendly slug
   */
  private static function slugify($string)
  {
    $slug = strtolower(trim($string));
    $slug = iconv("UTF-8", "ASCII//TRANSLIT", $slug);
    $slug = preg_replace("/[^a-z0-9-]/", "-", $slug);
    $slug = preg_replace("/-+/", "-", $slug);
    $slug = trim($slug, "-");

    return sanitize_title($slug);
  }

  /**
   * Return post type arguments
   */
  private static function return_post_type_args()
  {
    $labels = [
      "name" => _x("Folders", "post type general name", "snipvault"),
      "singular_name" => _x("Folder", "post type singular name", "snipvault"),
      "menu_name" => _x("Folders", "admin menu", "snipvault"),
      "name_admin_bar" => _x("Folder", "add new on admin bar", "snipvault"),
      "add_new" => _x("Add New", "Folder", "snipvault"),
      "add_new_item" => __("Add New Folder", "snipvault"),
      "new_item" => __("New Folder", "snipvault"),
      "edit_item" => __("Edit Folder", "snipvault"),
      "view_item" => __("View Folder", "snipvault"),
      "all_items" => __("All Folders", "snipvault"),
      "search_items" => __("Search Folders", "snipvault"),
      "not_found" => __("No folders found.", "snipvault"),
      "not_found_in_trash" => __("No folders found in Trash.", "snipvault"),
    ];

    $args = [
      "labels" => $labels,
      "description" => __("Folders for organizing SnipVault snippets", "snipvault"),
      "public" => false,
      "publicly_queryable" => false,
      "show_ui" => false,
      "show_in_menu" => false,
      "query_var" => false,
      "has_archive" => false,
      "hierarchical" => true,
      "supports" => ["title", "custom-fields"],
      "show_in_rest" => true,
      "rest_base" => "svfolders",
    ];

    return $args;
  }
}
