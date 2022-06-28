@mod @mod_quiz
Feature: Edit quiz page - duplicate question
  In order to build the quiz I want my students to attempt
  As a teacher
  I need to be able to duplicate question in the quiz.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | T1        | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name   | intro                                    | course | idnumber |
      | quiz     | Quiz 1 | Quiz 1 for testing the duplicate feature | C1     | quiz1    |
      | quiz     | Quiz 2 | Quiz 2 for testing the duplicate feature | C1     | quiz2    |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name       | questiontext        |
      | Test questions   | truefalse | Question A | This is question 01 |
      | Test questions   | truefalse | Question B | This is question 02 |
    And the following "core_question > Tags" exist:
      | question   | tag |
      | Question A | foo |
      | Question B | bar |
    And quiz "Quiz 1" contains the following questions:
      | question   | page |
      | Question A | 1    |
      | Question B | 2    |

  @javascript
  Scenario: Duplicate the question by clicking on the duplicate icon
    Given I am on the "Quiz 1" "mod_quiz > Edit" page logged in as "teacher1"
    And I should see "Question A" on quiz page "1"
    And I should see "Question B" on quiz page "2"
    When I duplicate "Question A" in the quiz by clicking the duplicate icon
    Then I should see "Are you sure you want to duplicate the 'Question A' question?"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I should see "Question A (copy)" on quiz page "1"
    And I duplicate "Question B" in the quiz by clicking the duplicate icon
    And I should see "Are you sure you want to duplicate the 'Question B' question?"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I should see "Question B (copy)" on quiz page "2"

  @javascript
  Scenario: The duplicate operation in the Quiz can be cancelled
    Given I am on the "Quiz 1" "mod_quiz > Edit" page logged in as "teacher1"
    And I should see "Question A" on quiz page "1"
    And I duplicate "Question A" in the quiz by clicking the duplicate icon
    And I should see "Are you sure you want to duplicate the 'Question A' question?"
    When I click on "Cancel" "button" in the "Confirmation" "dialogue"
    Then I should see "Question A" on quiz page "1"
    And I should not see "Question A (copy)" on quiz page "1"

  @javascript
  Scenario: Duplicate the random question by clicking on the duplicate icon
    Given I am on the "Quiz 2" "mod_quiz > Edit" page logged in as "teacher1"
    And I open the "last" add to quiz menu
    And I click on "a random question" "link"
    And I set the field "Tags" to "foo"
    And I press "Add random question"
    And I should see "Random (Test questions, tags: foo)" on quiz page "1"
    When I duplicate "Random (Test questions, tags: foo)" in the quiz by clicking the duplicate icon
    Then I should see "Are you sure you want to duplicate the 'Random question' question?"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I should see "Random (Test questions, tags: foo)" on quiz page "1"
    And "Random (Test questions, tags: foo)" should have number "1" on the edit quiz page
    And "Random (Test questions, tags: foo)" should have number "2" on the edit quiz page
