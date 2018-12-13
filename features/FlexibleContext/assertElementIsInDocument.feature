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
