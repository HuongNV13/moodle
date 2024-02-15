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

const SELECTORS = {
    WRAPPER: '.toggle-sensitive-wrapper',
    INPUT: '.toggle-sensitive-input',
    BUTTON: '.toggle-sensitive-button',
    ICON: '.toggle-sensitive-icon',
};

/**
 * Entrypoint of the js.
 *
 * @method init
 * @param {boolean} smallscreensonly Is this for small screens?
 */
export const init = (smallscreensonly) => {
    registerListenerEvents(smallscreensonly);
};

/**
 * Register event listeners.
 *
 * @method registerListenerEvents
 * @param {boolean} smallscreensonly Is this for small screens?
 */
const registerListenerEvents = (smallscreensonly) => {
    // Toggle the sensitive input visibility when interacting with the toggle button.
    document.addEventListener('click', handleButtonInteraction);
    document.addEventListener('keydown', handleButtonInteraction);

    // For small screens only, hide all sensitive inputs when the screen is enlarged.
    if (smallscreensonly) {
        window.addEventListener('resize', () => {
            if (!isExtraSmall()) {
                document.querySelectorAll(SELECTORS.WRAPPER).forEach((element) => {
                    const input = element.querySelector(SELECTORS.INPUT);
                    const icon = element.querySelector(SELECTORS.ICON);
                    if (input.getAttribute('type') === 'text') {
                        toggleSensitiveVisibility(input, icon);
                    }
                });
            }
        });
    }
};

/**
 * Handle events trigger by interacting with the toggle button.
 *
 * @method handleButtonInteraction
 * @param {event} event The button event.
 */
const handleButtonInteraction = (event) => {
    const button = event.target.closest(SELECTORS.BUTTON);
    if (button) {
        const wrapper = event.target.closest(SELECTORS.WRAPPER);
        const input = wrapper.querySelector(SELECTORS.INPUT);
        const icon = wrapper.querySelector(SELECTORS.ICON);
        toggleSensitiveVisibility(input, icon);
    }
};

/**
 * Toggle the sensitive input visibility and its associated icon.
 *
 * @method togglesensitiveVisibility
 * @param {HTMLInputElement} input The sensitive input element.
 * @param {HTMLElement} icon The icon element.
 */
const toggleSensitiveVisibility = (input, icon) => {
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
};
