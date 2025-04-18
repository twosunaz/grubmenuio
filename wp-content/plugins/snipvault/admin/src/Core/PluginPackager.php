<?php namespace SnipVault\Core;

// Prevent direct access to this file
defined("ABSPATH") || exit();

class PluginPackager
{
  private $upload_dir;
  private $temp_dir;
  private $plugin_dir;
  private static $plugin_name;
  private static $plugin_author;
  private static $plugin_description;
  private static $plugin_version = "1.0.0";
  private $core_files = [
    "snippet-loader.php" => "core/snippet-loader.php",
    "conditions-evaluator.php" => "core/conditions-evaluator.php",
  ];

  public function __construct()
  {
    $upload_info = wp_upload_dir();
    $this->upload_dir = $upload_info["basedir"];
    $this->temp_dir = $this->upload_dir . "/snipvault-temp";

    // Create temp directory if it doesn't exist
    if (!file_exists($this->temp_dir)) {
      wp_mkdir_p($this->temp_dir);

      file_put_contents($this->temp_dir . "/index.php", "<?php // Silence is golden");
    }

    // Protect directory but allow ZIP downloads
    $htaccess_content = "Options -Indexes\n";
    $htaccess_content .= "<FilesMatch \"^.*\.(php|php5|phtml|inc)$\">\n";
    $htaccess_content .= "Order Deny,Allow\n";
    $htaccess_content .= "Deny from all\n";
    $htaccess_content .= "</FilesMatch>\n";
    $htaccess_content .= "<FilesMatch \"^.*\.zip$\">\n";
    $htaccess_content .= "Order Allow,Deny\n";
    $htaccess_content .= "Allow from all\n";
    $htaccess_content .= "</FilesMatch>";

    // Protect directory
    file_put_contents($this->temp_dir . "/.htaccess", $htaccess_content);
  }

  /**
   * Create plugin from snippet IDs
   *
   * @param array $snippet_ids Array of snippet post IDs
   * @return string|WP_Error Download URL or error
   */
  public function create_plugin($snippet_ids, $plugin_name, $plugin_author, $plugin_description)
  {
    if (empty($snippet_ids) || !is_array($snippet_ids)) {
      return new \WP_Error("invalid_input", "No valid snippet IDs provided");
    }

    self::$plugin_name = $plugin_name;
    self::$plugin_author = $plugin_author;
    self::$plugin_description = $plugin_description;

    $plugin_slug = self::slugify(self::$plugin_name);

    try {
      // Create unique plugin directory
      $this->plugin_dir = $this->temp_dir . "/" . $plugin_slug;
      wp_mkdir_p($this->plugin_dir);

      // Create plugin file structure
      $this->create_plugin_structure($snippet_ids);

      // Create ZIP file
      $zip_file = $this->create_zip_archive($plugin_slug);

      // Schedule cleanup
      wp_schedule_single_event(time() + HOUR_IN_SECONDS, "snipvault_cleanup_export", [$zip_file]);

      // Return download URL
      return str_replace($this->upload_dir, wp_upload_dir()["baseurl"], $zip_file);
    } catch (\Exception $e) {
      return new \WP_Error("export_failed", $e->getMessage());
    }
  }

  /**
   * Create plugin file structure
   */
  private function create_plugin_structure($snippet_ids)
  {
    $plugin_slug = self::slugify(self::$plugin_name);

    // Create directories
    $core_dir = $this->plugin_dir . "/core";
    $snippets_dir = $this->plugin_dir . "/snippets";
    $assets_dir = $this->plugin_dir . "/assets";

    wp_mkdir_p($core_dir);
    wp_mkdir_p($snippets_dir);
    wp_mkdir_p($assets_dir);

    // Create plugin main file
    $main_file_content = $this->generate_main_plugin_file();
    file_put_contents($this->plugin_dir . "/{$plugin_slug}.php", $main_file_content);

    // Copy core files
    $this->copy_core_files();

    // Process snippets and create JSON data
    $this->process_snippets($snippet_ids);
  }

  /**
   * Copy core framework files
   */
  private function copy_core_files()
  {
    $core_dir = $this->plugin_dir . "/core";

    // Copy SnippetLoader class
    $loader_content = $this->get_snippet_loader_class();
    file_put_contents($core_dir . "/snippet-loader.php", $loader_content);

    // Copy ConditionsEvaluator class
    $conditions_evaluator = $this->get_conditions_evaluator_class();
    file_put_contents($core_dir . "/conditions-evaluator.php", $conditions_evaluator);
  }

  /**
   * Get the SnippetLoader class content
   */
  private function get_snippet_loader_class()
  {
    $namespace_segment = self::namespace_slug(self::$plugin_name);
    $namespace = "SnipVaultExport\\{$namespace_segment}\\Core";

    // Read the content from the external file
    $file_path = dirname(__FILE__) . "/SnippetLoader.php";

    if (!file_exists($file_path)) {
      throw new \Exception("SnippetLoader.php file not found in " . dirname(__FILE__));
    }

    // Replace the namespace with a dynamic one
    $content = file_get_contents($file_path);
    $content = str_replace("namespace SnipVaultExport\\Core", "namespace {$namespace}", $content);
    $content = str_replace("CustomPlugin", $namespace_segment, $content);

    // Also replace any reference to ConditionsEvaluator with the namespaced version
    $content = str_replace("new ConditionsEvaluator()", "new \\{$namespace}\\ConditionsEvaluator()", $content);

    return $content;
  }

  /**
   * Get the ConditionsEvaluator class content
   */
  private function get_conditions_evaluator_class()
  {
    $namespace_segment = self::namespace_slug(self::$plugin_name);
    $namespace = "SnipVaultExport\\{$namespace_segment}";

    // Read the content from the external file
    $file_path = dirname(__FILE__) . "/ConditionsEvaluator.php";

    if (!file_exists($file_path)) {
      throw new \Exception("ConditionsEvaluator.php file not found in " . dirname(__FILE__));
    }

    // Replace the namespace with a dynamic one
    $content = file_get_contents($file_path);
    $content = str_replace("namespace SnipVault", "namespace {$namespace}", $content);

    return $content;
  }

  /**
   * Process snippets and create JSON files
   */
  private function process_snippets($snippet_ids)
  {
    $snippets_dir = $this->plugin_dir . "/snippets";
    $assets_dir = $this->plugin_dir . "/assets";
    $snippets_data = [];

    foreach ($snippet_ids as $id) {
      $snippet = get_post($id);
      if (!$snippet || $snippet->post_type !== "snipvault-snippets") {
        continue;
      }

      $snippet_settings = get_post_meta($id, "snippet_settings", true) ?: [];
      $content = get_post_meta($id, "snippet_content", true);
      $language = strtolower($snippet_settings["language"] ?: "php");

      // Create a clean snippet record
      $snippet_record = [
        "id" => $id,
        "title" => $snippet->post_title,
        "auto_load" => $snippet->status == "draft" ? false : true,
        "type" => $language,
        "settings" => $snippet_settings,
        "version" => "1.0.0",
      ];

      // Handle file extension mapping for different languages
      $extension_map = [
        "javascript" => "js",
        "html" => "html",
        "scss" => "scss", // Will store both scss and compiled css
        "css" => "css",
        "php" => "php",
      ];

      $extension = $extension_map[$language] ?? "php";
      $slug_name = self::slugify($snippet->post_title);
      $filename = sprintf("%s_%d.%s", $slug_name, $id, $extension);

      // Save the content to assets directory
      file_put_contents($assets_dir . "/" . $filename, $content);
      $snippet_record["file"] = $filename;

      // For SCSS files, also create a compiled CSS version
      if ($language === "scss") {
        $css_filename = sprintf("%s_%d.css", $slug_name, $id);
        // Simply copying the content here - in a real implementation,
        // you might want to run the SCSS through a compiler
        file_put_contents($assets_dir . "/" . $css_filename, $content);
        $snippet_record["file"] = $css_filename; // Use the CSS version for including
      }

      // Add snippet to the collection
      $snippets_data[] = $snippet_record;
    }

    // Write the snippets index file
    file_put_contents($snippets_dir . "/snippets-index.json", json_encode($snippets_data, JSON_PRETTY_PRINT));
  }

  /**
   * Generate a valid PHP namespace from a plugin name
   *
   * @param string $name The plugin name
   * @return string A properly formatted namespace segment
   */
  private static function namespace_slug($name)
  {
    // Split by non-alphanumeric characters
    $parts = preg_split("/[^a-zA-Z0-9]/", $name);

    // Filter out empty parts and capitalize each part
    $parts = array_filter($parts, function ($part) {
      return !empty($part);
    });

    $parts = array_map("ucfirst", $parts);

    // Join parts and ensure the result is valid
    $result = implode("", $parts);

    // If the result is empty, provide a default
    if (empty($result)) {
      $result = "Plugin";
    }

    return $result;
  }

  /**
   * Generate main plugin file content
   */
  private function generate_main_plugin_file()
  {
    $plugin_slug = self::slugify(self::$plugin_name);
    $namespace_segment = self::namespace_slug(self::$plugin_name);
    $namespace = "SnipVaultExport\\" . $namespace_segment . "\Core";
    $function_call = "new \\" . $namespace . "\SnippetLoader()";

    // Get settings and prepare global variables code
    $global_vars_code = "";
    $settings = get_option("snipvault_settings", []);
    if (isset($settings["php_global_variables"]) && is_array($settings["php_global_variables"])) {
      foreach ($settings["php_global_variables"] as $variable) {
        if (isset($variable["key"]) && !empty($variable["key"]) && isset($variable["value"])) {
          // Add code to define constants in the plugin file
          $global_vars_code .= "// Only define if the constant doesn't already exist\n";
          $global_vars_code .= "if (!defined('" . esc_js($variable["key"]) . "')) {\n";
          $global_vars_code .= "  define('" . esc_js($variable["key"]) . "', " . var_export($variable["value"], true) . ");\n";
          $global_vars_code .= "}\n";
        }
      }
    }

    // If we have global variables, add a header comment
    if (!empty($global_vars_code)) {
      $global_vars_code = "// Define global variables\n" . $global_vars_code . "\n";
    }

    // Create main plugin file with initialization code
    return "<?php\n/**\n" .
      " * Plugin Name: " .
      self::$plugin_name .
      "\n" .
      " * Description: " .
      self::$plugin_description .
      "\n" .
      " * Author: " .
      self::$plugin_author .
      "\n" .
      " * Version: " .
      self::$plugin_version .
      "\n" .
      " */\n\n" .
      "// Prevent direct access\n" .
      "defined('ABSPATH') || exit();\n\n" .
      $global_vars_code .
      "// Load the core snippet loader\n" .
      "require_once plugin_dir_path(__FILE__) . 'core/snippet-loader.php';\n\n" .
      "// Initialize the plugin\n" .
      "{$function_call};\n";
  }

  /**
   * Converts a string into a URL-friendly slug.
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
   * Create ZIP archive of the plugin
   */
  private function create_zip_archive($plugin_slug)
  {
    $zip = new \ZipArchive();
    $zip_file = $this->temp_dir . "/" . $plugin_slug . ".zip";

    if ($zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
      $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->plugin_dir), \RecursiveIteratorIterator::LEAVES_ONLY);

      foreach ($files as $file) {
        if (!$file->isDir()) {
          $filePath = $file->getRealPath();
          $relativePath = substr($filePath, strlen($this->plugin_dir) + 1);

          $zip->addFile($filePath, $plugin_slug . "/" . $relativePath);
        }
      }

      $zip->close();

      // Clean up the temporary plugin directory
      $this->recursive_rmdir($this->plugin_dir);

      return $zip_file;
    }

    throw new \Exception("Failed to create ZIP file");
  }

  /**
   * Recursively remove a directory
   */
  private function recursive_rmdir($dir)
  {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (is_dir($dir . "/" . $object)) {
            $this->recursive_rmdir($dir . "/" . $object);
          } else {
            unlink($dir . "/" . $object);
          }
        }
      }
      rmdir($dir);
    }
  }

  /**
   * Cleanup exported files (called by WP Cron)
   */
  public static function cleanup_export($zip_file)
  {
    if (file_exists($zip_file)) {
      unlink($zip_file);
    }
  }
}

// Register cleanup hook
add_action("snipvault_cleanup_export", ["SnipVault\Core\PluginPackager", "cleanup_export"]);
