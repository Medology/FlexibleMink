Feature: Assert an element is displayed or not displayed
  In order to ensure an element is displayed
  As a developer
  I should be able to assert an element is displayed

  Background:
    Given I am on "/ScrollableDivs.html"

  Scenario: Assert Test Text is (not) fully displayed
    Then "All Centered 1" should be fully visible in the viewport
    And "All Centered 2" should be fully visible in the viewport
    And "All Centered 3" should be fully visible in the viewport
    And "All Centered 4" should be fully visible in the viewport
    And "Partial Left" should not be fully visible in the viewport
    And "Partial Right" should not be fully visible in the viewport
    And "Partial Top" should not be fully visible in the viewport
    And "Partial Bottom" should not be fully visible in the viewport
    And "Out to left" should not be fully visible in the viewport
    And "Out to right" should not be fully visible in the viewport
    And "Out to top" should not be fully visible in the viewport
    And "Out to bottom" should not be fully visible in the viewport
    And "Visible" should be fully visible in the viewport
    And "Invisible" should not be fully visible in the viewport
    And "Invisible 2" should not be fully visible in the viewport
    And "Visible 2" should not be fully visible in the viewport

  Scenario: Assert Test Text is (not) displayed
    Then "All Centered 1" should be visible in the viewport
    And "All Centered 2" should be visible in the viewport
    And "All Centered 3" should be visible in the viewport
    And "All Centered 4" should be visible in the viewport
    And "Partial Left" should be visible in the viewport
    And "Partial Right" should be visible in the viewport
    And "Partial Top" should be visible in the viewport
    And "Partial Bottom" should be visible in the viewport
    And "Out to left" should not be visible in the viewport
    And "Out to right" should not be visible in the viewport
    And "Out to top" should not be visible in the viewport
    And "Out to bottom" should not be visible in the viewport
    And "Visible" should be visible in the viewport
    And "Invisible" should not be visible in the viewport
    And "Invisible 2" should not be visible in the viewport
    And "Visible 2" should not be visible in the viewport
