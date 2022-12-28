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
 * Communication plugins manager. Allow enable/disable communication plugins and jump to settings
 *
 * @package    core_communication
 * @copyright  2022 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_communication\admin;

use admin_setting;
use core_plugin_manager;
use html_table;
use html_writer;

/**
 * Class manage_communication_plugins_page.
 *
 * @package    core_communication
 * @copyright  2022 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_communication_plugins_page extends admin_setting {

    /**
     * Constructor
     */
    public function __construct() {
        $this->nosave = true;
        parent::__construct('managecommunications',
            new \lang_string('managecommunicationplugins', 'core_communication'), '', '');
    }

    /**
     * Returns current value of this setting
     *
     * @return mixed array or string depending on instance, NULL means not set yet
     */
    public function get_setting() {
        return true;
    }

    /**
     * Store new setting
     *
     * @param mixed $data string or array, must not be NULL
     * @return string empty string if ok, string error message otherwise
     */
    public function write_setting($data) {
        // Do not write any setting.
        return '';
    }

    /**
     * Return part of form with setting
     * This function should always be overwritten
     *
     * @param mixed $data array or string depending on setting
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        $pluginmanager = core_plugin_manager::instance();
        $plugins = $pluginmanager->get_plugins_of_type('communication');
        if (empty($plugins)) {
            return get_string('nocommunicationplugin', 'core_communication');
        }

        $table = new html_table();
        $table->head = [
            get_string('name'),
            get_string('enable'),
            get_string('settings'),
            get_string('uninstallplugin', 'core_admin'),
        ];
        $table->align = ['left', 'center', 'center', 'center'];
        $table->attributes['class'] = 'manageqbanktable generaltable admintable';
        $table->data  = [];

        return html_writer::table($table);
    }
}
