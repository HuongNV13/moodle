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
 * Hook callbacks for OAuth2.
 *
 * @package    core
 * @copyright  2026 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\oauth2;

use core\oauth2\hook\after_oauth2_verified;

class hook_callbacks {

    /**
     * Store Apple user info in session after OAuth2 verification.
     *
     * @param after_oauth2_verified $hook
     */
    public static function after_oauth2_verified(
        after_oauth2_verified $hook,
    ): void {
        $issuerid = $hook->issuerid;
        $userinfo = $hook->userinfo;
        $issuer = new \core\oauth2\issuer($issuerid);
        if ($issuer && $issuer->get('servicetype') === 'apple') {
            // Apple passes back the user information only on the first hit to the oauth service.
            // This user information is stored in the $SESSION variable to capture the user information.
            global $SESSION;
            $SESSION->appleuserpostcontent = $userinfo;
        }
    }
}
