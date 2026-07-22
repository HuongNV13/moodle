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

/**
 * Magic link authentication plugin.
 *
 * @package auth_magiclink
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/**
 * Magic link authentication plugin - passwordless login via token exchange.
 */
class auth_plugin_magiclink extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'magiclink';
        $this->config = get_config('auth_magiclink');
    }

    /**
     * Do not allow password-based login.
     *
     * Magic link authentication uses token-based login via a separate verify endpoint,
     * not through this method. Password validation is not supported.
     *
     * @param string $username The username.
     * @param string $password The password (unused).
     * @return bool Always returns false.
     */
    public function user_login($username, $password) {
        return false;
    }

    /**
     * Returns true if this authentication plugin is "internal".
     *
     * Magic link is an external authentication mechanism.
     *
     * @return bool False - this plugin does not manage local passwords.
     */
    public function is_internal() {
        return false;
    }

    /**
     * Return a list of identity providers to display on the login page.
     *
     * @param string|moodle_url $wantsurl The requested URL.
     * @return array List of arrays with keys url, iconurl and name.
     */
    public function loginpage_idp_list($wantsurl) {
        // Only offer magic link option if the plugin is enabled.
        if (!is_enabled_auth('magiclink')) {
            return [];
        }

        // Build request page parameters.
        $params = [];
        if (!empty($wantsurl)) {
            $params['wantsurl'] = $wantsurl;
        }

        $url = new \moodle_url('/auth/magiclink/request.php', $params);

        return [
            [
                'url' => $url,
                'iconurl' => null,
                'name' => get_string('requestlinkbutton', 'auth_magiclink'),
            ],
        ];
    }
}
