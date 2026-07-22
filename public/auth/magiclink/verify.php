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
 * Verify magic link token and log in the user.
 *
 * @package    auth_magiclink
 * @copyright  2024 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/authlib.php');
require_once(__DIR__ . '/../../login/lib.php');

$token = required_param('token', PARAM_ALPHANUM);

$PAGE->set_url(new moodle_url('/auth/magiclink/verify.php', ['token' => $token]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');

// Validate and consume the token.
$user = \auth_magiclink\token_manager::validate_and_consume($token);

if (!$user) {
    // Invalid, expired, or already-used token.
    $PAGE->set_title(get_string('verifyinvalidtokentitle', 'auth_magiclink'));
    $PAGE->set_heading(get_site()->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('verifyinvalidtokentitle', 'auth_magiclink'));
    echo $OUTPUT->box(get_string('verifyinvalidtoken', 'auth_magiclink'), 'generalbox boxaligncenter');
    $requesturl = new moodle_url('/auth/magiclink/request.php');
    echo $OUTPUT->single_button($requesturl, get_string('requestnewlink', 'auth_magiclink'));
    echo $OUTPUT->footer();
    exit;
}

// Perform safety checks on the user account.
if (empty($user->confirmed) || $user->deleted || $user->suspended) {
    // Account not confirmed, deleted, or suspended - treat as invalid token.
    $PAGE->set_title(get_string('verifyinvalidtokentitle', 'auth_magiclink'));
    $PAGE->set_heading(get_site()->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('verifyinvalidtokentitle', 'auth_magiclink'));
    echo $OUTPUT->box(get_string('verifyinvalidtoken', 'auth_magiclink'), 'generalbox boxaligncenter');
    $requesturl = new moodle_url('/auth/magiclink/request.php');
    echo $OUTPUT->single_button($requesturl, get_string('requestnewlink', 'auth_magiclink'));
    echo $OUTPUT->footer();
    exit;
}

// All checks passed - log in the user.
complete_user_login($user);

\core\session\manager::apply_concurrent_login_limit($user->id, session_id());

// Redirect to the appropriate page.
redirect(core_login_get_return_url());
