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
 * Strings for component 'auth_magiclink', language 'en'.
 *
 * @package   auth_magiclink
 * @copyright 2026 Moodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Magic link (passwordless)';
$string['auth_magiclinkdescription'] = '<p>Magic link authentication allows users to log in to their account using a one-time secure link sent to their email address, instead of entering a password. This works for accounts using manual or email-based authentication only.</p><p>To enable this authentication method, ensure it is selected from the authentication dropdown menu on the \'Manage authentication\' page.</p>';
$string['auth_magiclinksettings'] = 'Settings';
$string['expiryminutes'] = 'Link expiry time';
$string['expiryminutes_desc'] = 'How long (in minutes) a magic link remains valid after it is requested.';
$string['requestlinkbutton'] = 'Login with magic link';
$string['requestformusername'] = 'Username or email address';
$string['requestforminstructions'] = 'Enter your username or email address and we\'ll send you a link to log in without a password.';
$string['requestformsubmit'] = 'Send login link';
$string['confirmationmessage'] = 'If that account exists and supports magic link login, we\'ve sent an email with a link to log in. The link will expire soon. If you don\'t receive the email, please check your spam folder.';
$string['confirmationtitle'] = 'Check your email';
$string['emailsubject'] = 'Your login link for {$a->sitename}';
$string['emailmessage'] = 'Hello {$a->username},

You\'ve requested a login link for {$a->sitename}. Click the link below to log in without needing a password:

{$a->link}

This link will expire in {$a->expiryminutes} minutes.

If you did not request this link, you can safely ignore this email. Your account is secure and has not been accessed.

Regards,
The {$a->sitename} team';
$string['emailmessagehtml'] = '<p>Hello {$a->username},</p>
<p>You\'ve requested a login link for {$a->sitename}. Click the link below to log in without needing a password:</p>
<p><a href="{$a->link}">{$a->link}</a></p>
<p>This link will expire in {$a->expiryminutes} minutes.</p>
<p>If you did not request this link, you can safely ignore this email. Your account is secure and has not been accessed.</p>
<p>Regards,<br>The {$a->sitename} team</p>';
$string['verifyinvalidtoken'] = 'This login link is invalid or has expired. Please request a new one.';
$string['verifyinvalidtokentitle'] = 'Login link invalid';
$string['requestnewlink'] = 'Request a new login link';
$string['privacy:metadata'] = 'The magic link authentication plugin stores information about login requests. For each login attempt, it stores: the user ID, a one-way hash of the login token (the raw token itself is never stored), the time the link was created, and when it expires. This data is used solely to authenticate a single login attempt and is deleted after use or expiry.';
$string['privacy:metadata:auth_magiclink_token'] = 'Stores hashed magic link login tokens.';
$string['privacy:metadata:auth_magiclink_token:userid'] = 'The ID of the user who requested the login link.';
$string['privacy:metadata:auth_magiclink_token:tokenhash'] = 'A one-way hash of the login token (the raw token itself is never stored).';
$string['privacy:metadata:auth_magiclink_token:timecreated'] = 'The time the login link was requested.';
$string['taskcleanup'] = 'Clean up expired magic link tokens';
