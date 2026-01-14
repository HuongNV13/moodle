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
 * Hook after OAuth2 verification is successful.
 *
 * @package    core
 * @copyright  2026 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\oauth2\hook;

#[\core\attribute\label('Allows plugins or features to perform actions after OAuth2 verification is successful.')]
#[\core\attribute\tags('oauth2', 'authentication')]
class after_oauth2_verified {

    /**
     * Constructor for the hook.
     *
     * @param int $issuerid The issuer id.
     * @param string $userinfo The user information.
     */
    public function __construct(
        /** @var int The issuer id. */
        public readonly int $issuerid,
        /** @var string The user information. */
        public readonly string $userinfo,
    ) {
    }
}
