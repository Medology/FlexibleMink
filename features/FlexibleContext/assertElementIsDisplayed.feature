Feature: Assert an element is displayed or not displayed
  In order to ensure an element is displayed
  As a developer
  I should be able to assert an element is displayed

  Background:
    Given I am on "/ScrollableDivs.html"

  Scenario: Assert visibility status of test elements
    Then "Big DIV" should be partially visible in the viewport
    And "All Centered 1" should be fully visible in the viewport
    And "All Centered 2" should be fully visible in the viewport
    And "All Centered 3" should be fully visible in the viewport
    And "All Centered 4" should be fully visible in the viewport

    And "Partial Left" should be partially visible in the viewport
    And "Partial Right" should be partially visible in the viewport
    And "Partial Top" should be partially visible in the viewport
    And "Partial Bottom" should be partially visible in the viewport

    And "Out to left" should not be visible in the viewport
    And "Out to right" should not be visible in the viewport
    And "Out to top" should not be visible in the viewport
    And "Out to bottom" should not be visible in the viewport

    And "Visible" should be fully visible in the viewport
    And "Invisible" should not be visible in the viewport
    And "Invisible 2" should not be visible in the viewport
    And "Visible 2" should not be visible in the viewport

  Scenario Outline: Throw a ExpectationException when visibility test fails
    When I assert that <Step Text to Assert>
    Then the assertion should throw a ExpectationException
    And the assertion should fail with the message "<Expected Exception Message>"

    Examples:
      | Step Text to Assert                                          | Expected Exception Message                               |
      | "Big DIV" should not be visible in the viewport              | Big DIV is visible in the viewport.                      |
      | "All Centered 1" should not be visible in the viewport       | All Centered 1 is visible in the viewport.               |
      | "All Centered 2" should not be visible in the viewport       | All Centered 2 is visible in the viewport.               |
      | "All Centered 3" should not be visible in the viewport       | All Centered 3 is visible in the viewport.               |
      | "All Centered 4" should not be visible in the viewport       | All Centered 4 is visible in the viewport.               |
      | "Big DIV" should be fully visible in the viewport            | Big DIV is not fully visible in the viewport.            |
      | "All Centered 1" should be partially visible in the viewport | All Centered 1 is not partially visible in the viewport. |
      | "All Centered 2" should be partially visible in the viewport | All Centered 2 is not partially visible in the viewport. |
      | "All Centered 3" should be partially visible in the viewport | All Centered 3 is not partially visible in the viewport. |
      | "All Centered 4" should be partially visible in the viewport | All Centered 4 is not partially visible in the viewport. |
      | "Partial Left" should be fully visible in the viewport       | Partial Left is not fully visible in the viewport.       |
      | "Partial Right" should be fully visible in the viewport      | Partial Right is not fully visible in the viewport.      |
      | "Partial Top" should be fully visible in the viewport        | Partial Top is not fully visible in the viewport.        |
      | "Partial Bottom" should be fully visible in the viewport     | Partial Bottom is not fully visible in the viewport.     |
      | "Partial Left" should not be visible in the viewport         | Partial Left is visible in the viewport.                 |
      | "Partial Right" should not be visible in the viewport        | Partial Right is visible in the viewport.                |
      | "Partial Top" should not be visible in the viewport          | Partial Top is visible in the viewport.                  |
      | "Partial Bottom" should not be visible in the viewport       | Partial Bottom is visible in the viewport.               |
      | "Out to left" should be fully visible in the viewport        | Out to left is not fully visible in the viewport.        |
      | "Out to right" should be fully visible in the viewport       | Out to right is not fully visible in the viewport.       |
      | "Out to top" should be fully visible in the viewport         | Out to top is not fully visible in the viewport.         |
      | "Out to bottom" should be fully visible in the viewport      | Out to bottom is not fully visible in the viewport.      |
      | "Out to left" should be partially visible in the viewport    | Out to left is not partially visible in the viewport.    |
      | "Out to right" should be partially visible in the viewport   | Out to right is not partially visible in the viewport.   |
      | "Out to top" should be partially visible in the viewport     | Out to top is not partially visible in the viewport.     |
      | "Out to bottom" should be partially visible in the viewport  | Out to bottom is not partially visible in the viewport.  |
      | "Visible" should not be visible in the viewport              | Visible is visible in the viewport.                      |
      | "Visible 2" should be fully visible in the viewport          | Visible 2 was not found in the document.                 |
      | "Invisible" should be fully visible in the viewport          | Invisible is not fully visible in the viewport.          |
      | "Invisible 2" should be fully visible in the viewport        | Invisible 2 is not fully visible in the viewport.        |
      | "Visible" should be partially visible in the viewport        | Visible is not partially visible in the viewport.        |
      | "Visible 2" should be partially visible in the viewport      | Visible 2 was not found in the document.                 |
      | "Invisible" should be partially visible in the viewport      | Invisible is not partially visible in the viewport.      |
      | "Invisible 2" should be partially visible in the viewport    | Invisible 2 is not partially visible in the viewport.    |
      | "Visible 2" should be fully visible in the viewport          | Visible 2 was not found in the document.                 |
