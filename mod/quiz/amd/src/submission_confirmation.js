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
 * A javascript module to handle submission confirmation for quiz.
 *
 * @module mod_quiz/submission_confirmation
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since 4.1
 */

import Notification from 'core/notification';
import Prefetch from 'core/prefetch';
import {get_string as getString} from 'core/str';
import * as Modal from 'core/modal_factory';
import * as ModalEvents from 'core/modal_events';

const SELECTOR = {
    attemptSubmitButton: '.path-mod-quiz .btn-finishattempt button',
    attemptSubmitForm: 'form#frm-finishattempt',
};

/**
 * Register events for attempt submit button.
 */
const registerEventListeners = () => {
    document.addEventListener('click', e => {
        const submitAction = e.target.closest(SELECTOR.attemptSubmitButton);
        if (submitAction) {
            e.preventDefault();
            const modalPromise = Modal.create({
                type: Modal.types.SAVE_CANCEL,
                title: getString('submission_confirmation', 'quiz'),
                body: getString('submission_confirmation_content', 'quiz'),
                buttons: {
                    save: getString('submit', 'core')
                },
            }).then(modal => {
                modal.show();
                return modal;
            });
            modalPromise.then(modal => {
                modal.getRoot().on(ModalEvents.save, () => {
                    const attemptForm = submitAction.closest(SELECTOR.attemptSubmitForm);
                    attemptForm.submit();
                });
                return modal;
            }).catch(Notification.exception);
        }
    });
};

/**
 * Initialises.
 */
export const init = () => {
    Prefetch.prefetchStrings('core', ['submit']);
    Prefetch.prefetchStrings('core_admin', ['confirmation']);
    Prefetch.prefetchStrings('quiz', ['submission_confirmation', 'submission_confirmation_content']);
    registerEventListeners();
};
