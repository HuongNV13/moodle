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
 * A javascript module to handle question duplicating for quiz.
 *
 * @module mod_quiz/duplicate
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since 4.1
 */

import Notification from 'core/notification';
import Prefetch from 'core/prefetch';
import {get_string as getString} from 'core/str';
import * as Modal from 'core/modal_factory';
import * as ModalEvents from 'core/modal_events';
import * as QuizRepository from 'mod_quiz/repository';
import * as DuplicateQuestionRepository from 'qbank_duplicatequestion/repository';

const SELECTOR = {
    duplicateQuestion: '.path-mod-quiz [data-action="duplicatequestion"]'
};

/**
 * Register events for duplicate links.
 *
 * @param {int} contextId Context id
 * @param {string} returnUrl Return url
 * @param {int} quizId Quiz id
 */
const registerEventListeners = (contextId, returnUrl, quizId) => {
    document.addEventListener('click', e => {
        const duplicateAction = e.target.closest(SELECTOR.duplicateQuestion);
        if (duplicateAction) {
            e.preventDefault();
            const questionId = duplicateAction.dataset.questionid;
            const questionName = duplicateAction.dataset.questionname;
            const questionType = duplicateAction.dataset.questiontype;
            const pageNumber = duplicateAction.dataset.page;
            const modalPromise = Modal.create({
                type: Modal.types.SAVE_CANCEL,
                title: getString('confirmation', 'admin'),
                body: getString('duplicatequestioncheck', 'qbank_duplicatequestion', questionName),
                buttons: {
                    save: getString('yes')
                },
            }).then(modal => {
                modal.show();
                return modal;
            });
            modalPromise.then(modal => {
                modal.getRoot().on(ModalEvents.save, () => {
                    if (questionType == 'random') {
                        // Random question type. No need to duplicate the question in the question bank.
                        QuizRepository.addQuestionToQuiz(questionId, true, quizId, pageNumber).then(data => {
                            if (data.status) {
                                return window.location.assign(returnUrl);
                            } else {
                                return Notification.alert(getString('error'), data.warnings[0].message);
                            }
                        }).catch(Notification.exception);
                    } else {
                        // Normal question type.
                        // First, we need to duplicate the question in the question bank.
                        DuplicateQuestionRepository.duplicateQuestion(questionId, contextId, returnUrl).then(data => {
                            if (data.status) {
                                // After we duplicate the question in the question bank.
                                // Get the created question id and add it to the Quiz.
                                const createdQuestionId = data.createdquestionid;
                                QuizRepository.addQuestionToQuiz(createdQuestionId, false, quizId, pageNumber).then(data => {
                                    if (data.status) {
                                        window.location.assign(returnUrl);
                                    } else {
                                        Notification.alert(getString('error'), data.warnings[0].message);
                                    }
                                    return;
                                }).catch(Notification.exception);
                                return;
                            } else {
                                Notification.alert(getString('error'), data.warnings[0].message);
                                return;
                            }
                        }).catch(Notification.exception);
                    }
                });
                return modal;
            }).catch(Notification.exception);
        }
    });
};

/**
 * Initialises.
 *
 * @param {int} contextId Context id
 * @param {string} returnUrl Return url
 * @param {int} quizId Quiz id
 */
export const init = (contextId, returnUrl, quizId) => {
    Prefetch.prefetchStrings('moodle', ['yes', 'error']);
    Prefetch.prefetchStrings('core_admin', ['confirmation']);
    Prefetch.prefetchStrings('qbank_duplicatequestion', ['duplicatequestioncheck']);
    registerEventListeners(contextId, returnUrl, quizId);
};
