@theme_boost @accessibility
Feature: User menu accessibility
  In order to use the user menu in either colour mode
  As a user
  I need the user menu to meet the same accessibility standards whichever colour mode is in use

  Background:
    Given the following config values are set as admin:
      | enablecolourmodes | 1 | theme_boost |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |

  @javascript
  Scenario Outline: The user menu meets accessibility standards in <mode> mode
    Given the following "user preferences" exist:
      | user     | preference             | value  |
      | student1 | theme_boost_colourmode | <mode> |
    And I log in as "student1"
    When I open the user menu
    Then the user menu should be expanded
    And "Log out" "link" should exist in the user menu
    And the page should meet accessibility standards with "best-practice" extra tests

    Examples:
      | mode  |
      | light |
      | dark  |
