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
 * Strings for component aiprovider_openai, language 'en'.
 *
 * @package    aiprovider_openai
 * @copyright  2024 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'OpenAI API Provider';
$string['privacy:metadata'] = 'The OpenAI API provider plugin does not store any personal data.';

$string['apikey'] = 'OpenAI API key';
$string['apikey_desc'] = 'Enter your OpenAI API key. You can get one from https://platform.openai.com/account/api-keys';
$string['enableglobalratelimit'] = 'Enable global rate limiting';
$string['enableglobalratelimit_desc'] = 'Enable global rate limiting for the OpenAI API provider.';
$string['globalratelimit'] = 'Global rate limit';
$string['globalratelimit_desc'] = 'Set the number of requests per hour allowed for the global rate limit.';
$string['enableuserratelimit'] = 'Enable user rate limiting';
$string['enableuserratelimit_desc'] = 'Enable user rate limiting for the OpenAI API provider.';
$string['userratelimit'] = 'User rate limit';
$string['userratelimit_desc'] = 'Set the number of requests per hour allowed for the user rate limit.';
$string['orgid'] = 'OpenAI organization ID';
$string['orgid_desc'] = 'Enter your OpenAI organization ID. You can get one from https://platform.openai.com/account/org-settings';
