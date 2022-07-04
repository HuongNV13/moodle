@mod @mod_quiz
Feature: Submission confirmation
  In order to know how the total of un-answered questions
  As a student
  I need to see a submission confirmation dialogue that contains the total of unanswered questions

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | student  | Student   | 1        | teacher@example.com |
    And the following "course enrolments" exist:
      | user    | course | role    |
      | student | C1     | student |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "activities" exist:
      | activity | name   | course | idnumber | navmethod  |
      | quiz     | Quiz 1 | C1     | quiz1    | free       |
      | quiz     | Quiz 2 | C1     | quiz2    | sequential |
    And the following "questions" exist:
      | questioncategory | qtype     | name            | questiontext               |
      | Test questions   | truefalse | First question  | Answer the first question  |
      | Test questions   | truefalse | Second question | Answer the second question |
    And quiz "Quiz 1" contains the following questions:
      | question        | page |
      | First question  | 1    |
      | Second question | 2    |
    And quiz "Quiz 2" contains the following questions:
      | question        | page |
      | First question  | 1    |
      | Second question | 2    |

  @javascript
  Scenario: The warning will not be shown if all the questions have been answered
    Given I am on the "Quiz 1" "quiz activity" page logged in as student
    And I press "Attempt quiz"
    And I should see "Answer the first question"
    And I set the field "True" to "1"
    And I press "Next page"
    And I should see "Answer the second question"
    And I set the field "False" to "1"
    And I press "Finish attempt ..."
    And I should see "Answer saved" in the "1" "table_row"
    And I should see "Answer saved" in the "2" "table_row"
    And I should not see "Not yet answered"
    When I press "Submit all and finish"
    Then I should see "Are you ready to submit? You will no longer be able to change your answers for this attempt." in the "Submission confirmation" "dialogue"
    And I should not see "questions without a responses." in the "Submission confirmation" "dialogue"

  @javascript
  Scenario: The warning will not be shown if all the quiz navigation method is set to sequential
    Given I am on the "Quiz 2" "quiz activity" page logged in as student
    And I press "Attempt quiz"
    And I should see "Answer the first question"
    And I press "Next page"
    And I should see "Answer the second question"
    And I press "Finish attempt ..."
    And I should see "Not yet answered" in the "1" "table_row"
    And I should see "Not yet answered" in the "2" "table_row"
    When I press "Submit all and finish"
    Then I should see "Are you ready to submit? You will no longer be able to change your answers for this attempt." in the "Submission confirmation" "dialogue"
    And I should not see "questions without a responses." in the "Submission confirmation" "dialogue"

  @javascript
  Scenario: The warning will be shown if there is an un-answered question
    Given I am on the "Quiz 1" "quiz activity" page logged in as student
    And I press "Attempt quiz"
    And I should see "Answer the first question"
    And I set the field "True" to "1"
    And I press "Next page"
    And I should see "Answer the second question"
    And I press "Finish attempt ..."
    And I should see "Answer saved" in the "1" "table_row"
    And I should see "Not yet answered" in the "2" "table_row"
    When I press "Submit all and finish"
    Then I should see "Are you ready to submit? You will no longer be able to change your answers for this attempt." in the "Submission confirmation" "dialogue"
    And I should see "You have 1 question(s) without a responses." in the "Submission confirmation" "dialogue"
