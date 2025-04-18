<?php
/*
 * Plugin Name: SnipVault
 * Plugin URI: https://snipvault.co
 * Description: Securely store and manage code snippets with military-grade signing. Features file-based storage, tamper protection, and instant verification. Perfect for developers who value both security and simplicity.
 * Version: 1.0.18
 * Author: uipress
 * Text Domain: snipvault
 * Domain Path: /languages/
 * Requires PHP: 8.1
 * Requires at least: 5.5
 * Update URI: https://api.snipvault.co/v1/update/latest
 * License: GPLv2 or later for PHP code, proprietary license for other assets
 * License URI: licence.txt
 */

// If this file is called directly, abort.
!defined("ABSPATH") ? exit() : "";

define("snipvault_plugin_version", "1.0.18");
define("snipvault_plugin_path", plugin_dir_path(__FILE__));
define("snipvault_dev_mode", false);

require snipvault_plugin_path . "admin/vendor/autoload.php";

// Start app
new SnipVault\App\SnipVault();

// Register key rotation hooks
register_activation_hook(__FILE__, ["SnipVault\PostTypes\Snippets", "schedule_key_rotation"]);
register_deactivation_hook(__FILE__, ["SnipVault\PostTypes\Snippets", "deactivate_scheduled_events"]);
