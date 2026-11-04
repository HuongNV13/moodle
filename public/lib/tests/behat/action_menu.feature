@core
Feature: Navigate action menu
  In order to navigate an action menu
  As a user
  I need to be able to use the keyboard

  @javascript
  Scenario: The menu does not close on keyboard navigation
    When I log in as "admin"
    # Click to open the user menu.
    And I open the user menu
    # The menu should now be visible.
    Then the user menu should be expanded
    # Press down arrow.
    And I press the down key
    # The menu should still be visible.
    And the user menu should be expanded

  @javascript
  Scenario: The menu closes when it clicked outside
    When I log in as "admin"
    # Click to open the user menu.
    And I open the user menu
    # The menu should now be visible.
    Then the user menu should be expanded
    # Click outside the menu.
    And I click on "adminsearchquery" "field"
    # The menu should now be hidden.
    And the user menu should not be expanded
