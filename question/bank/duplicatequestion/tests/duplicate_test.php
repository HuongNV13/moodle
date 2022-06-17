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
 * Question bank external duplicate API unit tests.
 *
 * @package qbank_duplicatequestion
 * @category external
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qbank_duplicatequestion;

use advanced_testcase;
use context_course;
use qbank_duplicatequestion\external\duplicate;
use question_bank;

/**
 * Question bank external duplicate API unit tests.
 *
 * @package qbank_duplicatequestion
 * @category external
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \qbank_duplicatequestion\external\duplicate
 */
class duplicate_test extends advanced_testcase {

    /**
     * This method is called before each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
        $this->questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->context = context_course::instance($this->course->id);
    }

    /**
     * Test the question duplication API.
     *
     * @covers ::execute
     */
    public function test_duplicate_question() {
        $this->resetAfterTest();

        // Create category.
        $cat = $this->questiongenerator->create_question_category(['contextid' => $this->context->id]);

        // Create a a True/False question.
        $firstquestionname = 'This is a test question';
        $secondquestionname = 'This is a test question updated';
        $question = $this->questiongenerator->create_question('truefalse', null, [
            'category' => $cat->id, 'name' => $firstquestionname]);

        // Update the question with the second name.
        $this->questiongenerator->update_question($question, null, ['name' => $secondquestionname]);

        // Duplicate the question.
        $result = duplicate::execute($question->id, $this->context->id, '#');
        $this->assertTrue($result['status']);
        $this->assertEmpty($result['warnings']);
        $duplicatedquestionid = $result['createdquestionid'];

        // Load the question data.
        $duplicatedquestion = question_bank::load_question_data($duplicatedquestionid);
        $originalquestion = question_bank::load_question_data($question->id);

        // Duplicated question will always get the latest version of the original question.
        $this->assertNotEquals(get_string('questionnamecopy', 'question', $firstquestionname), $duplicatedquestion->name);
        $this->assertEquals(get_string('questionnamecopy', 'question', $secondquestionname), $duplicatedquestion->name);

        // Duplicated question will have the same information as the original question.
        $this->assertEquals($originalquestion->category, $duplicatedquestion->category);
        $this->assertEquals($originalquestion->questiontext, $duplicatedquestion->questiontext);
        $this->assertEquals($originalquestion->generalfeedback, $duplicatedquestion->generalfeedback);
        $this->assertEquals($originalquestion->defaultmark, $duplicatedquestion->defaultmark);

        // The version of the duplicated question will be reset.
        $this->assertEquals(2, $originalquestion->version);
        $this->assertEquals(1, $duplicatedquestion->version);

        // Duplicate the not existing question.
        $result = duplicate::execute(9999, $this->context->id, '#');
        $this->assertFalse($result['status']);
        $this->assertNotEmpty($result['warnings']);
        $warning = $result['warnings'][0];
        $this->assertEquals(get_string('questiondoesnotexist', 'question'), $warning['message']);
        $this->assertEquals('errorduplicatequestion', $warning['warningcode']);
        $this->assertEquals(9999, $warning['item']);
    }
}
