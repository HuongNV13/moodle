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
 * Magic link authentication request form.
 *
 * @package auth_magiclink
 * @copyright 2026 Moodle Pty Ltd
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_magiclink\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for requesting a magic link login token.
 *
 * @package auth_magiclink
 * @copyright 2026 Moodle Pty Ltd
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class request_form extends \moodleform {

    /**
     * Define the form fields.
     */
    protected function definition() {
        $mform = $this->_form;

        // Add a header.
        $mform->addElement('header', 'header', get_string('requestformusername', 'auth_magiclink'));

        // Add instructions text.
        $mform->addElement('static', 'instructions', '', get_string('requestforminstructions', 'auth_magiclink'));

        // Add username/email field.
        $mform->addElement('text', 'username', get_string('requestformusername', 'auth_magiclink'), 'size="30" maxlength="100"');
        $mform->setType('username', PARAM_RAW_TRIMMED);
        $mform->addRule('username', get_string('required'), 'required', null, 'client');

        // Add submit button.
        $submitlabel = get_string('requestformsubmit', 'auth_magiclink');
        $mform->addElement('submit', 'submit', $submitlabel);
    }

    /**
     * Validate form data.
     *
     * @param array $data Form data.
     * @param array $files Uploaded files.
     * @return array Validation errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['username'])) {
            $errors['username'] = get_string('required');
        }

        return $errors;
    }
}
