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
 * This file gives information about MoodleNet.
 *
 * @package    core
 * @copyright  2023 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    if (!empty($CFG->enablesharingtomoodlenet)) {
        if (!$ADMIN->locate('moodlenet')) {
            $ADMIN->add('root', new admin_category('moodlenet', get_string('pluginname', 'tool_moodlenet')));
        }

        // Outbound settings page.
        $settings = new admin_settingpage('moodlenetoutbound', 'MoodleNet outbound settings');
        $ADMIN->add('moodlenet', $settings);

        // Get all the issuers.
        $issuers = \core\oauth2\api::get_all_issuers();
        $enabledissuers = [];
        $disabled = false;
        foreach ($issuers as $issuer) {
            // Get the enabled issuer with the service type is MoodleNet only.
            if ($issuer->get('servicetype') == 'moodlenet' && $issuer->get('enabled')) {
                $enabledissuers[] = $issuer;
            }
        }

        $oauth2services = [
            '' => new lang_string('none', 'admin'),
        ];

        if (count($enabledissuers) > 0) {
            unset($CFG->forced_plugin_settings['moodlenet']);
            foreach ($enabledissuers as $issuer) {
                $oauth2services[$issuer->get('id')] = s($issuer->get('name'));
            }
        } else {
            $CFG->forced_plugin_settings['moodlenet']['oauthservice'] = '';
        }

        $url = new \moodle_url('/admin/tool/oauth2/issuers.php');

        $settings->add(new admin_setting_configselect('moodlenet/oauthservice', new lang_string('issuer', 'auth_oauth2'),
            new lang_string('configmoodlenetoauthservice', 'admin', $url->out()), '', $oauth2services));

    }
}
