Feature: Assert element is visible in the document or not
  In order to ensure an element is displayed
  As a developer
  I should be able to assert an element is displayed

  Background:
    Given I am on "/ScrollableDivs.html"

  Scenario: Assert button is (not) visible in the document
    Then "Button 1" should be visible in the document
     And "Button 2" should not be visible in the document
     And "Button 3" should be visible in the document
     And "Button 4" should not be visible in the document
     And "Button 5" should not be visible in the document
     And "Button 6" should not be visible in the document

  Scenario Outline: Throw an exception when a test fails
    When I assert that <Step Text to Assert>
    Then the assertion should throw a ExpectationException
    And the assertion should fail with the message "<Expected Exception Message>"

    Examples:
      | Step Text to Assert                              | Expected Exception Message                    |
      | "Button 1" should not be visible in the document | Button 1 is visible in the document.          |
      | "Button 2" should be visible in the document     | Button 2 is not visible in the document.      |
      | "Button 3" should not be visible in the document | Button 3 is visible in the document.          |
      | "Button 4" should be visible in the document     | Button 4 is not visible in the document.      |
      | "Button 5" should be visible in the document     | Button 5 is not visible in the document.      |
      | "Button 6" should be visible in the document     | Button 6 is not in the DOM, and it should be. |
