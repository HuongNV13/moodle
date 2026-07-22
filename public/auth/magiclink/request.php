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
 * Magic link authentication request page.
 *
 * Allows users to request a passwordless login link by entering their username or email address.
 * The link is sent via email if the account exists and is eligible.
 *
 * @package auth_magiclink
 * @copyright 2026 Moodle Pty Ltd
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/authlib.php');
require_once(__DIR__ . '/classes/form/request_form.php');

// Check if magic link authentication is enabled.
if (!is_enabled_auth('magiclink')) {
    redirect(get_login_url());
}

// Set up the page.
$PAGE->set_url(new \moodle_url('/auth/magiclink/request.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('requestlinkbutton', 'auth_magiclink'));

// Create the form.
$mform = new \auth_magiclink\form\request_form();

// Handle form cancellation.
if ($mform->is_cancelled()) {
    redirect(get_login_url());
}

// Handle form submission.
if ($data = $mform->get_data()) {
    // Look up the user by username or email.
    $user = null;
    $usernameoremail = trim($data->username);

    if (!empty($usernameoremail)) {
        // Try to find user by username first (case-insensitive, matching login behavior).
        $usernameoremail = \core_text::strtolower($usernameoremail);
        $userparams = [
            'username' => $usernameoremail,
            'mnethostid' => $CFG->mnet_localhost_id,
            'deleted' => 0,
            'suspended' => 0,
        ];
        $user = $DB->get_record('user', $userparams);

        // If not found by username, try by email.
        if (!$user) {
            // Use case-insensitive search for email (matching core_login_process_password_reset pattern).
            $sql = "SELECT *
                      FROM {user}
                     WHERE " . $DB->sql_equal('email', ':email1', false, true) . "
                       AND id IN (SELECT id
                                    FROM {user}
                                   WHERE mnethostid = :mnethostid
                                     AND deleted = 0
                                     AND suspended = 0
                                     AND " . $DB->sql_equal('email', ':email2', false, false) . ")";

            $params = [
                'email1' => $usernameoremail,
                'email2' => $usernameoremail,
                'mnethostid' => $CFG->mnet_localhost_id,
            ];

            $user = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);
        }
    }

    // Wrap the eligibility check and email sending in error handling to avoid leaking information.
    $emailsent = false;

    if ($user && !empty($user->confirmed)) {
        // Check if the user's authentication method supports magic link.
        // Only allow 'manual' and 'email' auth plugins.
        if ($user->auth === 'manual' || $user->auth === 'email') {
            // Attempt to generate a token.
            $rawtoken = \auth_magiclink\token_manager::generate_token($user);

            if ($rawtoken !== null) {
                // Token generated successfully (not rate-limited). Send email.
                try {
                    $site = get_site();
                    $supportuser = \core_user::get_support_user();

                    // Build the verify URL.
                    $verifyurl = new \moodle_url('/auth/magiclink/verify.php', ['token' => $rawtoken]);

                    // Get expiry minutes from config.
                    $expiryminutes = (int) get_config('auth_magiclink', 'expiryminutes');
                    if (!$expiryminutes) {
                        $expiryminutes = 15;
                    }

                    // Prepare email data.
                    $emaildata = new \stdClass();
                    $emaildata->username = $user->username;
                    $emaildata->sitename = format_string($site->fullname);
                    $emaildata->link = $verifyurl->out(false);
                    $emaildata->expiryminutes = $expiryminutes;

                    $subject = get_string('emailsubject', 'auth_magiclink', $emaildata);
                    $message = get_string('emailmessage', 'auth_magiclink', $emaildata);

                    // Send the email.
                    $emailsent = email_to_user($user, $supportuser, $subject, $message);
                } catch (\Exception $e) {
                    // Silently catch any email sending exceptions to avoid leaking information.
                    // The confirmation message will still be shown regardless.
                    $emailsent = false;
                }
            }
        }
    }

    // Always display the confirmation page with a generic message,
    // regardless of whether the user was found, eligible, or rate-limited.
    // This prevents account enumeration attacks.
    $PAGE->set_title(get_string('confirmationtitle', 'auth_magiclink'));
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('confirmationtitle', 'auth_magiclink'));
    echo $OUTPUT->box(
        get_string('confirmationmessage', 'auth_magiclink'),
        'generalbox boxwidthnormal boxaligncenter'
    );
    echo $OUTPUT->footer();
    die;
}

// Display the form on GET request.
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
