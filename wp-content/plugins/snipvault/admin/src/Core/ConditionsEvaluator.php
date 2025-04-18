<?php

namespace SnipVault\Core;

/**
 * SnipVault Conditions Evaluator.
 *
 * Evaluates complex condition structures to determine if a snippet should be loaded.
 *
 * @package SnipVault
 * @since 1.0.0
 */

// Prevent direct access to this file
defined("ABSPATH") || exit();

/**
 * Conditions_Evaluator class.
 *
 * Evaluates snippet loading conditions using the hierarchical group/condition
 * structure to determine if a snippet should be loaded.
 *
 * @since 1.0.0
 */
class ConditionsEvaluator
{
  /**
   * Evaluates all conditions for a snippet to determine if it should load.
   *
   * @since 1.0.0
   * @access public
   *
   * @param array $conditions The conditions structure from the snippet settings.
   * @return bool True if conditions are met, false otherwise.
   */
  public function evaluate($conditions)
  {
    // If conditions are not set or empty, return true (no restrictions)
    if (empty($conditions) || !isset($conditions["groups"]) || empty($conditions["groups"])) {
      return true;
    }

    // Get the top-level operator (default to AND if not specified)
    $operator = isset($conditions["operator"]) ? strtoupper($conditions["operator"]) : "AND";

    // Evaluate each group
    $groups_results = [];
    foreach ($conditions["groups"] as $group) {
      $groups_results[] = $this->evaluate_group($group);
    }

    // Apply the operator to the group results
    return $this->apply_operator($groups_results, $operator);
  }

  /**
   * Evaluates a single condition group.
   *
   * @since 1.0.0
   * @access private
   *
   * @param array $group The condition group to evaluate.
   * @return bool True if group conditions are met, false otherwise.
   */
  private function evaluate_group($group)
  {
    // If no conditions in the group, return true
    if (empty($group) || !isset($group["conditions"]) || empty($group["conditions"])) {
      return true;
    }

    // Get the group operator (default to AND if not specified)
    $operator = isset($group["operator"]) ? strtoupper($group["operator"]) : "AND";

    // Evaluate each condition in the group
    $conditions_results = [];
    foreach ($group["conditions"] as $condition) {
      $conditions_results[] = $this->evaluate_condition($condition);
    }

    // Apply the operator to the condition results
    return $this->apply_operator($conditions_results, $operator);
  }

  /**
   * Applies an operator (AND/OR) to an array of boolean results.
   *
   * @since 1.0.0
   * @access private
   *
   * @param array  $results  Array of boolean values.
   * @param string $operator The operator to apply ('AND' or 'OR').
   * @return bool The combined result.
   */
  private function apply_operator($results, $operator)
  {
    if (empty($results)) {
      return true; // Empty results means no conditions to check
    }

    if ($operator === "AND") {
      // AND operator: All must be true
      return !in_array(false, $results, true);
    } else {
      // OR operator: At least one must be true
      return in_array(true, $results, true);
    }
  }

  /**
   * Evaluates a single condition.
   *
   * @since 1.0.0
   * @access private
   *
   * @param array $condition The condition to evaluate.
   * @return bool True if condition is met, false otherwise.
   */
  private function evaluate_condition($condition)
  {
    // Check if condition is properly formed
    if (!isset($condition["field"]) || !isset($condition["comparison"])) {
      return false;
    }

    $field = $condition["field"];
    $comparison = $condition["comparison"];
    $value = isset($condition["value"]) ? $condition["value"] : null;

    // Special handling for conditions that don't need a value
    if (in_array($comparison, ["is_empty", "is_not_empty"])) {
      $actual_value = $this->get_field_value($field, $condition);
      return $this->compare_values($actual_value, null, $comparison);
    }

    // Get the actual value from WordPress
    $actual_value = $this->get_field_value($field, $condition);

    // Compare the actual value against the condition value
    return $this->compare_values($actual_value, $value, $comparison);
  }

  /**
   * Gets the actual value of a field from WordPress.
   *
   * @since 1.0.0
   * @access private
   *
   * @param string $field     The field identifier.
   * @param array  $condition The full condition array for context.
   * @return mixed The field's current value.
   */
  private function get_field_value($field, $condition)
  {
    // WordPress Core fields
    switch ($field) {
      // Post type fields
      case "post_type":
        return get_post_type();

      case "post_id":
        return get_the_ID();

      case "post_status":
        return get_post_status();

      case "post_format":
        $format = get_post_format();
        return $format ? $format : "standard";

      case "page_template":
        return get_page_template_slug() ?: "default";

      case "taxonomy":
        if (isset($condition["term"]) && isset($condition["value"])) {
          return has_term($condition["value"], $condition["term"]);
        }
        return false;

      case "term":
        if (isset($condition["taxonomy"]) && isset($condition["value"])) {
          return has_term($condition["value"], $condition["taxonomy"]);
        }
        return false;

      // Conditional tags
      case "is_front_page":
        return is_front_page();

      case "is_home":
        return is_home();

      case "is_single":
        return is_single();

      case "is_archive":
        return is_archive();

      case "is_search":
        return is_search();

      case "is_404":
        return is_404();

      // User related fields
      case "user_role":
        $user = wp_get_current_user();
        return !empty($user->roles) ? $user->roles : [];

      case "user_id":
        return get_current_user_id();

      case "user_capability":
        $user = wp_get_current_user();
        return isset($condition["value"]) ? user_can($user, $condition["value"]) : false;

      case "user_meta":
        if (!isset($condition["meta_key"])) {
          return null;
        }
        $user_id = get_current_user_id();
        return $user_id ? get_user_meta($user_id, $condition["meta_key"], true) : null;

      case "is_user_logged_in":
        return is_user_logged_in();

      // URL & Request fields
      case "url_path":
        return $_SERVER["REQUEST_URI"] ?? "";

      case "url_parameter":
        if (!isset($condition["parameter_name"])) {
          return null;
        }
        return $_GET[$condition["parameter_name"]] ?? null;

      case "request_method":
        return $_SERVER["REQUEST_METHOD"] ?? "";

      case "referrer":
        return wp_get_referer();

      case "browser":
        return $this->detect_browser();

      case "device_type":
        return $this->detect_device_type();

      // Time & Date fields
      case "day_of_week":
        return date("w"); // 0 (Sunday) to 6 (Saturday)

      case "month":
        return date("n"); // 1 to 12

      case "time_of_day":
        return date("H:i");

      case "date_range":
        $current_date = strtotime(date("Y-m-d"));
        $from = isset($condition["value_from"]) ? strtotime($condition["value_from"]) : false;
        $to = isset($condition["value_to"]) ? strtotime($condition["value_to"]) : false;

        if ($from && $to) {
          return $current_date >= $from && $current_date <= $to;
        } elseif ($from) {
          return $current_date >= $from;
        } elseif ($to) {
          return $current_date <= $to;
        }
        return false;

      // Plugin & Theme fields
      case "active_theme":
        $theme = wp_get_theme();
        return $theme->get_stylesheet();

      case "plugin_active":
        if (!isset($condition["value"])) {
          return false;
        }

        if (!function_exists("is_plugin_active")) {
          include_once ABSPATH . "wp-admin/includes/plugin.php";
        }

        return is_plugin_active($condition["value"]);

      // Custom fields
      case "post_meta":
        if (!isset($condition["meta_key"])) {
          return null;
        }

        global $post;
        return $post ? get_post_meta($post->ID, $condition["meta_key"], true) : null;

      case "custom_function":
        if (!isset($condition["value"]) || !is_callable($condition["value"])) {
          return false;
        }

        try {
          return (bool) call_user_func($condition["value"]);
        } catch (\Exception $e) {
          return false;
        }

      case "php_expression":
        if (!isset($condition["value"])) {
          return false;
        }

        try {
          // Use eval carefully - this should be restricted to admin users only
          return (bool) eval("return (" . $condition["value"] . ");");
        } catch (\Exception $e) {
          return false;
        }

      default:
        // Allow custom field handling through filters
        return apply_filters("snipvault_condition_field_value", null, $field, $condition);
    }
  }

  /**
   * Compares two values using the specified comparison operator.
   *
   * @since 1.0.0
   * @access private
   *
   * @param mixed  $actual_value The actual value from WordPress.
   * @param mixed  $value        The value to compare against.
   * @param string $comparison   The comparison operator.
   * @return bool The comparison result.
   */
  private function compare_values($actual_value, $value, $comparison)
  {
    switch ($comparison) {
      case "equals":
        return $this->equals($actual_value, $value);

      case "does_not_equal":
        return !$this->equals($actual_value, $value);

      case "contains":
        return $this->contains($actual_value, $value);

      case "does_not_contain":
        return !$this->contains($actual_value, $value);

      case "starts_with":
        return $this->starts_with($actual_value, $value);

      case "ends_with":
        return $this->ends_with($actual_value, $value);

      case "greater_than":
        return $this->greater_than($actual_value, $value);

      case "less_than":
        return $this->less_than($actual_value, $value);

      case "is_empty":
        return $this->is_empty($actual_value);

      case "is_not_empty":
        return !$this->is_empty($actual_value);

      case "matches_regex":
        return $this->matches_regex($actual_value, $value);

      default:
        // Allow custom comparison operators through filters
        return apply_filters("snipvault_condition_comparison", false, $actual_value, $value, $comparison);
    }
  }

  /**
   * Checks if two values are equal.
   *
   * @since 1.0.0
   * @access private
   *
   * @param mixed $actual_value The actual value from WordPress.
   * @param mixed $value        The value to compare against.
   * @return bool True if equal, false otherwise.
   */
  private function equals($actual_value, $value)
  {
    if (is_array($actual_value)) {
      return in_array($value, $actual_value, true);
    }

    // Special handling for boolean values
    if (is_bool($actual_value) || $actual_value === "true" || $actual_value === "false") {
      return $this->compare_booleans($actual_value, $value);
    }

    return $actual_value == $value; // Loose comparison intentional
  }

  /**
   * Checks if the actual value contains the specified value.
   *
   * @since 1.0.0
   * @access private
   *
   * @param mixed $actual_value The actual value from WordPress.
   * @param mixed $value        The value to look for.
   * @return bool True if contained, false otherwise.
   */
  private function contains($actual_value, $value)
  {
    if (is_array($actual_value)) {
      return in_array($value, $actual_value, true);
    }

    if (is_string($actual_value) && is_string($value)) {
      return stripos($actual_value, $value) !== false;
    }

    return false;
  }

  /**
   * Checks if the actual value starts with the specified value.
   *
   * @since 1.0.0
   * @access private
   *
   * @param mixed $actual_value The actual value from WordPress.
   * @param mixed $value        The value to compare against.
   * @return bool True if starts with, false otherwise.
   */
  private function starts_with($actual_value, $value)
  {
    if (!is_string($actual_value) || !is_string($value)) {
      return false;
    }

    return strpos($actual_value, $value) === 0;
  }

  /**
   * Checks if the actual value ends with the specified value.
   *
   * @since 1.0.0
   * @access private
   *
   * @param mixed $actual_value The actual value from WordPress.
   * @param mixed $value        The value to compare against.
   * @return bool True if ends with, false otherwise.
   */
  private function ends_with($actual_value, $value)
  {
    if (!is_string($actual_value) || !is_string($value)) {
      return false;
    }

    $actual_length = strlen($actual_value);
    $value_length = strlen($value);

    if ($value_length > $actual_length) {
      return false;
    }

    return substr_compare($actual_value, $value, -$value_length) === 0;
  }

  /**
   * Checks if the actual value is greater than the specified value.
   *
   * @since 1.0.0
   * @access private
   *
   * @param mixed $actual_value The actual value from WordPress.
   * @param mixed $value        The value to compare against.
   * @return bool True if greater, false otherwise.
   */
  private function greater_than($actual_value, $value)
  {
    if (!is_numeric($actual_value) || !is_numeric($value)) {
      return false;
    }

    return floatval($actual_value) > floatval($value);
  }

  /**
   * Checks if the actual value is less than the specified value.
   *
   * @since 1.0.0
   * @access private
   *
   * @param mixed $actual_value The actual value from WordPress.
   * @param mixed $value        The value to compare against.
   * @return bool True if less, false otherwise.
   */
  private function less_than($actual_value, $value)
  {
    if (!is_numeric($actual_value) || !is_numeric($value)) {
      return false;
    }

    return floatval($actual_value) < floatval($value);
  }

  /**
   * Checks if the actual value is empty.
   *
   * @since 1.0.0
   * @access private
   *
   * @param mixed $actual_value The actual value from WordPress.
   * @return bool True if empty, false otherwise.
   */
  private function is_empty($actual_value)
  {
    if (is_array($actual_value)) {
      return empty($actual_value);
    }

    return $actual_value === null || $actual_value === "" || $actual_value === false;
  }

  /**
   * Checks if the actual value matches a regex pattern.
   *
   * @since 1.0.0
   * @access private
   *
   * @param mixed $actual_value The actual value from WordPress.
   * @param mixed $value        The regex pattern to match against.
   * @return bool True if matches, false otherwise.
   */
  private function matches_regex($actual_value, $value)
  {
    if (!is_string($actual_value) || !is_string($value)) {
      return false;
    }

    try {
      return preg_match($value, $actual_value) === 1;
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Compares boolean values, handling string representations.
   *
   * @since 1.0.0
   * @access private
   *
   * @param mixed $actual_value The actual value from WordPress.
   * @param mixed $value        The value to compare against.
   * @return bool True if equal, false otherwise.
   */
  private function compare_booleans($actual_value, $value)
  {
    // Convert string representations to actual booleans
    if ($actual_value === "true") {
      $actual_value = true;
    } elseif ($actual_value === "false") {
      $actual_value = false;
    }

    if ($value === "true") {
      $value = true;
    } elseif ($value === "false") {
      $value = false;
    }

    return $actual_value === $value;
  }

  /**
   * Detects the user's browser based on user agent.
   *
   * @since 1.0.0
   * @access private
   *
   * @return string The browser identifier.
   */
  private function detect_browser()
  {
    $user_agent = $_SERVER["HTTP_USER_AGENT"] ?? "";

    if (stripos($user_agent, "Chrome") !== false) {
      return "chrome";
    } elseif (stripos($user_agent, "Safari") !== false) {
      return "safari";
    } elseif (stripos($user_agent, "Firefox") !== false) {
      return "firefox";
    } elseif (stripos($user_agent, "MSIE") !== false || stripos($user_agent, "Trident") !== false) {
      return "ie";
    } elseif (stripos($user_agent, "Edge") !== false) {
      return "edge";
    } elseif (stripos($user_agent, "Opera") !== false || stripos($user_agent, "OPR") !== false) {
      return "opera";
    }

    return "unknown";
  }

  /**
   * Detects the user's device type based on user agent.
   *
   * @since 1.0.0
   * @access private
   *
   * @return string The device type (desktop, tablet, mobile).
   */
  private function detect_device_type()
  {
    $user_agent = $_SERVER["HTTP_USER_AGENT"] ?? "";

    // Check for mobile devices
    if (stripos($user_agent, "mobile") !== false || (stripos($user_agent, "android") !== false && stripos($user_agent, "mobile") !== false) || stripos($user_agent, "iphone") !== false) {
      return "mobile";
    }

    // Check for tablets
    if (stripos($user_agent, "tablet") !== false || stripos($user_agent, "ipad") !== false || (stripos($user_agent, "android") !== false && stripos($user_agent, "mobile") === false)) {
      return "tablet";
    }

    // Default to desktop
    return "desktop";
  }
}
