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
 * Options helper for Tiny Accessibility Checker plugin.
 *
 * @module      tiny_accessibilitychecker/options
 * @copyright   2022 Huong Nguyen <huongnv13@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getPluginOptionName} from 'editor_tiny/options';
import {pluginName} from 'tiny_accessibilitychecker/common';

const ignoredClassesName = getPluginOptionName(pluginName, 'ignoredclasses');

/**
 * Register the options for the Tiny Accessibility Checker plugin.
 *
 * @param {TinyMCE} editor
 */
export const register = (editor) => {
    const registerOption = editor.options.register;

    registerOption(ignoredClassesName, {
        processor: 'array',
        "default": [],
    });
};

/**
 * Get the ignored classes configuration for the Tiny Accessibility Checker plugin.
 *
 * @param {TinyMCE} editor
 * @returns {object}
 */
export const getIgnoredClassesName = (editor) => editor.options.get(ignoredClassesName);

/**
 * Add the ignored class for the Tiny Accessibility Checker plugin.
 *
 * @param {TinyMCE} editor
 * @param {string} className
 */
export const addIgnoredClassName = (editor, className) => {
    let existingData = getIgnoredClassesName(editor);
    existingData.push(className);
    editor.options.set(ignoredClassesName, existingData);
};
