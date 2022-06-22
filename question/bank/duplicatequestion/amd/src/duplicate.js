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
 * A javascript module to handle question duplicating.
 *
 * @module qbank_duplicatequestion/duplicate
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since 4.1
 */

import * as Modal from 'core/modal_factory';
import * as ModalEvents from 'core/modal_events';
import {get_string as getString} from 'core/str';
import {displayException} from 'core/notification';
import Ajax from 'core/ajax';

const SELECTOR = {
    duplicateQuestion: '[data-action="duplicatequestion"]'
};

/**
 * Register events for duplicate links.
 */
const registerEventListeners = () => {
    document.addEventListener('click', e => {
        const duplicateAction = e.target.closest(SELECTOR.duplicateQuestion);
        if (duplicateAction) {
            e.preventDefault();
            const modalPromise = Modal.create({
                type: Modal.types.SAVE_CANCEL,
                title: getString('confirmation', 'admin'),
                body: 'Are you absolutely sure you want to duplicate this question?',
                buttons: {
                    save: getString('yes')
                },
            }).then(modal => {
                modal.show();
                return modal;
            });
            modalPromise.then(modal => {
                modal.getRoot().on(ModalEvents.save, () => {
                    const contextId = duplicateAction.dataset.contextid;
                    const questionId = duplicateAction.dataset.questionid;
                    const url = duplicateAction.dataset.url;
                    const request = {
                        methodname: 'qbank_duplicatequestion_make_duplicate',
                        args: {
                            questionid: questionId,
                            contextid: contextId,
                            returnurl: url,
                        }
                    };

                    const response = Ajax.call([request])[0];
                    response.then(data => {
                        if (data.status) {
                            window.location.assign(data.returnurl);
                        }
                        return;
                    }).catch(displayException);
                });
                return modal;
            }).catch(displayException);
        }
    });
};

/**
 * Initialises.
 */
export const init = () => {
    registerEventListeners();
};
