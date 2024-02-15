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
 * JS module for toggling the sensitive input visibility (e.g. passwords, keys).
 *
 * @module     core/togglesensitive
 * @copyright  2023 David Woloszyn <david.woloszyn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {isExtraSmall} from 'core/pagehelpers';
import Templates from 'core/templates';
import Pending from 'core/pending';
import Prefetch from 'core/prefetch';

const SELECTORS = {
    BUTTON: '.toggle-visibility-btn',
    ICON: '.toggle-visibility-btn .icon',
};

const PIX = {
    EYE: 't/hide',
    EYE_SLASH: 't/show',
};

let sensitiveElementId;
let sensitiveInputHTML;

/**
 * Entrypoint of the js.
 *
 * @method init
 * @param {String} elementId Form button element
 * @param {boolean} isSmallScreensOnly Is this for small screens?
 */
export const init = (elementId, isSmallScreensOnly) => {
    Prefetch.prefetchTemplate('core/form_sensitive_with_toggle');
    if (typeof isSmallScreensOnly === 'undefined') {
        isSmallScreensOnly = true;
    }
    const sensitiveInput = document.getElementById(elementId);
    if (sensitiveInput === null) {
        // Exit early if invalid element id passed.
        return;
    }
    sensitiveElementId = elementId;
    // Render the sensitive input toggle button.
    renderToggleButton(sensitiveInput, isSmallScreensOnly).then(() => {
        return window.console.log('Sensitive input toggle button rendered');
    });
    registerListenerEvents(isSmallScreensOnly);
};

const renderToggleButton = async(sensitiveInput, isSmallScreensOnly) => {
    sensitiveInputHTML = sensitiveInput.outerHTML;
    const {html} = await Templates.renderForPromise(
        'core/form_sensitive_with_toggle',
        {
            smallscreensonly: isSmallScreensOnly,
            sensitiveInput: sensitiveInputHTML,
        }
    );
    sensitiveInput.outerHTML = html;
};

/**
 * Register event listeners.
 *
 * @method registerListenerEvents
 * @param {boolean} isSmallScreensOnly Is this for small screens?
 */
const registerListenerEvents = (isSmallScreensOnly) => {
    // Toggle the sensitive input visibility when interacting with the toggle button.
    document.addEventListener('click', handleButtonInteraction);
    // For small screens only, hide all sensitive inputs when the screen is enlarged.
    if (isSmallScreensOnly) {
        window.addEventListener('resize', () => {
            if (!isExtraSmall()) {
                const sensitiveInput = document.getElementById(sensitiveElementId);
                const toggleButton = sensitiveInput.parentNode.querySelector(SELECTORS.BUTTON);
                toggleSensitiveVisibility(sensitiveInput, toggleButton, true);
            }
        });
    }
};

/**
 * Handle events trigger by interacting with the toggle button.
 *
 * @method handleButtonInteraction
 * @param {Event} event The button event.
 */
const handleButtonInteraction = (event) => {
    const toggleButton = event.target.closest(SELECTORS.BUTTON);
    if (toggleButton) {
        const sensitiveInput = document.getElementById(sensitiveElementId);
        toggleSensitiveVisibility(sensitiveInput, toggleButton);
    }
};

/**
 * Toggle the sensitive input visibility and its associated icon.
 *
 * @method togglesensitiveVisibility
 * @param {HTMLInputElement} sensitiveInput The sensitive input element.
 * @param {HTMLElement} toggleButton The icon element.
 * @param {boolean} force Force the display back to password.
 */
const toggleSensitiveVisibility = (sensitiveInput, toggleButton, force) => {
    const pendingPromise = new Pending('core/togglesensitive:toggle');
    let type;
    let icon;
    if (typeof force !== 'undefined' && force === true) {
        type = 'password';
        icon = PIX.EYE;
    } else {
        type = sensitiveInput.getAttribute('type') === 'password' ? 'text' : 'password';
        icon = sensitiveInput.getAttribute('type') === 'password' ? PIX.EYE_SLASH : PIX.EYE;
    }
    sensitiveInput.setAttribute('type', type);
    Templates.renderPix(icon, 'core').then((html) => {
        toggleButton.innerHTML = html;
        pendingPromise.resolve();
        return;
    });
};
