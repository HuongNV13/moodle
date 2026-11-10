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
 * Render the question slot template for each question in the quiz edit view.
 *
 * @module     mod_quiz/question_slot
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Guillermo Gomez Arias <guillermogomez@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import Notification from 'core/notification';
import Pending from 'core/pending';

/**
 * Set the question version for the slot.
 *
 * @param {Number} slotId
 * @param {Number} newVersion
 * @return {Array} The modified question version
 */
const setQuestionVersion = (slotId, newVersion) => fetchMany([{
    methodname: 'mod_quiz_set_question_version',
    args: {
        slotid: slotId,
        newversion: newVersion,
    }
}])[0];

/**
 * Replace the container with a new version.
 */
const registerEventListeners = () => {
    document.addEventListener('change', e => {
        if (!e.target.matches('[data-action="mod_quiz-select_slot"][data-slot-id]')) {
            return;
        }

        const slotId = e.target.dataset.slotId;
        const newVersion = parseInt(e.target.value);

        // Keep this pending until the page has been reloaded. It is intentionally not resolved on success, because the
        // reload discards the document. Otherwise automated tests could act on the old page while it is unloading.
        const pendingPromise = new Pending('mod_quiz/question_slot:setversion');

        setQuestionVersion(slotId, newVersion === 0 ? null : newVersion)
            .then(() => {
                location.reload();
                return;
            })
            .catch((error) => {
                pendingPromise.resolve();
                return Notification.exception(error);
            });
    });
};

/**
 * Entrypoint of the js.
 */
export const init = () => {
    registerEventListeners();
};
