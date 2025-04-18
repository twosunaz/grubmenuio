<?php
namespace SnipVault\Update;

// Prevent direct access to this file
!defined("ABSPATH") ? exit() : "";

class Updater
{
  private static $version = snipvault_plugin_version;
  private static $transient = "snipvault-update-transient";
  private static $transientFailed = "snipvault-failed-transient";
  private static $updateURL = "https://api.snipvault.co/v1/update/latest";
  private static $expiry = 1 * HOUR_IN_SECONDS;
  private static $timeout = 10;

  /**
   * Adds actions and filters to update hooks
   *
   * @since 2.2.0
   */
  public function __construct()
  {
    add_filter("plugins_api", ["SnipVault\Update\Updater", "plugin_info"], 20, 3);
    add_filter("site_transient_update_plugins", ["SnipVault\Update\Updater", "push_update"]);
    add_action("upgrader_process_complete", ["SnipVault\Update\Updater", "after_update"], 10, 2);
  }

  /**
   * Fetches plugin update info
   *
   * @param object $res
   * @param string $action
   * @param object $args
   * @return object
   * @since 2.2.0
   */
  public static function plugin_info($res, $action, $args)
  {
    if ("plugin_information" !== $action) {
      return $res;
    }

    if (true == get_transient(self::$transientFailed)) {
      return $res;
    }

    $plugin_slug = "snipvault";

    if ($plugin_slug !== $args->slug) {
      return $res;
    }

    $remote = self::get_remote_data();

    if (is_wp_error($remote)) {
      return $res;
    }

    $remote = json_decode($remote["body"]);

    $res = new \stdClass();

    $res->name = $remote->name;
    $res->slug = $plugin_slug;
    $res->version = $remote->version;
    $res->tested = $remote->tested;
    $res->requires = $remote->requires;
    $res->download_link = $remote->download_url;
    $res->trunk = $remote->download_url;
    $res->requires_php = "7.4";
    $res->last_updated = $remote->last_updated;
    $res->sections = [
      "description" => $remote->sections->description,
      "installation" => $remote->sections->installation,
      "changelog" => $remote->sections->changelog,
    ];

    if (!empty($remote->sections->screenshots)) {
      $res->sections["screenshots"] = $remote->sections->screenshots;
    }

    $res->banners = [
      "low" => $remote->banners->low,
      "high" => $remote->banners->high,
    ];

    return $res;
  }

  /**
   * Retrieves remote data from the update URL
   *
   * @return array|WP_Error
   * @since 3.2.0
   */
  private static function get_remote_data()
  {
    if (false == ($remote = get_transient(self::$transient))) {
      $remote = wp_remote_get(self::$updateURL, [
        "timeout" => self::$timeout,
        "headers" => [
          "Accept" => "application/json",
        ],
      ]);

      if (self::is_response_clean($remote)) {
        set_transient(self::$transient, $remote, self::$expiry);
      } else {
        set_transient(self::$transientFailed, true, self::$expiry);
        return new \WP_Error("remote_error", "Failed to retrieve remote data.");
      }
    } else {
      $remote = get_transient(self::$transient);
      if (!self::is_response_clean($remote)) {
        set_transient(self::$transientFailed, true, self::$expiry);
        return new \WP_Error("cache_error", "Failed to retrieve data from cache.");
      }
    }

    return $remote;
  }

  /**
   * Checks if the response is clean and valid
   *
   * @param object $status
   * @return bool
   * @since 3.2.0
   */
  private static function is_response_clean($status)
  {
    if (isset($status->errors)) {
      return false;
    }

    if (isset($status["response"]["code"]) && $status["response"]["code"] != 200) {
      return false;
    }

    if (is_wp_error($status)) {
      return false;
    }

    return true;
  }

  /**
   * Pushes plugin update to the plugin table
   *
   * @param object $transient
   * @return object
   * @since 1.4
   */
  public static function push_update($transient)
  {
    if (empty($transient->checked)) {
      return $transient;
    }

    if (true == get_transient(self::$transientFailed)) {
      return $transient;
    }

    $remote = self::get_remote_data();

    if (is_wp_error($remote)) {
      return $transient;
    }

    $remote = json_decode($remote["body"]);

    if ($remote && version_compare(self::$version, $remote->version, "<")) {
      $res = new \stdClass();
      $res->slug = "snipvault";
      $res->plugin = "snipvault/snipvault.php";
      $res->new_version = $remote->version;
      $res->tested = $remote->tested;
      $res->package = $remote->download_url;
      $transient->response[$res->plugin] = $res;
    } else {
      // If there's no update, add the plugin to the 'no_update' list
      $transient->no_update["snipvault/snipvault.php"] = (object) self::getNoUpdateItemFields();
    }

    return $transient;
  }

  /**
   * Get fields for the no update item
   *
   * @return array
   */
  private static function getNoUpdateItemFields()
  {
    return [
      "new_version" => self::$version,
      "url" => "", // You can add a URL to your plugin's page if you want
      "package" => "",
      "requires_php" => "8", // Adjust this to your plugin's PHP requirement
      "requires" => "5.0", // Adjust this to your plugin's WordPress requirement
      "icons" => [], // Add icons if you have them
      "banners" => [], // Add banners if you have them
      "banners_rtl" => [], // Add RTL banners if you have them
      "tested" => "6.7", // Adjust this to the latest WordPress version you've tested with
      "id" => "snipvault/snipvault.php",
    ];
  }

  /**
   * Cleans cache after update
   *
   * @param object $upgrader_object
   * @param array $options
   * @since 1.4
   */
  public static function after_update($upgrader_object, $options)
  {
    if ($options["action"] == "update" && $options["type"] === "plugin") {
      if (isset(self::$upgrade_transient)) {
        delete_transient(self::$upgrade_transient);
      }
    }
  }
}
