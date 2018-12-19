Feature: Assert element is visible in the document or not
  In order to ensure an element is displayed
  As a developer
  I should be able to assert an element is displayed

  Background:
    Given I am on "/ScrollableDivs.html"

  Scenario: Assert button is (not) visible in the document
    Then "Plain Sight Button" should be visible in the document
     And "Hidden Button" should not be visible in the document
     And "Scrolled Out Of Sight Button" should be visible in the document
     And "Scrolled Out Of Sight Button With No Display" should not be visible in the document
     And "Scrolled Out Of Sight Button Inside a Not Displayed DIV" should not be visible in the document
     And "A Non-Existent Button" should not be visible in the document

  Scenario Outline: Throw an exception when a test fails
    When I assert that <Step Text to Assert>
    Then the assertion should throw a ExpectationException
    And the assertion should fail with the message "<Expected Exception Message>"

    Examples:
      | Step Text to Assert                                                                         | Expected Exception Message                                                              |
      | "Plain Sight Button" should not be visible in the document                                  | Plain Sight Button is visible in the document.                                          |
      | "Hidden Button" should be visible in the document                                           | Hidden Button is not visible in the document.                                           |
      | "Scrolled Out Of Sight Button" should not be visible in the document                        | Scrolled Out Of Sight Button is visible in the document.                                |
      | "Scrolled Out Of Sight Button With No Display" should be visible in the document            | Scrolled Out Of Sight Button With No Display is not visible in the document.            |
      | "Scrolled Out Of Sight Button Inside a Not Displayed DIV" should be visible in the document | Scrolled Out Of Sight Button Inside a Not Displayed DIV is not visible in the document. |
      | "A Non-Existent Button" should be visible in the document                                   | A Non-Existent Button was not found in the document.                                    |
