<?php
namespace SnipVault\Rest;

// Prevent direct access to this file
defined("ABSPATH") || exit();
/**
 * Class ClaudeEndpoint
 *
 * Handles communication with the Claude API for the SnipVault plugin
 *
 * @package SnipVault\Rest
 */
class ClaudeEndpoint
{
  // API constants
  private $api_url = "https://api.anthropic.com/v1/messages";
  private $model = "claude-3-7-sonnet-20250219";
  private $api_version = "2023-06-01";

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
    register_rest_route("snipvault/v1", "/claude", [
      "methods" => "POST",
      "callback" => [$this, "handle_claude_message"],
      "permission_callback" => [$this, "check_permission"],
      "args" => [
        "system" => [
          "required" => true,
          "type" => "string",
          "validate_callback" => function ($system) {
            if (empty($system) || !$system) {
              return false;
            }
            return true;
          },
        ],
        "messages" => [
          "required" => true,
          "type" => "array",
          "items" => [
            "type" => "object",
            "properties" => [
              "role" => [
                "type" => "string",
                "enum" => ["user", "assistant", "system"],
              ],
              "content" => [
                "type" => "string",
              ],
            ],
          ],
          "validate_callback" => function ($messages) {
            if (empty($messages) || !is_array($messages)) {
              return false;
            }

            foreach ($messages as $message) {
              if (!isset($message["role"]) || !isset($message["content"])) {
                return false;
              }

              if (!in_array($message["role"], ["user", "assistant", "system"])) {
                return false;
              }
            }

            // The last message must be from the user
            $last_message = end($messages);
            if ($last_message["role"] !== "user") {
              return false;
            }

            return true;
          },
        ],
        "max_tokens" => [
          "required" => false,
          "type" => "integer",
          "default" => 1024,
          "sanitize_callback" => "absint",
        ],
        "temperature" => [
          "required" => false,
          "type" => "number",
          "default" => 0.7,
          "sanitize_callback" => function ($param) {
            return floatval($param);
          },
          "validate_callback" => function ($param) {
            return $param >= 0 && $param <= 1;
          },
        ],
      ],
    ]);
  }

  /**
   * Check if user has permission to use Claude API
   */
  public function check_permission()
  {
    return current_user_can("manage_options");
  }

  /**
   * Handle Claude API request
   */
  public function handle_claude_message(\WP_REST_Request $request)
  {
    // Get API key from options
    $api_key = "";
    //get_option('snipvault_claude_api_key');

    $options = get_option("snipvault_settings", false);
    $options = !$options ? [] : $options;

    if (isset($options["anthropic_key"])) {
      $api_key = $options["anthropic_key"];
    }

    if (empty($api_key)) {
      return new \WP_Error("missing_api_key", "Claude API key is not configured. Please set it in the SnipVault plugin settings.", ["status" => 400]);
    }

    $messages = $request->get_param("messages");
    $system_prompt = $request->get_param("system");
    $max_tokens = $request->get_param("max_tokens");
    $temperature = $request->get_param("temperature");

    try {
      $response = $this->send_claude_request($api_key, $messages, $max_tokens, $temperature, $system_prompt);

      if (is_wp_error($response)) {
        return $response;
      }

      $body = json_decode(wp_remote_retrieve_body($response), true);
      $status_code = wp_remote_retrieve_response_code($response);

      if ($status_code !== 200) {
        $error_message = isset($body["error"]["message"]) ? $body["error"]["message"] : "Unknown error occurred";
        return new \WP_Error("claude_api_error", $error_message, ["status" => $status_code]);
      }

      return [
        "success" => true,
        "message" => [
          "role" => "assistant",
          "content" => $body["content"][0]["text"],
          "id" => $body["id"],
          "model" => $body["model"],
          "usage" => $body["usage"],
        ],
      ];
    } catch (\Exception $e) {
      return new \WP_Error("claude_request_failed", $e->getMessage(), ["status" => 500]);
    }
  }

  /**
   * Send request to Claude API
   */
  private function send_claude_request($api_key, $messages, $max_tokens, $temperature, $system_prompt)
  {
    $body = [
      "model" => $this->model,
      "messages" => $messages,
      "max_tokens" => $max_tokens,
      "temperature" => $temperature,
      "system" => $system_prompt,
    ];

    $args = [
      "method" => "POST",
      "timeout" => 45,
      "redirection" => 5,
      "httpversion" => "1.1",
      "blocking" => true,
      "headers" => [
        "Content-Type" => "application/json",
        "x-api-key" => $api_key,
        "anthropic-version" => $this->api_version,
      ],
      "body" => json_encode($body),
      "cookies" => [],
    ];

    return wp_remote_request($this->api_url, $args);
  }
}
