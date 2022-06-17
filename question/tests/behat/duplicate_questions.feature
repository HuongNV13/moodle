@core @core_question
Feature: A teacher can duplicate questions in the question bank
  In order to efficiently expand my question bank
  As a teacher
  I need to be able to duplicate existing questions and make small changes

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher  | Teacher   | One      | teacher@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | weeks  |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype | name                       | questiontext                  | idnumber |
      | Test questions   | essay | Test question to be copied | Write about whatever you want | qid      |
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration

  @javascript
  Scenario: Duplicating a previously created question
    Given I choose "Duplicate" action for "Test question to be copied" in the question bank
    And I should see "Are you sure you want to duplicate the 'Test question to be copied' question?"
    When I click on "Yes" "button" in the "Confirmation" "dialogue"
    Then I should see "Test question to be copied (copy)"
    And I should see "Test question to be copied"
    And "Test question to be copied ID number qid" row "Created by" column of "categoryquestions" table should contain "Admin User"

  @javascript
  Scenario: The duplicate operation can be cancelled
    Given I choose "Duplicate" action for "Test question to be copied" in the question bank
    And I should see "Are you sure you want to duplicate the 'Test question to be copied' question?"
    When I click on "Cancel" "button" in the "Confirmation" "dialogue"
    Then I should see "Test question to be copied"
    Then I should not see "Test question to be copied (copy)"

  @javascript
  Scenario: Duplicating a question with an idnumber increments it
    Given the following "questions" exist:
      | questioncategory | qtype | name                   | questiontext                  | idnumber |
      | Test questions   | essay | Question with idnumber | Write about whatever you want | id101    |
    And I reload the page
    When I choose "Duplicate" action for "Question with idnumber" in the question bank
    And I should see "Are you sure you want to duplicate the 'Question with idnumber' question?"
    When I click on "Yes" "button" in the "Confirmation" "dialogue"
    Then I should see "Question with idnumber (copy)"
    And I should see "id102" in the "Question with idnumber (copy)" "table_row"
