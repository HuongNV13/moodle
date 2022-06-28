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
 * A javascript module to handle quiz ajax actions.
 *
 * @module mod_quiz/repository
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since 4.1
 */

import Ajax from 'core/ajax';

/**
 * Add question to the quiz.
 *
 * @param {int} questionId The question id
 * @param {boolean} isRandom Is random question or not
 * @param {int} quizId The quiz id
 * @param {int} pageNumber The page number
 * @return {promise}
 */
export const addQuestionToQuiz = (questionId, isRandom, quizId, pageNumber) => {
    const request = {
        methodname: 'mod_quiz_add_question_to_quiz',
        args: {
            questionid: questionId,
            israndom: isRandom,
            quizid: quizId,
            page: pageNumber,
        }
    };

    return Ajax.call([request])[0];
};
