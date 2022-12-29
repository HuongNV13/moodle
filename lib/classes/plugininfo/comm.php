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
 * Defines classes used for plugin info.
 *
 * @package    core
 * @copyright  2022 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\plugininfo;

use core_plugin_manager;
use moodle_url;

/**
 * Class for communication plugins
 *
 * @package    core
 * @copyright  2022 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comm extends base {
    /**
     * Return URL used for management of plugins of this type.
     *
     * @return moodle_url
     */
    public static function get_manage_url(): moodle_url {
        return new moodle_url('/admin/settings.php', ['section' => 'managecommunications']);
    }

    /**
     * Get the name for the settings section.
     *
     * @return string
     */
    public function get_settings_section_name(): string {
        return $this->type . '_' . $this->name;
    }

    /**
     * Enable or disable a plugin.
     * When possible, the change will be stored into the config_log table, to let admins check when/who has modified it.
     *
     * @param string $pluginname The plugin name to enable/disable.
     * @param int $enabled Whether the pluginname should be enabled (1) or not (0). This is an integer because some plugins, such
     * as filters or repositories, might support more statuses than just enabled/disabled.
     *
     * @return bool Whether $pluginname has been updated or not.
     */
    public static function enable_plugin(string $pluginname, int $enabled): bool {
        $haschanged = false;

        $plugin = 'comm_' . $pluginname;
        $oldvalue = get_config($plugin, 'disabled');
        $disabled = !$enabled;
        // Only set value if there is no config setting or if the value is different from the previous one.
        if ($oldvalue == false && $disabled) {
            set_config('disabled', $disabled, $plugin);
            $haschanged = true;
        } else if ($oldvalue != false && !$disabled) {
            unset_config('disabled', $plugin);
            $haschanged = true;
        }

        if ($haschanged) {
            add_to_config_log('disabled', $oldvalue, $disabled, $plugin);
            core_plugin_manager::reset_caches();
        }

        return $haschanged;
    }

}
