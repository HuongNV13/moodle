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
 * Regrade modal form is used to regrade or dryrun the attempts and questions.
 *
 * @module quiz_overview/regrade_modal
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';
import Notification from 'core/notification';
import RegradeModal from 'quiz_overview/regrade_modal_type';
import * as ModalEvents from 'core/modal_events';

/**
 * @type {Object} selectors used in this code.
 */
const SELECTORS = {
    'allQuestionsButton': '#regradeallquestions',
    'dryRunButton': '#dryrunbutton',
    'mainTableForm': '#attemptsform',
    'questionCheckbox': '[id^="regradeslot"]',
    'regradeAttemptsButtonId': 'regradeattempts',
    'regradeButton': '#regradebutton',
    'reportTableSelectedAttempts': '[id^="attemptid_"]:checked',
};

/**
 * Show the regrade modal.
 *
 * @param {Event} e click event that opened the modal.
 * @returns {Promise<void>}
 */
const showModal = async(e) => {
    e.preventDefault();
    try {
        let hiddenInputs = [];
        document.querySelectorAll(SELECTORS.mainTableForm + ' input[type=hidden]').forEach((hiddenInput) => {
            hiddenInputs.push({'name': hiddenInput.name, 'value': hiddenInput.value});
        });
        document.querySelectorAll(SELECTORS.reportTableSelectedAttempts).forEach((selectedAttempt) => {
            hiddenInputs.push({'name': selectedAttempt.name, 'value': selectedAttempt.value});
        });
        const modalConfig = {
            templateContext: {
                'actionurl': document.querySelector(SELECTORS.mainTableForm).action,
                'hasselectedattempts': document.querySelector(SELECTORS.reportTableSelectedAttempts) !== null,
                'questions': JSON.parse(document.getElementById(SELECTORS.regradeAttemptsButtonId).dataset.slots),
                'hiddeninputs': hiddenInputs,
                'regradehelp': {text: await getString('regrade_help', 'quiz_overview')},
            },
        };
        const modalPromise = RegradeModal.create(modalConfig);
        modalPromise.then((modal) => {
            modal.getRoot().on(ModalEvents.shown, () => {
                const $modal = modal.getModal();
                const modalElement = $modal[0];
                modalElement.querySelector('form').addEventListener('change', updateButtonStates);
                updateButtonStates();
            });

            modal.show();
            return modal;
        });
    } catch (ex) {
        await Notification.exception(ex);
    }
};

/**
 * Enables and disables controls when the selected options are changed.
 */
const updateButtonStates = () => {
    const allQuestionsButton = document.querySelector(SELECTORS.allQuestionsButton);

    // Question checkboxes enabled only if Selected questions is checked.
    document.querySelectorAll(SELECTORS.questionCheckbox).forEach((questionCheckbox) => {
        questionCheckbox.disabled = allQuestionsButton.checked;
    });

    // State of submit buttons.
    const isAnyQuestionSelected = document.querySelector(SELECTORS.questionCheckbox + ':checked') !== null;
    const canSubmit = allQuestionsButton.checked || isAnyQuestionSelected;
    document.querySelector(SELECTORS.regradeButton).disabled = !canSubmit;
    document.querySelector(SELECTORS.dryRunButton).disabled = !canSubmit;
};

/**
 * Initialize the regrade button to open the modal.
 */
export const init = () => {
    const regradeButton = document.getElementById(SELECTORS.regradeAttemptsButtonId);
    if (!regradeButton) {
        return;
    }

    regradeButton.addEventListener('click', showModal);
};
