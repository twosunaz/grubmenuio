<?php
namespace SnipVaultExport\Core;

// Prevent direct access
defined("ABSPATH") || exit();

class SnippetLoader
{
  private static $instance = null;
  private $snippets_data = [];
  private $plugin_dir;
  private $assets_url;
  private $user_styles = [];
  private $user_scripts = [];
  private $user_html = [];

  /**
   * Private constructor to prevent direct instantiation
   */
  public function __construct()
  {
    $this->plugin_dir = plugin_dir_path(dirname(__FILE__));
    $this->assets_url = plugin_dir_url(dirname(__FILE__)) . "assets/";
    $this->load_snippets_data();

    // Register hooks
    add_action("plugins_loaded", [$this, "execute_php_snippets"], 10);
    add_action("wp_enqueue_scripts", [$this, "enqueue_frontend_assets"]);
    add_action("admin_enqueue_scripts", [$this, "enqueue_admin_assets"]);

    add_action("wp_head", [$this, "output_html_head"]);
    add_action("wp_footer", [$this, "output_html_footer"]);
    add_action("admin_head", [$this, "output_html_head"]);
    add_action("admin_footer", [$this, "output_html_footer"]);
  }

  /**
   * Load all snippets data from JSON files
   */
  private function load_snippets_data()
  {
    $snippets_dir = $this->plugin_dir . "snippets/";
    $data_file = $snippets_dir . "snippets-index.json";

    if (file_exists($data_file)) {
      $this->snippets_data = json_decode(file_get_contents($data_file), true);

      $this->snippets_data = array_filter($this->snippets_data, function ($snippet) {
        return $snippet["auto_load"];
      });
    }
  }

  /**
   * Execute all PHP snippets
   */
  public function execute_php_snippets()
  {
    foreach ($this->snippets_data as $snippet) {
      if ($snippet["type"] !== "php") {
        continue;
      }

      // Get file path
      $file_path = $this->plugin_dir . "assets/" . $snippet["file"];

      $has_conditions = isset($snippet["settings"]["conditions"]) && !empty($snippet["settings"]["conditions"]);
      $default_hook = $has_conditions ? "wp" : "plugins_loaded";
      $hook = isset($snippet["settings"]["hook"]) && $snippet["settings"]["hook"] ? $snippet["settings"]["hook"] : $default_hook;

      // We are already on plugins loaded so execute directly
      if ($hook === "plugins_loaded") {
        // Check conditions and access restrictions
        if (!$this->should_load_snippet($snippet)) {
          continue;
        }

        // Execute PHP file
        if (file_exists($file_path)) {
          $this->safe_include($file_path);
        }
      } else {
        add_action($hook, function () use ($snippet, $file_path) {
          // Check conditions and access restrictions
          if (!$this->should_load_snippet($snippet)) {
            return;
          }

          // Execute PHP file
          if (file_exists($file_path)) {
            $this->safe_include($file_path);
          }
        });
      }
    }
  }

  /**
   * Check if a snippet should be loaded based on conditions
   */
  private function should_load_snippet($snippet)
  {
    // Check user access conditions
    $load_for = isset($snippet["settings"]["load_for"]) ? $snippet["settings"]["load_for"] : "everyone";
    $location = isset($snippet["settings"]["location"]) ? $snippet["settings"]["location"] : "everywhere";

    if ($load_for === "logged_in" && !is_user_logged_in()) {
      return false;
    }

    if ($load_for === "logged_out" && is_user_logged_in()) {
      return false;
    }

    // Only load in admin
    if ($location == "admin" && !is_admin()) {
      return false;
    }

    // Only load in front
    if ($location == "frontend" && is_admin()) {
      return false;
    }

    // Check capability
    $capability = isset($snippet["settings"]["capability"]) ? $snippet["settings"]["capability"] : "";
    if (!empty($capability) && !current_user_can($capability)) {
      return false;
    }

    // Check advanced conditions
    if (!empty($snippet["settings"]["conditions"])) {
      require_once $this->plugin_dir . "core/conditions-evaluator.php";
      $evaluator = new \SnipVaultExport\CustomPlugin\Core\ConditionsEvaluator();
      if (!$evaluator->evaluate($snippet["settings"]["conditions"])) {
        return false;
      }
    }

    return true;
  }

  /**
   * Safely include a PHP file
   */
  private function safe_include($file_path)
  {
    // Start output buffering to prevent unwanted output
    ob_start();

    try {
      include_once $file_path;
    } catch (\Throwable $e) {
      error_log("SnipVault Export Plugin Error: " . $e->getMessage());
    }

    // End output buffering
    ob_end_clean();
  }

  /**
   * Enqueue frontend assets (CSS and JS)
   */
  public function enqueue_frontend_assets()
  {
    $this->enqueue_assets("frontend");
  }

  /**
   * Enqueue admin assets (CSS and JS)
   */
  public function enqueue_admin_assets()
  {
    $this->enqueue_assets("admin");
  }

  /**
   * Enqueue assets based on location
   */
  private function enqueue_assets($location)
  {
    foreach ($this->snippets_data as $snippet) {
      // Skip if not CSS or JS
      if (!in_array($snippet["type"], ["css", "scss", "js"])) {
        continue;
      }

      // Check location
      if ($snippet["settings"]["location"] === "admin" && $location !== "admin") {
        continue;
      }

      if ($snippet["settings"]["location"] === "frontend" && $location !== "frontend") {
        continue;
      }

      // Check conditions and access restrictions
      if (!$this->should_load_snippet($snippet)) {
        continue;
      }

      // Enqueue based on type
      $file_url = $this->assets_url . $snippet["file"];
      $slug = sanitize_title($snippet["title"]) . "-" . $snippet["id"];

      if (in_array($snippet["type"], ["css", "scss"])) {
        wp_enqueue_style($slug, $file_url, [], $snippet["version"]);
      } elseif ($snippet["type"] === "js") {
        $footer = !empty($snippet["settings"]["footer"]);
        $is_module = !empty($snippet["settings"]["module"]);

        if ($is_module) {
          add_action($location === "admin" ? "admin_footer" : "wp_footer", function () use ($slug, $file_url) {
            printf('<script type="module" id="%s-js" src="%s"></script>', esc_attr($slug), esc_url($file_url));
          });
        } else {
          wp_enqueue_script($slug, $file_url, [], $snippet["version"], $footer);
        }
      }
    }
  }

  /**
   * Output HTML to head
   */
  public function output_html_head()
  {
    $this->output_html(is_admin(), false);
  }

  /**
   * Output HTML to footer
   */
  public function output_html_footer()
  {
    $this->output_html(is_admin(), true);
  }

  /**
   * Output HTML based on location
   */
  private function output_html($is_admin, $is_footer)
  {
    foreach ($this->snippets_data as $snippet) {
      if ($snippet["type"] !== "html") {
        continue;
      }

      // Check location
      if ($snippet["settings"]["location"] === "admin" && !$is_admin) {
        continue;
      }

      if ($snippet["settings"]["location"] === "frontend" && $is_admin) {
        continue;
      }

      // Check footer setting
      $footer = !empty($snippet["settings"]["footer"]);
      if (($footer && !$is_footer) || (!$footer && $is_footer)) {
        continue;
      }

      // Check conditions and access restrictions
      if (!$this->should_load_snippet($snippet)) {
        continue;
      }

      // Output the HTML content
      $file_path = $this->plugin_dir . "assets/" . $snippet["file"];
      if (file_exists($file_path)) {
        echo file_get_contents($file_path);
      }
    }
  }
}
