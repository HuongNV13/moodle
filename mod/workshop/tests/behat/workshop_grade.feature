@mod @mod_workshop
Feature: Workshop grade submission and assessment
  In order to use workshop activity
  As a teacher
  I need to be able to grade student's submissions and feedbacks

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
      | student3 | Sam3      | Student3 | student3@example.com |
      | student4 | Sam4      | Student4 | student3@example.com |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname  | shortname |
      | Course1   | c1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | c1     | student        |
      | student2 | c1     | student        |
      | student3 | c1     | student        |
      | student4 | c1     | student        |
      | teacher1 | c1     | editingteacher |
    And the following "activities" exist:
      | activity | name         | intro                     | course | idnumber  | submissiontypetext | submissiontypefile | grade | gradinggrade | gradedecimals | overallfeedbackmethod |
      | workshop | TestWorkshop | Test workshop description | c1     | workshop1 | 2                  | 1                  | 10    | 5            | 1             | 2                     |

  @javascript
  Scenario: Assess submissions and gradings in workshop with javascript enabled
# teacher1 sets up assessment form and changes the phase to submission
    When I log in as "teacher1"
    And I am on "Course1" course homepage
    And I edit assessment form in workshop "TestWorkshop" as:"
      | id_description__idx_0_editor | Aspect1 |
      | id_description__idx_1_editor |         |
      | id_description__idx_2_editor |         |
    And I change phase in workshop "TestWorkshop" to "Submission phase"
    And I log out
# student1 submits
    And I am on the TestWorkshop "workshop activity" page logged in as student1
    Then I should see "Submit your work"
    And I add a submission in workshop "TestWorkshop" as:"
      | Title              | Submission1  |
      | Submission content | Some content |
    And "//div[@class='submission-full' and contains(.,'Submission1') and contains(.,'submitted on')]" "xpath_element" should exist
    And I log out
# teacher1 allocates reviewers and changes the phase to assessment
    And I am on the TestWorkshop "workshop activity" page logged in as teacher1
    And I change window size to "large"
    And I click on "Close course index" "button"
    And I should see "to allocate: 1"
    Then I should see "Workshop submissions report"
    And I should see "Submitted (1) / not submitted (3)"
    And I should see "Submission1" in the "Sam1 Student1" "table_row"
    And I should see "No submission found for this user" in the "Sam2 Student2" "table_row"
    And I should see "No submission found for this user" in the "Sam3 Student3" "table_row"
    And I should see "No submission found for this user" in the "Sam4 Student4" "table_row"
    And I allocate submissions in workshop "TestWorkshop" as:"
      | Participant   | Reviewer      |
      | Sam1 Student1 | Sam2 Student2 |
      | Sam1 Student1 | Sam3 Student3 |
      | Sam1 Student1 | Sam4 Student4 |
    And I am on the TestWorkshop "workshop activity" page
    And I should see "to allocate: 0"
    And I change phase in workshop "TestWorkshop" to "Assessment phase"
    And I log out
# student2 assesses work of student1
    And I am on the TestWorkshop "workshop activity" page logged in as student2
    And "//ul[@class='tasks']/li[div[@class='title' and contains(.,'Assess peers')]]/div[@class='details' and contains(.,'pending: 1') and contains(.,'total: 1')]" "xpath_element" should exist
    And I assess submission "Sam1" in workshop "TestWorkshop" as:"
      | grade__idx_0            | 10 / 10   |
      | peercomment__idx_0      | Amazing   |
      | Feedback for the author | Good work |
    And "//ul[@class='tasks']/li[div[@class='title' and contains(.,'Assess peers')]]/div[@class='details' and contains(.,'pending: 0') and contains(.,'total: 1')]" "xpath_element" should exist
    And I log out
# student3 assesses work of student1
    And I am on the TestWorkshop "workshop activity" page logged in as student3
    And "//ul[@class='tasks']/li[div[@class='title' and contains(.,'Assess peers')]]/div[@class='details' and contains(.,'pending: 1') and contains(.,'total: 1')]" "xpath_element" should exist
    And I assess submission "Sam1" in workshop "TestWorkshop" as:"
      | grade__idx_0            | 10 / 10   |
      | peercomment__idx_0      | Amazing   |
      | Feedback for the author | Good work |
    And "//ul[@class='tasks']/li[div[@class='title' and contains(.,'Assess peers')]]/div[@class='details' and contains(.,'pending: 0') and contains(.,'total: 1')]" "xpath_element" should exist
    And I log out
# student4 assesses work of student1
    And I am on the TestWorkshop "workshop activity" page logged in as student4
    And "//ul[@class='tasks']/li[div[@class='title' and contains(.,'Assess peers')]]/div[@class='details' and contains(.,'pending: 1') and contains(.,'total: 1')]" "xpath_element" should exist
    And I assess submission "Sam1" in workshop "TestWorkshop" as:"
      | grade__idx_0            | 6 / 10            |
      | peercomment__idx_0      | You can do better |
      | Feedback for the author | Good work         |
    And "//ul[@class='tasks']/li[div[@class='title' and contains(.,'Assess peers')]]/div[@class='details' and contains(.,'pending: 0') and contains(.,'total: 1')]" "xpath_element" should exist
    And I log out
# teacher1 makes sure he can see all peer grades and changes to grading evaluation phase
    And I am on the TestWorkshop "workshop activity" page logged in as teacher1
    And I should see grade "10.0" for workshop participant "Sam1" set by peer "Sam2"
    And I should see grade "10.0" for workshop participant "Sam1" set by peer "Sam3"
    And I should see grade "6.0" for workshop participant "Sam1" set by peer "Sam4"
    And I should see "No submission found for this user" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam2')]]" "xpath_element"
    And I should see "No submission found for this user" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam3')]]" "xpath_element"
    And I should see "No submission found for this user" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam4')]]" "xpath_element"
    And I change phase in workshop "TestWorkshop" to "Grading evaluation phase"
    And I press "Re-calculate grades"
    And I should see "8.7" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam1')]]/td[contains(concat(' ', normalize-space(@class), ' '), ' submissiongrade ')]" "xpath_element"
    And I should see "5.0" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam2')]]/td[contains(concat(' ', normalize-space(@class), ' '), ' gradinggrade ')]" "xpath_element"
    And I should see "5.0" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam3')]]/td[contains(concat(' ', normalize-space(@class), ' '), ' gradinggrade ')]" "xpath_element"
    And I should see "3.2" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam4')]]/td[contains(concat(' ', normalize-space(@class), ' '), ' gradinggrade ')]" "xpath_element"
  # teacher1 overrides the grade on assessment by student2
    And I should see "6.0 (3.2)" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam4')]]/td[contains(concat(' ', normalize-space(@class), ' '), ' givengrade ')]" "xpath_element"
    And I should see "3.2" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam4')]]/td[contains(concat(' ', normalize-space(@class), ' '), ' gradinggrade ')]" "xpath_element"
    And I click on ".grade" "css_element" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam4')]]/td[contains(concat(' ', normalize-space(@class), ' '), ' givengrade ')]" "xpath_element"
    And I set the following fields to these values:
      | gradinggradeover | 4 |
    And I press "Save and close"
    And I should see "4.0" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam4')]]/td[contains(concat(' ', normalize-space(@class), ' '), ' givengrade ')]" "xpath_element"
    And I should see "4.0" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam4')]]/td[contains(concat(' ', normalize-space(@class), ' '), ' gradinggrade ')]" "xpath_element"
  # Undo teacher1 overrides the grade on assessment by student2
    And I click on ".grade" "css_element" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam4')]]/td[contains(concat(' ', normalize-space(@class), ' '), ' givengrade ')]" "xpath_element"
    And I set the following fields to these values:
      | gradinggradeover | Not overridden |
    And I press "Save and close"
    And I should see "6.0 (3.2)" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam4')]]/td[contains(concat(' ', normalize-space(@class), ' '), ' givengrade ')]" "xpath_element"
    And I should see "3.2" in the "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam4')]]/td[contains(concat(' ', normalize-space(@class), ' '), ' gradinggrade ')]" "xpath_element"
