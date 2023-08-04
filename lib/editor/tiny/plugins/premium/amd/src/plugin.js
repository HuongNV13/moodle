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

import {getTinyMCE} from 'editor_tiny/loader';
import {getPluginMetadata} from 'editor_tiny/utils';

import {component, pluginName} from 'tiny_premium/common';
import * as Configuration from 'tiny_premium/configuration';
import * as Options from 'tiny_premium/options';
import {apiKey} from 'tiny_premium/external';

// let tinyPremiumPromise;
// /**
//  * Promise for Tiny Premium API key authentication.
//  *
//  * @param {string} apikey
//  * @return {Promise}
//  */
// const getTinyPremium = (apikey) => {
//     if (!apikey) {
//         return Promise.resolve();
//     }

//     if (tinyPremiumPromise) {
//         return tinyPremiumPromise;
//     }

//     tinyPremiumPromise = new Promise((resolve, reject) => {
//         const head = document.querySelector('head');
//         const script = document.createElement('script');
//         script.dataset.tinymce = 'premium';
//         script.src = `https://cdn.tiny.cloud/1/${apikey}/tinymce/6/plugins.min.js`;
//         script.referrerpolicy = "origin";

//         script.addEventListener('load', () => {
//             resolve();
//         }, false);

//         script.addEventListener('error', (err) => {
//             reject(err);
//         }, false);

//         head.append(script);
//     });

//     return tinyPremiumPromise;
// };

/**
 * Tiny Premium plugin for Moodle.
 *
 * @module      tiny_premium/plugin
 * @copyright   2023 David Woloszyn <david.woloszyn@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// eslint-disable-next-line no-async-promise-executor
export default new Promise(async(resolve) => {
    const [
        tinyMCE,
        pluginMetadata,
    ] = await Promise.all([
        getTinyMCE(),
        getPluginMetadata(component, pluginName),
        //getTinyPremium(apiKey)
    ]);

    tinyMCE.PluginManager.add(`${component}/plugin`, (editor) => {
        // Register options.
        Options.register(editor);

        window.console.log(apiKey);

        return pluginMetadata;
    });

    // Resolve the Premium Plugin and include configuration.
    resolve([`${component}/plugin`, Configuration]);
});
