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
 * Tiny Premium configuration.
 *
 * @module      tiny_premium/configuration
 * @copyright   2023 David Woloszyn <david.woloszyn@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//import {addToolbarButton} from 'editor_tiny/utils';

export const configure = () => {
    // Update the instance configuration to add the Tiny Premium options to the menus.

    //getTinyPremium(options.)
    // instanceConfig.plugins += ` a11ychecker advcode editimage anchor autolink charmap codesample`;
    // instanceConfig.plugins += ` emoticons image link lists media searchreplace table visualblocks`;
    // instanceConfig.plugins += ` wordcount checklist mediaembed casechange export formatpainter`;
    // instanceConfig.plugins += ` pageembed linkchecker a11ychecker tinymcespellchecker permanentpen`;
    // instanceConfig.plugins += ` powerpaste advtable advcode editimage tinycomments tableofcontents`;
    // instanceConfig.plugins += ` footnotes mergetags autocorrect typography`;

    //works
    // window.console.log(options);
    // instanceConfig.plugins += ` formatpainter`;
    // instanceConfig.toolbar = addToolbarButton(instanceConfig.toolbar, 'content', 'formatpainter');

    //instanceConfig.plugins += ` advcode`;
    //instanceConfig.toolbar = addToolbarButton(instanceConfig.toolbar, 'content', 'code');

    //works
    // instanceConfig.plugins += ` advtable`;
    // instanceConfig.toolbar = addToolbarButton(instanceConfig.toolbar, 'content', 'advtablerownumbering');

    //instanceConfig.plugins += ` ai`;
    //instanceConfig.toolbar = addToolbarButton(instanceConfig.toolbar, 'content', 'aidialog aishortcuts');

    //works
    // instanceConfig.plugins += ` tinymcespellchecker`;
    // instanceConfig.toolbar = addToolbarButton(instanceConfig.toolbar, 'content', 'spellchecker');
    // instanceConfig.toolbar = addToolbarButton(instanceConfig.toolbar, 'content', 'language');
    // instanceConfig.toolbar = addToolbarButton(instanceConfig.toolbar, 'content', 'spellcheckdialog');

    //autocorrect_capitalize: true;

    //works
    // instanceConfig.plugins += ` autocorrect`;
    // instanceConfig.autocorrect_capitalize = options.plugins["tiny_premium/plugin"].config.autocorrect_capitalize;
    //instanceConfig.toolbar = addToolbarButton(instanceConfig.toolbar, 'content', 'spellchecker');

    //return instanceConfig;

};
