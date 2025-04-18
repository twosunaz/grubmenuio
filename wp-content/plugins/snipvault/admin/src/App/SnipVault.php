<?php
namespace SnipVault\App;
use SnipVault\Options\GlobalOptions;
use SnipVault\Pages\Editor;
use SnipVault\PostTypes\Snippets;
use SnipVault\PostTypes\SnippetsRevisons;
use SnipVault\PostTypes\Folders;
use SnipVault\Core\SnippetExecutor;
use SnipVault\Rest\ExportEndpoint;
use SnipVault\Rest\ClaudeEndpoint;
use SnipVault\Rest\FileSystemEndpoint;
use SnipVault\Rest\CLIEndpoint;
use SnipVault\Rest\SCSSCompiler;
use SnipVault\Rest\SQLEditorEndpoint;
use SnipVault\Rest\ErrorLog;
use SnipVault\Utility\Scripts;
use SnipVault\Update\Updater;

// Prevent direct access to this file
defined("ABSPATH") || exit();

/**
 * Class SnipVault
 *
 * Main class for initialising the snipvault app.
 */
class SnipVault
{
  /**
   * snipvault constructor.
   *
   * Initialises the main app.
   */
  public function __construct()
  {
    new Editor();
    new Snippets();
    new SnippetExecutor();
    new ExportEndpoint();
    new ClaudeEndpoint();
    new SnippetsRevisons();
    new Folders();
    new GlobalOptions();
    new Updater();
    new FileSystemEndpoint();
    new CLIEndpoint();
    new SQLEditorEndpoint();
    new SCSSCompiler();
    new ErrorLog();

    add_action("init", [$this, "languages_loader"]);
    add_filter("plugin_action_links", [$this, "add_settings_link"], 10, 2);
  }

  /**
   * Loads translation files
   *
   * @since 1.0.8
   */
  public static function languages_loader()
  {
    load_plugin_textdomain("snipvault", false, dirname(dirname(dirname(dirname(plugin_basename(__FILE__))))) . "/languages");
  }

  /**
   * Add custom action link beneath plugin name
   *
   * @param array $links Array of plugin action links
   * @param string $plugin_file Path to the plugin file
   * @return array Modified array of plugin action links
   */
  function add_settings_link($links, $plugin_file)
  {
    // Make sure this only runs for your plugin
    if ("snipvault/snipvault.php" == $plugin_file) {
      // Add your custom link
      $custom_link = '<a href="' . admin_url("options-general.php?page=snipvault") . '">Settings</a>';

      // Add to the beginning of the links array
      array_unshift($links, $custom_link);

      // Alternatively, add to the end of the links array
      // $links[] = $custom_link;
    }

    return $links;
  }
}
