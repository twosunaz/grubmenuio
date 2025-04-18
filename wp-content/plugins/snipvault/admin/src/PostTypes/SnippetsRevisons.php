<?php
namespace SnipVault\PostTypes;

// Prevent direct access to this file
defined("ABSPATH") || exit();

/**
 * Track post meta changes in revisions
 */
class SnippetsRevisons
{
  // Meta keys we want to track in revisions
  private $meta_keys = ["snippet_content"];

  public function __construct()
  {
    // Save post meta to revision
    add_action("_wp_put_post_revision", [$this, "save_meta_to_revision"]);

    // Restore post meta when revision is restored
    add_action("wp_restore_post_revision", [$this, "restore_meta_from_revision"], 10, 2);

    // Add meta data to revision data that gets compared
    add_filter("_wp_post_revision_fields", [$this, "add_meta_to_revision_fields"], 10, 2);
  }

  /**
   * Save post meta to revision
   */
  public function save_meta_to_revision($revision_id)
  {
    $revision = get_post($revision_id);
    $post_id = $revision->post_parent;

    // Save each meta value to the revision
    foreach ($this->meta_keys as $meta_key) {
      $meta_value = get_post_meta($post_id, $meta_key, true);
      if (!empty($meta_value)) {
        add_metadata("post", $revision_id, $meta_key, $meta_value);
      }
    }
  }

  /**
   * Add meta to revision fields
   */
  public function add_meta_to_revision_fields($fields, $post)
  {
    foreach ($this->meta_keys as $meta_key) {
      $fields[$meta_key] = ucwords(str_replace("_", " ", $meta_key));

      // Add callback for this specific meta key
      add_filter(
        "_wp_post_revision_field_{$meta_key}",
        function ($value, $field, $revision, $type) use ($meta_key) {
          return $this->get_meta_value($value, $field, $revision, $type);
        },
        10,
        4
      );
    }

    return $fields;
  }

  /**
   * Get meta value for revision field
   */
  public function get_meta_value($value, $field, $revision, $type)
  {
    if (in_array($field, $this->meta_keys)) {
      $value = get_metadata("post", $revision->ID, $field, true);
    }
    return $value;
  }

  /**
   * Restore post meta from revision
   */
  public function restore_meta_from_revision($post_id, $revision_id)
  {
    foreach ($this->meta_keys as $meta_key) {
      $meta_value = get_metadata("post", $revision_id, $meta_key, true);
      if (!empty($meta_value)) {
        update_post_meta($post_id, $meta_key, $meta_value);
      }
    }
  }
}
