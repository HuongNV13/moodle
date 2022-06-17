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
 * A javascript module to handle qbank_duplicatequestion ajax actions.
 *
 * @module qbank_duplicatequestion/repository
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since 4.1
 */

import Ajax from 'core/ajax';

/**
 * Duplicate the question in the question bank.
 *
 * @param {int} questionId The question id
 * @param {int} contextId Is random question or not
 * @param {string} returnUrl The quiz id
 * @return {promise}
 */
export const duplicateQuestion = (questionId, contextId, returnUrl) => {
    const request = {
        methodname: 'qbank_duplicatequestion_make_duplicate',
        args: {
            questionid: questionId,
            contextid: contextId,
            returnurl: returnUrl,
        }
    };

    return Ajax.call([request])[0];
};
