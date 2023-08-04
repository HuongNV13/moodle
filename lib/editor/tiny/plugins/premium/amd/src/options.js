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
 * Options helper for Tiny Premium plugin.
 *
 * @module      tiny_premium/options
 * @copyright   2023 David Woloszyn <david.woloszyn@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getPluginOptionName} from 'editor_tiny/options';
import {pluginName} from 'tiny_premium/common';

const apiKeyName = getPluginOptionName(pluginName, 'apikey');

/**
 * Register the options for the Tiny Premium plugin.
 *
 * @param {TinyMCE} editor
 */
export const register = (editor) => {
    const registerOption = editor.options.register;

    registerOption(apiKeyName, {
        processor: 'string'
    });
};

// /**
//  * Get the permissions configuration for the Tiny Premium plugin.
//  *
//  * @param {TinyMCE} editor
//  * @returns {object}
//  */
// export const getPermissions = (editor) => editor.options.get(permissionsName);

/**
 * Get the API Key for Tiny Premium.
 *
 * @param {tinyMCE} editor The editor instance to fetch the value for
 * @returns {string} The value of the myFirstProperty option
 */
export const getApiKey = (editor) => editor.options.get(apiKeyName);
