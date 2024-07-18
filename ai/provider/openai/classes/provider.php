<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace aiprovider_openai;

use core\http_client;
use core_ai\aiactions;
use core_ai\ratelimiter;
use core_ai\aiactions\responses\response_generate_image;
use core_ai\aiactions\responses\response_generate_text;
use core_ai\aiactions\responses\response_summarise_text;

/**
 * Class provider.
 *
 * @package    aiprovier_openai
 * @copyright  2024 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider extends \core_ai\provider {
    /** @var string The openAI API key. */
    // API key.
    private string $apikey;

    /** @var string The organisation ID that goes with the key. */
    private string $orgid;

    /** @var bool Is global rate limiting for the API enabled. */
    private bool $enableglobalratelimit;

    /** @var int The global rate limit. */
    private int $globalratelimit;

    /** @var bool Is user rate limiting for the API enabled */
    private bool $enableuserratelimit;

    /** @var int The user rate limit. */
    private int $userratelimit;

    /** @var string A unique identifier representing your end-user, which can help OpenAI to monitor and detect abuse.  */
    private string $userid;

    /**
     * Class constructor.
     */
    public function __construct() {
        // Get api key from config.
        $this->apikey = get_config('aiprovider_openai', 'apikey');
        // Get api org id from config.
        $this->orgid = get_config('aiprovider_openai', 'orgid');
        // Get global rate limit from config.
        $this->enableglobalratelimit = get_config('aiprovider_openai', 'enableglobalratelimit');
        $this->globalratelimit = get_config('aiprovider_openai', 'globalratelimit');
        // Get user rate limit from config.
        $this->enableuserratelimit = get_config('aiprovider_openai', 'enableuserratelimit');
        $this->userratelimit = get_config('aiprovider_openai', 'userratelimit');
    }

    /**
     * Get the list of actions that this provider supports.
     *
     * @return array An array of action class names.
     */
    public function get_action_list(): array {
        return [
            'generate_text',
            'generate_image',
            'summarise_text',
        ];
    }

    /**
     * Process the generate_text action.
     * Handles communication with the OpenAI API and returning the result.
     *
     * @param aiactions\base $action The action to process.
     * @return \core_ai\aiactions\responses\response_base The result of the action.
     */
    public function process_action_generate_image(aiactions\base $action): aiactions\responses\response_base {
        // Check the rate limiter.
        $ratelimitcheck = $this->is_request_allowed($action);
        if ($ratelimitcheck !== true) {
            return new response_generate_image(
                    success: false,
                    actionname: 'generate_image',
                    errorcode: $ratelimitcheck['errorcode'],
                    errormessage: $ratelimitcheck['errormessage']
            );
        }

        $imagegenerator = new process_generate_image();

        // Create the HTTP client.
        $url = $imagegenerator->get_apiendpoint();
        $client = $this->create_http_client($url);

        // Generate the user id.
        $this->userid = $this->generate_userid($action->get_configuration('userid'));

        // Make the request to the OpenAI API.
        return $imagegenerator->process($client, $action, $this->userid);
    }

    /**
     * Process the generate_text action.
     * Handles communication with the OpenAI API and returning the result.
     *
     * @param aiactions\base $action The action to process.
     * @return \core_ai\aiactions\responses\response_base The result of the action.
     */
    public function process_action_generate_text(aiactions\base $action): aiactions\responses\response_base {
        // Check the rate limiter.
        $ratelimitcheck = $this->is_request_allowed($action);
        if ($ratelimitcheck !== true) {
            return new response_generate_text(
                    success: false,
                    actionname: 'generate_text',
                    errorcode: $ratelimitcheck['errorcode'],
                    errormessage: $ratelimitcheck['errormessage']
            );
        }

        $textgenerator = new process_generate_text();

        // Create the HTTP client.
        $url = $textgenerator->get_apiendpoint();
        $client = $this->create_http_client($url);

        // Generate the user id.
        $this->userid = $this->generate_userid($action->get_configuration('userid'));

        // Make the request to the OpenAI API.
        return $textgenerator->process($client, $action, $this->userid);
    }

    /**
     * Process the summarise_text action.
     * Handles communication with the OpenAI API and returning the result.
     *
     * @param aiactions\base $action The action to process.
     * @return \core_ai\aiactions\responses\response_base The result of the action.
     */
    public function process_action_summarise_text(aiactions\base $action): aiactions\responses\response_base {
        $ratelimitcheck = $this->is_request_allowed($action);
        if ($ratelimitcheck !== true) {
            return new response_summarise_text(
                    success: false,
                    actionname: 'generate_text',
                    errorcode: $ratelimitcheck['errorcode'],
                    errormessage: $ratelimitcheck['errormessage']
            );
        }

        $textsummary = new process_summarise_text();

        // Create the HTTP client.
        $url = $textsummary->get_apiendpoint();
        $client = $this->create_http_client($url);

        // Generate the user id.
        $this->userid = $this->generate_userid($action->get_configuration('userid'));

        // Make the request to the OpenAI API.
        return $textsummary->process($client, $action, $this->userid);
    }

    /**
     * Generate a user id.
     * This is a hash of the site id and user id,
     * this means we can determine who made the request
     * but don't pass any personal data to OpenAI.
     *
     * @param string $userid The user id.
     * @return string The generated user id.
     */
    private function generate_userid($userid): string {
        global $CFG;
        return hash('sha256', $CFG->siteidentifier . $userid);
    }

    /**
     * Create the HTTP client.
     *
     * @param string $apiendpoint The API endpoint.
     * @return http_client The HTTP client used to make requests.
     */
    private function create_http_client(string $apiendpoint): http_client {
        return new http_client([
                'base_uri' => $apiendpoint,
                'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->apikey,
                        'OpenAI-Organization' => $this->orgid,
                ]
        ]);
    }

    /**
     * Check if the request is allowed by the rate limiter.
     *
     * @param aiactions\base $action The action to check.
     * @return array|bool True on success, array of error details on failure.
     */
    private function is_request_allowed(aiactions\base $action): array|bool {
        $ratelimiter = ratelimiter::get_instance();
        $component = explode('\\', get_class($this))[0];

        // Check the user rate limit.
        if ($this->enableuserratelimit) {
            if (!$ratelimiter->check_user_rate_limit(
                    component: $component,
                    ratelimit: $this->userratelimit,
                    userid: $action->get_configuration('userid')
            )) {
                return [
                        'success' => false,
                        'errorcode' => 429,
                        'errormessage' => 'User rate limit exceeded',
                ];
            }
        }

        // Check the global rate limit.
        if ($this->enableglobalratelimit) {
            if (!$ratelimiter->check_global_rate_limit(
                    component: $component,
                    ratelimit: $this->globalratelimit)) {
                return [
                    'success' => false,
                    'errorcode' => 429,
                    'errormessage' => 'Global rate limit exceeded',
                ];
            }
        }

        return true;
    }
}
