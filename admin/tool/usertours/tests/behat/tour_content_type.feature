@tool @tool_usertours
Feature: Apply content type to a tour
  In order to give more content to a tour
  As an administrator
  I need to change the content type of the user tour

  Background:
    Given I log in as "admin"
    And I add a new user tour with:
      | Name               | First tour    |
      | Description        | My first tour |
      | Apply to URL match | /my/%         |
      | Tour is enabled    | 1             |

  @javascript
  Scenario: User can choose the the content type of the tour step
    Given I open the User tour settings page
    And I click on "View" "link" in the "My first tour" "table_row"
    When I click on "New step" "link"
    Then "Content type" "select" should exist
    And the "Content type" select box should contain "Moodle language string"
    And the "Content type" select box should contain "HTML"
    And I select "Moodle language string" from the "Content type" singleselect
    And I should see "Language identifier"
    And I should see "Component"
    And I select "Moodle language string" from the "Content type" singleselect
    And "#fgroup_id_contenthtmlgrp" "css_element" should not be visible
    And I select "HTML" from the "Content type" singleselect
    And "#fgroup_id_contenthtmlgrp" "css_element" should be visible
    And I should not see "Language identifier"
    And I should not see "Component"

  @javascript
  Scenario: Create a new step with Moodle Language content type
    Given I open the User tour settings page
    And I click on "View" "link" in the "My first tour" "table_row"
    And I click on "New step" "link"
    And I set the field "Title" to "tour_activityinfo_course_teacher_title,tool_usertours"
    And I select "Moodle language string" from the "Content type" singleselect
    And I set the field "Language identifier" to "tour_activityinfo_course_teacher_content_abc"
    When I open the autocomplete suggestions list
    And I click on "User tours (tool_usertours)" item in the autocomplete list
    Then "User tours (tool_usertours)" "autocomplete_selection" should exist
    And I press "Save changes"
    And I should see "Invalid language identifier"
    And I set the field "Language identifier" to "tour_activityinfo_course_teacher_content"
    And I press "Save changes"
    And I should see "New: Activity information"
    And I should see "New course settings 'Show completion conditions' and 'Show activity dates' enable you to choose whether activity completion conditions (if set) and/or dates are displayed for students on the course page."
    And I click on "Edit" "link" in the "New: Activity information" "table_row"
    And the field "Title" matches value "tour_activityinfo_course_teacher_title,tool_usertours"
    And the field "Language identifier" matches value "tour_activityinfo_course_teacher_content"
    And "User tours (tool_usertours)" "autocomplete_selection" should exist

  @javascript
  Scenario: Create a new step with HTML content type
    Given I open the User tour settings page
    And I click on "View" "link" in the "My first tour" "table_row"
    And I click on "New step" "link"
    And I set the field "Title" to "tour_activityinfo_course_teacher_title,tool_usertours"
    And I select "HTML" from the "Content type" singleselect
    And I set the field "id_content" to "<b>Test content</b>"
    And I press "Save changes"
    And I should see "New: Activity information"
    And I should see "Test content"
    And I click on "Edit" "link" in the "New: Activity information" "table_row"
    And I should not see "Language identifier"
    And I should not see "Component"
    And the field "Title" matches value "tour_activityinfo_course_teacher_title,tool_usertours"
    And the field "id_content" matches value "<b>Test content</b>"
