<?php
// This file is part of Moodle - https://moodle.org/
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
 * Adds admin settings for the plugin.
 *
 * @package    comm_matrix
 * @copyright  2022 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Column sort order link in manageqbanks page.
$url = new moodle_url('/communication/matrix/settings.php', ['section' => 'comm_matrix']);
if ($ADMIN->fulltree) {
    $page = $adminroot->locate('managecommunications');
    if (isset($page)) {
        $page->add(new admin_setting_description(
            'managecommmatrix',
            '',
            new lang_string('qbankgotocolumnsort', 'qbank_columnsortorder',
                html_writer::link($url, get_string('qbankcolumnsortorder', 'qbank_columnsortorder')))
        ));
    }
}

// Column sort order link in admin page.
$settings = new admin_externalpage('comm_matrix', get_string('qbankcolumnsortorder', 'qbank_columnsortorder'), $url);
