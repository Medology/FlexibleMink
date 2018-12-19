Feature: Assert an element is displayed or not displayed
  In order to ensure an element is displayed
  As a developer
  I should be able to assert an element is displayed

  Background:
    Given I am on "/ScrollableDivs.html"

  Scenario: Assert Test Text is (not) fully displayed
    Then "Big DIV" should not be fully visible in the viewport
     And "All Centered 1" should be fully visible in the viewport
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
    Then "Big DIV" should be visible in the viewport
     And "All Centered 1" should be visible in the viewport
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

  Scenario Outline: Throw an ExpectationException when a test fails
    When I assert that <Step Text to Assert>
    Then the assertion should throw a ExpectationException
     And the assertion should fail with the message "<Expected Exception Message>"

    Examples:
      | Step Text to Assert                                          | Expected Exception Message                           |
      | "Big DIV" should be fully visible in the viewport            | Big DIV is not fully visible in the viewport.        |
      | "All Centered 1" should not be fully visible in the viewport | All Centered 1 is fully visible in the viewport.     |
      | "All Centered 2" should not be fully visible in the viewport | All Centered 2 is fully visible in the viewport.     |
      | "All Centered 3" should not be fully visible in the viewport | All Centered 3 is fully visible in the viewport.     |
      | "All Centered 4" should not be fully visible in the viewport | All Centered 4 is fully visible in the viewport.     |
      | "Partial Left" should be fully visible in the viewport       | Partial Left is not fully visible in the viewport.   |
      | "Partial Right" should be fully visible in the viewport      | Partial Right is not fully visible in the viewport.  |
      | "Partial Top" should be fully visible in the viewport        | Partial Top is not fully visible in the viewport.    |
      | "Partial Bottom" should be fully visible in the viewport     | Partial Bottom is not fully visible in the viewport. |
      | "Out to left" should be fully visible in the viewport        | Out to left is not fully visible in the viewport.    |
      | "Out to right" should be fully visible in the viewport       | Out to right is not fully visible in the viewport.   |
      | "Out to top" should be fully visible in the viewport         | Out to top is not fully visible in the viewport.     |
      | "Out to bottom" should be fully visible in the viewport      | Out to bottom is not fully visible in the viewport.  |
      | "Visible" should not be fully visible in the viewport        | Visible is fully visible in the viewport.            |
      | "Invisible" should be fully visible in the viewport          | Invisible is not fully visible in the viewport.      |
      | "Invisible 2" should be fully visible in the viewport        | Invisible 2 is not fully visible in the viewport.    |
      | "Visible 2" should be fully visible in the viewport          | Visible 2 is not in the DOM, and it should be.       |
      | "Big DIV" should not be visible in the viewport              | Big DIV is visible in the viewport.                  |
      | "All Centered 1" should not be visible in the viewport       | All Centered 1 is visible in the viewport.           |
      | "All Centered 2" should not be visible in the viewport       | All Centered 2 is visible in the viewport.           |
      | "All Centered 3" should not be visible in the viewport       | All Centered 3 is visible in the viewport.           |
      | "All Centered 4" should not be visible in the viewport       | All Centered 4 is visible in the viewport.           |
      | "Partial Left" should not be visible in the viewport         | Partial Left is visible in the viewport.             |
      | "Partial Right" should not be visible in the viewport        | Partial Right is visible in the viewport.            |
      | "Partial Top" should not be visible in the viewport          | Partial Top is visible in the viewport.              |
      | "Partial Bottom" should not be visible in the viewport       | Partial Bottom is visible in the viewport.           |
      | "Out to left" should be visible in the viewport              | Out to left is not visible in the viewport.          |
      | "Out to right" should be visible in the viewport             | Out to right is not visible in the viewport.         |
      | "Out to top" should be visible in the viewport               | Out to top is not visible in the viewport.           |
      | "Out to bottom" should be visible in the viewport            | Out to bottom is not visible in the viewport.        |
      | "Visible" should not be visible in the viewport              | Visible is visible in the viewport.                  |
      | "Invisible" should be visible in the viewport                | Invisible is not visible in the viewport.            |
      | "Invisible 2" should be visible in the viewport              | Invisible 2 is not visible in the viewport.          |
      | "Visible 2" should be visible in the viewport                | Visible 2 is not in the DOM, and it should be.       |
