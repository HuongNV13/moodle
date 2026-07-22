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
 * Admin settings and defaults for magic link authentication plugin.
 *
 * @package auth_magiclink
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Introductory explanation.
    $settings->add(new admin_setting_heading('auth_magiclink/pluginname', '',
        new lang_string('auth_magiclinkdescription', 'auth_magiclink')));

    // Magic link token expiry time in minutes.
    $settings->add(new admin_setting_configtext('auth_magiclink/expiryminutes',
        new lang_string('expiryminutes', 'auth_magiclink'),
        new lang_string('expiryminutes_desc', 'auth_magiclink'), 15, PARAM_INT));
}
