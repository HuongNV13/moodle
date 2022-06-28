<?php
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
 * Quiz external add question API unit tests.
 *
 * @package mod_quiz
 * @category test
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_quiz;

use advanced_testcase;
use mod_quiz\external\add_question_to_quiz;
use quiz;

/**
 * Quiz external add question API unit tests.
 *
 * @package mod_quiz
 * @category test
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_quiz\external\add_question_to_quiz
 */
class add_question_to_quiz_test extends advanced_testcase {

    /**
     * This method is called before each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
        $this->questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
    }

    /**
     * Test the add question to Quiz API.
     *
     * @covers ::execute
     */
    public function test_add_question_to_quiz() {
        $this->resetAfterTest();

        // Create Quiz.
        $quiz = $this->quizgenerator->create_instance(['course' => $this->course->id]);

        // Create a couple of questions.
        $cat = $this->questiongenerator->create_question_category();
        $saq = $this->questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);
        $numq = $this->questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        $tfq = $this->questiongenerator->create_question('truefalse', null, ['category' => $cat->id]);

        // Get the Quiz object.
        $quizobj = quiz::create($quiz->id);

        // Verify that there is no question in the Quiz.
        $this->assertEquals(0, $quizobj->get_structure()->get_question_count());

        // Add the saq question to the Quiz via API.
        $result = add_question_to_quiz::execute($saq->id, false, $quiz->id, 0);
        $this->assertTrue($result['status']);
        $this->assertEmpty($result['warnings']);

        // Verify that there is 1 question in the Quiz now.
        $this->assertEquals(1, $quizobj->get_structure()->get_question_count());
        // Verify that the saq question will be added to slot 1.
        $this->assertEquals(1, $quizobj->get_structure()->get_question_by_id($saq->id)->slot);
        $this->assertEquals($saq->id, $quizobj->get_structure()->get_question_in_slot(1)->questionid);
        // Verify that the saq question will be added to page 1.
        $this->assertEquals(1, $quizobj->get_structure()->get_question_by_id($saq->id)->page);

        // Add the numq question to the Quiz via API (in the same page with the saq).
        $result = add_question_to_quiz::execute($numq->id, false, $quiz->id, 1);
        $this->assertTrue($result['status']);
        $this->assertEmpty($result['warnings']);

        // Verify that there is 2 questions in the Quiz now.
        $this->assertEquals(2, $quizobj->get_structure()->get_question_count());
        // Verify that the numq question will be added to slot 2.
        $this->assertEquals(2, $quizobj->get_structure()->get_question_by_id($numq->id)->slot);
        $this->assertEquals($numq->id, $quizobj->get_structure()->get_question_in_slot(2)->questionid);
        // Verify that the numq question will be added to page 1.
        $this->assertEquals(1, $quizobj->get_structure()->get_question_by_id($numq->id)->page);

        // Add the tfq question to the Quiz via API (in new page).
        $result = add_question_to_quiz::execute($tfq->id, false, $quiz->id, 0);
        $this->assertTrue($result['status']);
        $this->assertEmpty($result['warnings']);

        // Verify that there is 3 questions in the Quiz now.
        $this->assertEquals(3, $quizobj->get_structure()->get_question_count());
        // Verify that the tfq question will be added to slot 3.
        $this->assertEquals(3, $quizobj->get_structure()->get_question_by_id($tfq->id)->slot);
        $this->assertEquals($tfq->id, $quizobj->get_structure()->get_question_in_slot(3)->questionid);
        // Verify that the tfq question will be added to page 2.
        $this->assertEquals(2, $quizobj->get_structure()->get_question_by_id($tfq->id)->page);

        // Add the non-existing question.
        $result = add_question_to_quiz::execute(9999, false, $quiz->id, 0);
        $this->assertFalse($result['status']);
        $this->assertNotEmpty($result['warnings']);
        $warning = $result['warnings'][0];
        $this->assertEquals(get_string('questiondoesnotexist', 'question'), $warning['message']);
        $this->assertEquals('erroraddquestiontoquiz', $warning['warningcode']);
        $this->assertEquals(9999, $warning['item']);

        // Add the non-existing quiz.
        $result = add_question_to_quiz::execute($tfq->id, false, 9999, 0);
        $this->assertFalse($result['status']);
        $this->assertNotEmpty($result['warnings']);
        $warning = $result['warnings'][0];
        $this->assertEquals(get_string('errorinvalidquiz', 'quiz'), $warning['message']);
        $this->assertEquals('erroraddquestiontoquiz', $warning['warningcode']);
        $this->assertEquals(9999, $warning['item']);
    }
}
