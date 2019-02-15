@editor @editor_tinymce @tinymce_managefiles
Feature: Atto managefiles
  To use the tinymce managefiles button, we need permission

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Glossary" to section "1" and I fill the form with:
      | Name        | Test glossary name        |
      | Description | Test glossary description |
    And I navigate to "Users > Permissions" in current page administration
    And I override the system permissions of "Teacher" role with:
      | capability                   | permission |
      | moodle/editor:managefilesuse | Allow      |
    And I override the system permissions of "Student" role with:
      | capability                   | permission |
      | moodle/editor:managefilesuse | Prohibit   |
    And I log out

  @javascript
  Scenario: Teacher with permission can use managefiles button in TinyMCE editor
    Given I log in as "teacher1"
    And I open my profile in edit mode
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"
    And I set the field "Text editor" to "TinyMCE HTML editor"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I click on "Test glossary name" "link"
    When I press "Add a new entry"
    Then ".mce_managefiles" "css_element" should exist in the ".mceToolbar" "css_element"

  @javascript
  Scenario: Student without permission can not use managefiles button in TinyMCE editor
    Given I log in as "student1"
    And I open my profile in edit mode
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"
    And I set the field "Text editor" to "TinyMCE HTML editor"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I click on "Test glossary name" "link"
    When I press "Add a new entry"
    Then ".mce_managefiles" "css_element" should not exist in the ".mceToolbar" "css_element"
