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
 * A javascript module to handle MoodleNet ajax actions.
 *
 * @module     tool_moodlenet/repository
 * @copyright  2022 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      4.1
 */

import Ajax from 'core/ajax';

/**
 * Get the activity information by course model id.
 *
 * @param {Number} cmId The course module id
 * @return {promise}
 */
export const getActivityInformation = (cmId) => {
    const request = {
        methodname: 'tool_moodlenet_get_activity_info',
        args: {
            cmid: cmId
        }
    };

    return Ajax.call([request])[0];
};

